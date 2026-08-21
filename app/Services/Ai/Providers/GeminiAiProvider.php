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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini.
 *
 * La API key se lee de config y nunca se loguea ni se manda al frontend.
 * El system prompt va en `systemInstruction`, separado del contenido del
 * usuario, que es lo que hace que el texto del cliente no pueda hacerse pasar
 * por instrucción.
 */
final class GeminiAiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function isAvailable(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function chat(array $messages, array $tools = [], array $options = []): AiResponse
    {
        $model   = $options['model'] ?? $this->config['chat_model'];
        $payload = ['contents' => []];

        foreach ($messages as $message) {
            if ($message->role === AiMessage::ROLE_SYSTEM) {
                $payload['systemInstruction'] = ['parts' => [['text' => $message->content]]];
                continue;
            }

            $payload['contents'][] = $this->toContent($message);
        }

        if ($tools !== []) {
            $payload['tools'] = [[
                'functionDeclarations' => array_map(
                    static fn (array $tool): array => [
                        'name'        => $tool['name'],
                        'description' => $tool['description'],
                        'parameters'  => $tool['parameters'],
                    ],
                    $tools
                ),
            ]];
        }

        $response = $this->request($model, $payload);

        if ($response === null) {
            return AiResponse::failure('Gemini no respondió.', 'gemini');
        }

        [$body, $latency] = $response;

        $parts     = $body['candidates'][0]['content']['parts'] ?? [];
        $text      = '';
        $toolCalls = [];

        foreach ($parts as $index => $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }

            if (isset($part['functionCall'])) {
                $toolCalls[] = new AiToolCall(
                    id: 'call_' . $index,
                    name: (string) $part['functionCall']['name'],
                    arguments: (array) ($part['functionCall']['args'] ?? []),
                );
            }
        }

        return new AiResponse(
            text: trim($text),
            toolCalls: $toolCalls,
            usage: $this->usage($body),
            provider: 'gemini',
            model: $model,
            latencyMs: $latency,
        );
    }

    public function analyzeImage(string $imagePath, string $context = ''): ImageAnalysis
    {
        if (! is_file($imagePath)) {
            return ImageAnalysis::unusable('No se pudo leer la imagen.');
        }

        $mime = mime_content_type($imagePath) ?: 'image/jpeg';

        $payload = [
            'systemInstruction' => ['parts' => [['text' => ImageAnalysisSchema::systemPrompt()]]],
            'contents'          => [[
                'role'  => 'user',
                'parts' => [
                    ['text' => ImageAnalysisSchema::userPrompt($context)],
                    ['inlineData' => [
                        'mimeType' => $mime,
                        'data'     => base64_encode((string) file_get_contents($imagePath)),
                    ]],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema'   => ImageAnalysisSchema::geminiSchema(),
                'temperature'      => 0.1,
            ],
        ];

        $response = $this->request($this->config['vision_model'], $payload);

        if ($response === null) {
            return ImageAnalysis::unusable('El servicio de análisis de imágenes no está disponible.');
        }

        [$body] = $response;

        $raw     = $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return ImageAnalysis::unusable('La respuesta de análisis no se pudo interpretar.');
        }

        return ImageAnalysisSchema::hydrate($decoded, $this->usage($body));
    }

    public function structuredOutput(string $prompt, array $schema, array $options = []): array
    {
        $model = $options['model'] ?? $this->config['fast_model'];

        $payload = [
            'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema'   => $schema,
                'temperature'      => 0.0,
            ],
        ];

        if (isset($options['system'])) {
            $payload['systemInstruction'] = ['parts' => [['text' => $options['system']]]];
        }

        $response = $this->request($model, $payload);

        if ($response === null) {
            return [];
        }

        [$body] = $response;

        $decoded = json_decode($body['candidates'][0]['content']['parts'][0]['text'] ?? '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    public function embed(string $text): array
    {
        $model = $this->config['embedding_model'];

        try {
            $response = Http::timeout((int) config('bmh.ai.timeout', 30))
                ->post(
                    sprintf('%s/models/%s:embedContent?key=%s', $this->config['base_url'], $model, $this->config['api_key']),
                    ['content' => ['parts' => [['text' => $text]]]]
                );

            return (array) ($response->json('embedding.values') ?? []);
        } catch (\Throwable $e) {
            $this->logFailure('embed', $e);

            return [];
        }
    }

    /** @return array{0: array, 1: float}|null */
    private function request(string $model, array $payload): ?array
    {
        $started = microtime(true);

        try {
            $response = Http::timeout((int) config('bmh.ai.timeout', 30))
                ->post(
                    sprintf(
                        '%s/models/%s:generateContent?key=%s',
                        $this->config['base_url'],
                        $model,
                        $this->config['api_key'],
                    ),
                    $payload
                );

            if ($response->failed()) {
                // El body puede traer la key en el mensaje de error; se loguea
                // sólo el status.
                Log::warning('bmh.ai.gemini.http_error', ['status' => $response->status()]);

                return null;
            }

            return [$response->json() ?? [], (microtime(true) - $started) * 1000];
        } catch (\Throwable $e) {
            $this->logFailure('chat', $e);

            return null;
        }
    }

    private function toContent(AiMessage $message): array
    {
        $role = match ($message->role) {
            AiMessage::ROLE_ASSISTANT => 'model',
            default                   => 'user',
        };

        if ($message->role === AiMessage::ROLE_TOOL) {
            return [
                'role'  => 'user',
                'parts' => [[
                    'functionResponse' => [
                        'name'     => $message->toolName,
                        'response' => ['result' => json_decode($message->content, true) ?? []],
                    ],
                ]],
            ];
        }

        $parts = [['text' => $message->content]];

        foreach ($message->imagePaths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $parts[] = ['inlineData' => [
                'mimeType' => mime_content_type($path) ?: 'image/jpeg',
                'data'     => base64_encode((string) file_get_contents($path)),
            ]];
        }

        return ['role' => $role, 'parts' => $parts];
    }

    private function usage(array $body): AiUsage
    {
        $meta = $body['usageMetadata'] ?? [];

        return new AiUsage(
            inputTokens: (int) ($meta['promptTokenCount'] ?? 0),
            outputTokens: (int) ($meta['candidatesTokenCount'] ?? 0),
        );
    }

    private function logFailure(string $operation, \Throwable $e): void
    {
        Log::warning('bmh.ai.gemini.failure', [
            'operation' => $operation,
            'exception' => $e::class,
            // Sin mensaje completo: puede contener la URL con la key.
        ]);
    }
}
