<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTO;

use App\Domain\Support\Provenance;

/**
 * Una referencia cruzada: o bien una equivalencia con un código de otro
 * fabricante, o bien la pieza relacionada que le corresponde a este producto
 * (la escobilla de este alternador, el colector de este rotor).
 *
 * Salen de dos lados de la base legacy y por eso llevan `kind` y provenance:
 *  - `producto_caracteristica` (EAV normalizado)
 *  - `productos.columna_N` con tipo cross_reference
 */
final readonly class CrossReference implements \JsonSerializable
{
    public const KIND_EQUIVALENCE = 'equivalence';
    public const KIND_PART        = 'related_part';

    public function __construct(
        public string $kind,
        public string $label,
        public string $code,
        public Provenance $provenance,
    ) {
    }

    /** La base usa "-" como "no aplica". No es un código. */
    public static function isPlaceholder(?string $value): bool
    {
        $value = trim((string) $value);

        return $value === '' || $value === '-' || $value === '--' || $value === 'N/A' || $value === '0';
    }

    public function jsonSerialize(): array
    {
        return [
            'kind'       => $this->kind,
            'label'      => $this->label,
            'code'       => $this->code,
            'provenance' => $this->provenance,
        ];
    }
}
