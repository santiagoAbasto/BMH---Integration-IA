<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Domain\Support\Provenance;

/**
 * Disponibilidad.
 *
 * `productos.stock` vale 1 en las 5.054 filas de la base. Un solo valor
 * distinto. No hay ninguna consulta en el sistema de producción que filtre por
 * ese campo, ni pantalla de admin que lo edite. La lectura más probable es que
 * sea el `DEFAULT 1` de la migración original que nadie mantuvo.
 *
 * Hasta que BMH confirme la semántica, este servicio devuelve `unknown` y el
 * asistente tiene PROHIBIDO afirmar que hay stock. Preferimos decir "lo
 * confirma un asesor" antes que prometer una pieza que no está.
 *
 * Para activarlo cuando se confirme: `BMH_STOCK_SEMANTICS_VERIFIED=true`.
 *
 * @see docs/database-discovery.md §5
 */
final class InventoryService
{
    public const AVAILABLE   = 'available';
    public const UNAVAILABLE = 'unavailable';
    public const UNKNOWN     = 'unknown';

    /**
     * @return array{availability:string, can_assert:bool, source:string, message:string}
     */
    public function availabilityFor(int $productId, int|string|null $rawStock = null): array
    {
        if (! config('bmh.inventory.semantics_verified')) {
            return [
                'availability' => self::UNKNOWN,
                'can_assert'   => false,
                'source'       => (string) config('bmh.inventory.unverified_source'),
                'message'      => 'La disponibilidad no se puede confirmar automáticamente. La verifica un asesor de BMH.',
                'provenance'   => Provenance::unverified('productos.stock tiene un único valor en toda la tabla'),
            ];
        }

        $stock = (int) $rawStock;

        return [
            'availability' => $stock > 0 ? self::AVAILABLE : self::UNAVAILABLE,
            'can_assert'   => true,
            'source'       => 'productos.stock',
            'message'      => $stock > 0 ? 'Disponible.' : 'Sin stock.',
            'provenance'   => Provenance::database('productos', $productId, 'stock'),
        ];
    }

    /** ¿Puede el asistente afirmar algo sobre disponibilidad? */
    public function canAssertAvailability(): bool
    {
        return (bool) config('bmh.inventory.semantics_verified');
    }
}
