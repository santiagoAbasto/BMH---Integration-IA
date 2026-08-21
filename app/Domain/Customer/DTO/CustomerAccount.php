<?php

declare(strict_types=1);

namespace App\Domain\Customer\DTO;

/**
 * El cliente autenticado, visto por el dominio.
 *
 * Contiene PII (nombre, código) porque la aplicación la necesita para saludar
 * y para el handoff. Lo que se le manda al LLM es `toAiContext()`, que la
 * excluye deliberadamente.
 *
 * @see docs/security.md §"Data minimization"
 */
final readonly class CustomerAccount
{
    public function __construct(
        public int $id,
        public ?string $code,
        public string $displayName,
        public bool $enabled,
        public float $discountPercent,
        public ?float $resalePercent,
        public ?int $sellerId,
    ) {
    }

    /** Segmento comercial derivado, sin exponer el porcentaje exacto al modelo. */
    public function commercialSegment(): string
    {
        return match (true) {
            $this->discountPercent >= 15.0 => 'preferencial',
            $this->discountPercent >= 6.0  => 'mayorista',
            $this->discountPercent > 0.0   => 'con_acuerdo',
            default                        => 'lista',
        };
    }

    public function hasResale(): bool
    {
        return $this->resalePercent !== null && $this->resalePercent > 0;
    }

    /**
     * Lo ÚNICO que ve el proveedor de IA sobre el cliente.
     *
     * Sin nombre, sin DNI, sin email, sin teléfono, sin dirección, y sin el
     * porcentaje de descuento (el precio lo calcula el PricingEngine, el
     * modelo sólo lo comunica).
     */
    public function toAiContext(): array
    {
        return [
            'authenticated'      => true,
            'commercial_segment' => $this->commercialSegment(),
            'has_agreement'      => $this->discountPercent > 0.0,
            'resale_enabled'     => $this->hasResale(),
        ];
    }
}
