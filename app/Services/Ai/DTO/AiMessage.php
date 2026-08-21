<?php

declare(strict_types=1);

namespace App\Services\Ai\DTO;

/**
 * Un mensaje en el hilo que se le manda al proveedor.
 *
 * `role` distingue explícitamente el contenido del sistema, el del usuario, el
 * del asistente y el resultado de una tool. Esa separación es la primera
 * defensa contra prompt injection: lo que escribe el cliente entra SIEMPRE
 * como `user`, nunca como `system`.
 *
 * @see docs/security.md §"Prompt injection"
 */
final readonly class AiMessage implements \JsonSerializable
{
    public const ROLE_SYSTEM    = 'system';
    public const ROLE_USER      = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_TOOL      = 'tool';

    /** @param list<string> $imagePaths rutas absolutas a imágenes optimizadas */
    public function __construct(
        public string $role,
        public string $content,
        public array $imagePaths = [],
        public ?string $toolCallId = null,
        public ?string $toolName = null,
    ) {
    }

    public static function system(string $content): self
    {
        return new self(self::ROLE_SYSTEM, $content);
    }

    /** @param list<string> $imagePaths */
    public static function user(string $content, array $imagePaths = []): self
    {
        return new self(self::ROLE_USER, $content, $imagePaths);
    }

    public static function assistant(string $content): self
    {
        return new self(self::ROLE_ASSISTANT, $content);
    }

    /** Resultado de una tool ejecutada por Laravel. Es un DATO, no una orden. */
    public static function toolResult(string $toolName, string $callId, array $payload): self
    {
        return new self(
            self::ROLE_TOOL,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            [],
            $callId,
            $toolName,
        );
    }

    public function hasImages(): bool
    {
        return $this->imagePaths !== [];
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'role'         => $this->role,
            'content'      => $this->content,
            'tool_call_id' => $this->toolCallId,
            'tool_name'    => $this->toolName,
            'images'       => count($this->imagePaths) ?: null,
        ], static fn ($v) => $v !== null);
    }
}
