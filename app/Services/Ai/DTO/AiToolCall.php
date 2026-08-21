<?php

declare(strict_types=1);

namespace App\Services\Ai\DTO;

/**
 * Una tool que el modelo pidió ejecutar.
 *
 * El modelo sólo puede NOMBRAR una tool y pasarle argumentos. No puede escribir
 * SQL, no puede elegir tablas y no puede ampliar el alcance: Laravel valida el
 * nombre contra el registro y los argumentos contra un FormRequest antes de
 * tocar la base.
 */
final readonly class AiToolCall
{
    /** @param array<string, mixed> $arguments */
    public function __construct(
        public string $id,
        public string $name,
        public array $arguments,
    ) {
    }
}
