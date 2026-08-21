<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTO;

/**
 * `productos.estado`.
 *
 * Verificado contra ProductoController::mapEstado() y las vistas del sitio
 * actual: 1 = NUEVO, 2 = RECONSTRUIDO, 0 = oculto. No tiene nada que ver con
 * disponibilidad ni con stock.
 */
enum ProductCondition: string
{
    case New         = 'new';
    case Rebuilt     = 'rebuilt';
    case Hidden      = 'hidden';
    case Unspecified = 'unspecified';

    public static function fromLegacy(int|string|null $estado): self
    {
        return match ((int) $estado) {
            1       => self::New,
            2       => self::Rebuilt,
            0       => self::Hidden,
            default => self::Unspecified,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::New         => 'Nuevo',
            self::Rebuilt     => 'Reconstruido',
            self::Hidden      => 'No publicado',
            self::Unspecified => 'Sin especificar',
        };
    }

    /** Los ocultos no se le ofrecen al cliente. */
    public function isPublic(): bool
    {
        return $this === self::New || $this === self::Rebuilt;
    }
}
