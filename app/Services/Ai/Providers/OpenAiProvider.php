<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\AiMessage;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\DTO\AiToolCall;
use App\Services\Ai\DTO\AiUsage;
use App\Services\Ai\DTO\ImageAnalysis;
use App\Services\Ai\Support\ImageAnalysisSchema;
use App\Services\Ai\Support\ImagePayload;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI (Chat Completions).
 *
 * Misma interfaz que Gemini: el orquestador no sabe cuál está activo.
 */
final class OpenAiProvider implements AiProviderInterface
{
    /** Último error HTTP, para poder decir "401" en vez de "no respondió". */
    private ?string $lastError = null;

    public function __construct(
        private readonly array $config,
    ) {
    }

    public function name(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function chat(array $messages, array $tools = [], array $options = []): AiResponse
    {
        $payload = [
            'model'    => $options['model'] ?? $this->config['chat_model'],
            'messages' => $this->toMessages($messages),
        ];

        if ($tools !== []) {
            $payload['tools'] = array_map(
                static fn (array $tool): array => [
                    'type'     => 'function',
                    'function' => [
                        'name'        => $tool['name'],
                        'description' => $tool['description'],
                        'parameters'  => $tool['parameters'],
                    ],
                ],
                $tools
            );
        }

        $result = $this->request('chat/completions', $payload);

        if ($result === null) {
            return AiResponse::failure($this->lastError ?? 'OpenAI no respondió.', 'openai');
        }

        [$body, $latency] = $result;

        $choice    = $body['choices'][0]['message'] ?? [];
        $toolCalls = [];

        foreach ($choice['tool_calls'] ?? [] as $call) {
            $toolCalls[] = new AiToolCall(
                id: (string) ($call['id'] ?? uniqid('call_', true)),
                name: (string) ($call['function']['name'] ?? ''),
                arguments: (array) (json_decode((string) ($call['function']['arguments'] ?? '{}'), true) ?? []),
            );
        }

        return new AiResponse(
            text: trim((string) ($choice['content'] ?? '')),
            toolCalls: $toolCalls,
            usage: $this->usage($body),
            provider: 'openai',
            model: (string) $payload['model'],
            latencyMs: $latency,
        );
    }

    public function analyzeImage(string $imagePath, string $context = ''): ImageAnalysis
    {
        if (! is_file($imagePath)) {
            return ImageAnalysis::unusable('No se pudo leer la imagen.');
        }

                [$mimeImagen, $base64] = ImagePayload::encode($imagePath);

        $payload = [
            'model'    => $this->config['vision_model'],
            'messages' => [
                ['role' => 'system', 'content' => ImageAnalysisSchema::systemPrompt()],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => ImageAnalysisSchema::userPrompt($context)],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeImagen};base64,{$base64}"]],
                ]],
            ],
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'bmh_image_analysis',
                    'strict' => false,
                    'schema' => ImageAnalysisSchema::jsonSchema(),
                ],
            ],
            ...$this->temperature(0.1, (string) $this->config['vision_model']),
        ];

        $result = $this->request('chat/completions', $payload);

        if ($result === null) {
            return ImageAnalysis::unusable('El servicio de análisis de imágenes no está disponible.');
        }

        [$body] = $result;

        $decoded = json_decode((string) ($body['choices'][0]['message']['content'] ?? '{}'), true);

        if (! is_array($decoded)) {
            return ImageAnalysis::unusable('La respuesta de análisis no se pudo interpretar.');
        }

        return ImageAnalysisSchema::hydrate($decoded, $this->usage($body));
    }

    /**
     * Compara la foto del cliente contra las de catálogo en UNA sola llamada.
     *
     * Se manda todo junto —foto del cliente primero, después cada candidato
     * precedido por su número— para que el modelo las vea lado a lado. En
     * llamadas separadas no podría comparar: sólo describiría cada una.
     */
    public function compareImages(
        string $customerImagePath,
        array $catalogImagePaths,
        string $systemPrompt,
        array $schema,
    ): array {
        if (! is_file($customerImagePath) || $catalogImagePaths === []) {
            return [];
        }

        $contenido = [
            ['type' => 'text', 'text' => 'FOTO DEL CLIENTE:'],
            $this->imagenComoParte($customerImagePath),
        ];

        foreach ($catalogImagePaths as $indice => $ruta) {
            if (! is_file($ruta)) {
                continue;
            }

            $contenido[] = ['type' => 'text', 'text' => 'CANDIDATO ' . $indice . ':'];
            $contenido[] = $this->imagenComoParte($ruta);
        }

        // Menos de dos candidatos con imagen: no hay nada que comparar.
        if (count($contenido) < 6) {
            return [];
        }

        $modelo = (string) $this->config['vision_model'];

        $result = $this->request('chat/completions', [
            'model'    => $modelo,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $contenido],
            ],
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => ['name' => 'bmh_image_comparison', 'strict' => false, 'schema' => $schema],
            ],
            ...$this->temperature(0.0, $modelo),
        ]);

        if ($result === null) {
            return [];
        }

        [$body] = $result;

        $decoded = json_decode((string) ($body['choices'][0]['message']['content'] ?? '{}'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, mixed> */
    private function imagenComoParte(string $ruta): array
    {
        return [
            'type'      => 'image_url',
            'image_url' => [
                'url' => ImagePayload::dataUri($ruta),
                // `low` alcanza para comparar formas y abarata mucho la llamada.
                'detail' => 'low',
            ],
        ];
    }

    public function structuredOutput(string $prompt, array $schema, array $options = []): array
    {
        $messages = [];

        if (isset($options['system'])) {
            $messages[] = ['role' => 'system', 'content' => $options['system']];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $model = (string) ($options['model'] ?? $this->config['fast_model']);

        $result = $this->request('chat/completions', [
            'model'           => $model,
            'messages'        => $messages,
            'response_format' => [
                'type'        => 'json_schema',
                'json_schema' => ['name' => 'bmh_structured', 'strict' => false, 'schema' => $schema],
            ],
            ...$this->temperature(0.0, $model),
        ]);

        if ($result === null) {
            return [];
        }

        [$body] = $result;

        $decoded = json_decode((string) ($body['choices'][0]['message']['content'] ?? '{}'), true);

        return is_array($decoded) ? $decoded : [];
    }

    public function embed(string $text): array
    {
        $result = $this->request('embeddings', [
            'model' => $this->config['embedding_model'],
            'input' => $text,
        ]);

        if ($result === null) {
            return [];
        }

        [$body] = $result;

        return (array) ($body['data'][0]['embedding'] ?? []);
    }

    /** @return array{0: array, 1: float}|null */
    private function request(string $endpoint, array $payload): ?array
    {
        $started = microtime(true);

        try {
            $response = Http::withToken((string) $this->config['api_key'])
                ->timeout((int) config('bmh.ai.timeout', 30))
                ->post(rtrim((string) $this->config['base_url'], '/') . '/' . $endpoint, $payload);

            if ($response->failed()) {
                // El `message` de OpenAI dice exactamente qué está mal (clave
                // inválida, modelo inexistente, sin saldo). Es seguro mostrarlo:
                // no contiene la credencial.
                $detail = (string) ($response->json('error.message') ?? '');

                $this->lastError = trim(sprintf(
                    'OpenAI respondió HTTP %d%s',
                    $response->status(),
                    $detail === '' ? '' : ': ' . mb_substr($detail, 0, 200),
                ));

                Log::warning('bmh.ai.openai.http_error', [
                    'status'   => $response->status(),
                    'endpoint' => $endpoint,
                    'message'  => mb_substr($detail, 0, 200),
                ]);

                return null;
            }

            $this->lastError = null;

            return [$response->json() ?? [], (microtime(true) - $started) * 1000];
        } catch (\Throwable $e) {
            $this->lastError = 'No se pudo conectar con OpenAI (' . $e::class . ').';

            Log::warning('bmh.ai.openai.failure', ['exception' => $e::class, 'endpoint' => $endpoint]);

            return null;
        }
    }

    /**
     * `temperature` sólo para modelos que lo aceptan.
     *
     * Los modelos de razonamiento de OpenAI (familia `o*`, `gpt-5*`) rechazan el
     * parámetro con un 400. Omitirlo es más seguro que mandarlo siempre: si
     * alguien configura AI_CHAT_MODEL con uno de esos, sigue funcionando.
     *
     * @return array<string, float>
     */
    private function temperature(float $value, string $model): array
    {
        $model = mb_strtolower($model);

        $rejectsTemperature = preg_match('/^(o\\d|gpt-5)/', $model) === 1;

        return $rejectsTemperature ? [] : ['temperature' => $value];
    }

    /**
     * Serializa la conversación al dialecto de Chat Completions.
     *
     * OpenAI exige que todo mensaje con rol `tool` responda a un `assistant`
     * previo que haya declarado esos `tool_calls`; si falta, rechaza la llamada
     * entera con un 400 y la respuesta termina saliendo del texto de
     * contingencia en vez de la IA.
     *
     * Nuestro orquestador no simula una ronda de tool calling —resuelve las
     * herramientas él mismo y le pasa los resultados ya listos—, así que el
     * `assistant` que OpenAI espera hay que sintetizarlo acá. Es una exigencia
     * del formato de este proveedor, no del dominio: por eso vive en el
     * adaptador y no en el orquestador.
     *
     * @param  list<AiMessage> $messages
     * @return list<array<string, mixed>>
     */
    private function toMessages(array $messages): array
    {
        $salida = [];
        $ronda  = [];

        // Cierra una ronda: primero el assistant que declara las llamadas, y
        // después los resultados que las responden. En ese orden y pegados.
        $cerrarRonda = function () use (&$salida, &$ronda): void {
            if ($ronda === []) {
                return;
            }

            $salida[] = [
                'role'       => 'assistant',
                'content'    => null,
                'tool_calls' => array_column($ronda, 'call'),
            ];

            foreach ($ronda as $paso) {
                $salida[] = $paso['result'];
            }

            $ronda = [];
        };

        foreach ($messages as $i => $message) {
            if ($message->role !== AiMessage::ROLE_TOOL) {
                $cerrarRonda();
                $salida[] = $this->toMessage($message);

                continue;
            }

            $id = $message->toolCallId ?? 'call_' . $i;

            $ronda[] = [
                'call' => [
                    'id'       => $id,
                    'type'     => 'function',
                    'function' => [
                        'name'      => $message->toolName ?? 'tool_' . $i,
                        'arguments' => '{}',
                    ],
                ],
                'result' => [
                    'role'         => 'tool',
                    'tool_call_id' => $id,
                    'content'      => $message->content,
                ],
            ];
        }

        $cerrarRonda();

        return $salida;
    }

    private function toMessage(AiMessage $message): array
    {
        if ($message->role === AiMessage::ROLE_TOOL) {
            return [
                'role'         => 'tool',
                'tool_call_id' => $message->toolCallId ?? 'call_0',
                'content'      => $message->content,
            ];
        }

        if (! $message->hasImages()) {
            return ['role' => $message->role, 'content' => $message->content];
        }

        $content = [['type' => 'text', 'text' => $message->content]];

        foreach ($message->imagePaths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $content[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => ImagePayload::dataUri($path)],
            ];
        }

        return ['role' => $message->role, 'content' => $content];
    }

    private function usage(array $body): AiUsage
    {
        $usage = $body['usage'] ?? [];

        return new AiUsage(
            inputTokens: (int) ($usage['prompt_tokens'] ?? 0),
            outputTokens: (int) ($usage['completion_tokens'] ?? 0),
        );
    }
}
