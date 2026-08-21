<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\AiProviderManager;
use App\Services\Ai\DTO\AiMessage;
use Illuminate\Console\Command;

/**
 * Verifica que el proveedor de IA esté bien configurado y respondiendo.
 *
 * Prueba las tres capacidades que el asesor usa de verdad: conversación,
 * salida estructurada y embeddings. Nunca imprime la API key: sólo si está
 * presente y con qué prefijo/longitud, que alcanza para detectar un copiado
 * incompleto sin filtrarla.
 *
 *     php artisan bmh:ai-check
 */
final class AiCheckCommand extends Command
{
    protected $signature = 'bmh:ai-check';

    protected $description = 'Verifica la conexión con el proveedor de IA (OpenAI / Gemini)';

    public function handle(AiProviderManager $providers): int
    {
        $configured = (string) config('bmh.ai.provider');
        $key        = (string) config('bmh.ai.api_key');
        $provider   = $providers->primary();

        $this->newLine();
        $this->line('<options=bold>BMH — Verificación del proveedor de IA</>');
        $this->newLine();

        $this->table(['Parámetro', 'Valor'], [
            ['AI_ENABLED', config('bmh.features.ai') ? 'true' : 'false'],
            ['AI_PROVIDER (configurado)', $configured],
            ['Proveedor activo', $provider->name()],
            ['API key', $this->describeKey($key)],
            ['Modelo conversación', $providers->modelFor('conversation') ?? '—'],
            ['Modelo rápido', $providers->modelFor('extraction') ?? '—'],
            ['Modelo visión', $providers->modelFor('vision') ?? '—'],
            ['Visión habilitada', config('bmh.features.vision') ? 'true' : 'false'],
        ]);

        if ($provider->name() === 'mock') {
            $this->newLine();

            if ($configured === 'mock') {
                $this->warn('AI_PROVIDER está en `mock`. Cambialo a `openai` o `gemini` en el .env.');
            } else {
                $this->error("AI_PROVIDER es `{$configured}` pero no hay API key: el sistema cayó a `mock`.");
                $this->line('  Pegá la clave en AI_API_KEY= dentro del .env y corré: php artisan config:clear');
            }

            $this->newLine();
            $this->line('<fg=gray>La demo funciona igual con `mock`, pero sin IA real.</>');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Probando las tres capacidades contra el proveedor…');
        $this->newLine();

        $ok = true;

        $ok = $this->probe('Conversación', function () use ($provider): string {
            $response = $provider->chat([
                AiMessage::system('Respondé con una sola palabra.'),
                AiMessage::user('Decí: listo'),
            ]);

            if ($response->failed) {
                throw new \RuntimeException($response->error ?? 'sin respuesta');
            }

            if (trim($response->text) === '') {
                throw new \RuntimeException('respondió vacío');
            }

            return sprintf(
                '"%s" · %d ms · %d tokens',
                mb_substr(trim($response->text), 0, 40),
                (int) $response->latencyMs,
                $response->usage?->total() ?? 0,
            );
        }) && $ok;

        $ok = $this->probe('Salida estructurada', function () use ($provider): string {
            $result = $provider->structuredOutput(
                'El cliente dice: "necesito un rotor Bosch de 12v". Devolvé el JSON del esquema.',
                [
                    'type'       => 'object',
                    'properties' => [
                        'part'  => ['type' => 'string'],
                        'brand' => ['type' => 'string'],
                    ],
                    'required' => ['part'],
                ],
            );

            if ($result === []) {
                throw new \RuntimeException('devolvió vacío');
            }

            return json_encode($result, JSON_UNESCAPED_UNICODE) ?: '{}';
        }) && $ok;

        $ok = $this->probe('Embeddings', function () use ($provider): string {
            $vector = $provider->embed('rotor bosch 12v');

            if ($vector === []) {
                throw new \RuntimeException('devolvió un vector vacío');
            }

            return sprintf('%d dimensiones', count($vector));
        }) && $ok;

        $this->newLine();

        if ($ok) {
            $this->line('<options=bold;fg=green>Todo OK.</> El asesor va a usar IA real.');
            $this->line('<fg=gray>Probá el flujo completo: php artisan bmh:demo-flow</fg=gray>');
            $this->newLine();

            return self::SUCCESS;
        }

        $this->error('Alguna capacidad falló. Revisá la key, el saldo de la cuenta y el nombre del modelo.');
        $this->newLine();

        return self::FAILURE;
    }

    private function probe(string $label, callable $test): bool
    {
        $this->output->write(sprintf('  %-22s ', $label));

        try {
            $detail = $test();
            $this->line("<fg=green>OK</>  <fg=gray>{$detail}</>");

            return true;
        } catch (\Throwable $e) {
            $this->line('<fg=red>FALLÓ</>  <fg=gray>' . $this->redact($e->getMessage()) . '</>');

            return false;
        }
    }

    /** Ni siquiera acá se imprime la clave: sólo forma y longitud. */
    private function describeKey(string $key): string
    {
        if (trim($key) === '') {
            return 'VACÍA';
        }

        return sprintf('presente · %s… · %d caracteres', mb_substr($key, 0, 6), mb_strlen($key));
    }

    private function redact(string $message): string
    {
        return mb_substr(
            \App\Services\Ai\ConversationOrchestrator::redactSecrets($message),
            0,
            160,
        );
    }
}
