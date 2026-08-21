<?php

declare(strict_types=1);

namespace App\Services\Ai\DTO;

/**
 * Respuesta del proveedor.
 *
 * Deliberadamente NO existe un campo para chain-of-thought: no se pide, no se
 * recibe y no se persiste. Ver docs/security.md.
 */
final readonly class AiResponse
{
    /** @param list<AiToolCall> $toolCalls */
    public function __construct(
        public string $text,
        public array $toolCalls = [],
        public ?AiUsage $usage = null,
        public string $provider = 'unknown',
        public string $model = 'unknown',
        public float $latencyMs = 0.0,
        public bool $failed = false,
        public ?string $error = null,
    ) {
    }

    public static function failure(string $error, string $provider = 'unknown'): self
    {
        return new self('', [], null, $provider, 'unknown', 0.0, true, $error);
    }

    public function wantsTools(): bool
    {
        return $this->toolCalls !== [];
    }
}
