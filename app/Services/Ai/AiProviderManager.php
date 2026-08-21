<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Providers\GeminiAiProvider;
use App\Services\Ai\Providers\MockAiProvider;
use App\Services\Ai\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Log;

/**
 * Elige el proveedor y, si está habilitado, hace fallback.
 *
 * Reglas:
 *  - Si `AI_ENABLED=false` o no hay API key → MockAiProvider. La demo funciona.
 *  - El fallback NO se activa solo: cambiar de proveedor cuesta plata, así que
 *    requiere `AI_FALLBACK_ENABLED=true` explícito.
 */
final class AiProviderManager
{
    /** @var array<string, AiProviderInterface> */
    private array $resolved = [];

    public function primary(): AiProviderInterface
    {
        if (! config('bmh.features.ai')) {
            return $this->make('mock');
        }

        $provider = $this->make((string) config('bmh.ai.provider', 'mock'));

        if (! $provider->isAvailable()) {
            // Sin credenciales no se rompe nada: se sigue con el mock y se deja
            // constancia una sola vez.
            Log::info('bmh.ai.provider.unavailable_falling_back_to_mock', ['provider' => $provider->name()]);

            return $this->make('mock');
        }

        return $provider;
    }

    public function fallback(): ?AiProviderInterface
    {
        if (! config('bmh.features.fallback')) {
            return null;
        }

        $name = (string) config('bmh.ai.fallback_provider', 'mock');

        if ($name === (string) config('bmh.ai.provider')) {
            return null;
        }

        $provider = $this->make($name);

        return $provider->isAvailable() ? $provider : null;
    }

    /**
     * Ejecuta una operación contra el proveedor primario y, si falla y el
     * fallback está habilitado, la reintenta contra el secundario.
     *
     * @template T
     * @param  callable(AiProviderInterface): T $operation
     * @param  callable(T): bool                $succeeded
     * @return T
     */
    public function run(callable $operation, callable $succeeded): mixed
    {
        $result = $operation($this->primary());

        if ($succeeded($result)) {
            return $result;
        }

        $fallback = $this->fallback();

        if ($fallback === null) {
            return $result;
        }

        Log::info('bmh.ai.provider.fallback_engaged', ['provider' => $fallback->name()]);

        return $operation($fallback);
    }

    public function make(string $name): AiProviderInterface
    {
        return $this->resolved[$name] ??= match ($name) {
            'gemini' => new GeminiAiProvider((array) config('bmh.ai.providers.gemini')),
            'openai' => new OpenAiProvider((array) config('bmh.ai.providers.openai')),
            default  => new MockAiProvider(),
        };
    }

    /**
     * Registra una implementación concreta bajo un nombre.
     *
     * Sirve para enchufar un proveedor propio sin tocar el manager, y es lo que
     * usan los tests para simular una caída de Gemini sin salir a la red.
     */
    public function extend(string $name, AiProviderInterface $provider): void
    {
        $this->resolved[$name] = $provider;
    }

    /** Modelo a usar para una tarea, según el AiModelRouter de config. */
    public function modelFor(string $task): ?string
    {
        $providerName = $this->primary()->name();

        if ($providerName === 'mock') {
            return null;
        }

        $slot   = (string) config('bmh.ai.routing.' . $task, 'chat');
        $config = (array) config('bmh.ai.providers.' . $providerName);

        return match ($slot) {
            'fast'   => $config['fast_model'] ?? null,
            'vision' => $config['vision_model'] ?? null,
            default  => $config['chat_model'] ?? null,
        };
    }

    /** Para el AI debug mode y el header del chat. */
    public function describe(): array
    {
        $primary = $this->primary();

        return [
            'provider'       => $primary->name(),
            'mode'           => $primary->name() === 'mock' ? 'MOCK' : 'LIVE',
            'fallback'       => $this->fallback()?->name(),
            'prompt_version' => (string) config('bmh.ai.prompt_version'),
        ];
    }
}
