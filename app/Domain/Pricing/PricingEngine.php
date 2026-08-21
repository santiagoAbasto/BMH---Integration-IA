<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use App\Domain\Customer\DTO\CustomerAccount;
use App\Domain\Pricing\Contracts\PricingRepositoryInterface;
use App\Domain\Pricing\DTO\PriceQuote;

/**
 * El único lugar donde se calcula un precio.
 *
 * La fórmula NO se inventó: se reconstruyó leyendo el código de producción.
 * La referencia es `Producto::precio_unitario_descontado()`, que es el método
 * que el carrito realmente usa para armar el subtotal:
 *
 *     precio_neto = productos.precio
 *                 × (1 − productos.descuento/100)
 *                 × (1 − users.descuento/100)
 *
 * Y el IVA se suma después, sobre el subtotal (`Carrito::iva()`), porque
 * `productos.precio` está cargado SIN IVA.
 *
 * Lo que producción tiene cargado en la base pero NO aplica, y por lo tanto
 * acá tampoco (ver docs/pricing-rules.md):
 *
 *  - `categorias.aumento` / `categorias.descuento` → código comentado en
 *    `Producto::precio()`.
 *  - `productos.aumento` → 1.024 productos lo tienen en 10, tampoco se aplica.
 *  - `bonificaciones` → se muestra como escala en el carrito, pero
 *    `Carrito::total_format()` no la descuenta.
 *
 * Replicar producción es lo correcto: si el asesor cotizara distinto del
 * carrito, el cliente vería dos precios diferentes para la misma pieza.
 *
 * La IA nunca ejecuta esta cuenta. Recibe el PriceQuote resuelto.
 */
final class PricingEngine
{
    public function __construct(
        private readonly PricingRepositoryInterface $repository,
    ) {
    }

    /**
     * @param int|null $categoryId sólo se usa si se habilitan los modificadores
     *                             de categoría en config.
     */
    public function quote(int $productId, ?CustomerAccount $customer, ?int $categoryId = null): PriceQuote
    {
        $listPrice = $this->repository->listPrice($productId);

        if ($listPrice === null) {
            return PriceQuote::unavailable($productId, 'El producto no tiene precio cargado.');
        }

        // 24 productos tienen precio NULL y 2 tienen precio ≤ 1, que en esta
        // base es el default de la migración, no un precio real.
        if ($listPrice <= 1.0) {
            return new PriceQuote(
                productId: $productId,
                status: PriceQuote::STATUS_REQUIRES_VALIDATION,
                listPrice: $listPrice,
                customerDiscountPercent: 0.0,
                productDiscountPercent: 0.0,
                categoryDiscountPercent: 0.0,
                bonificationPercent: 0.0,
                taxPercent: $this->repository->taxRate(),
                netPrice: null,
                priceWithTax: null,
                resalePrice: null,
                currency: $this->currency(),
                calculationSource: 'verified_pricing_engine',
                notes: ['El precio de lista es el valor por defecto del esquema legacy, no un precio real.'],
            );
        }

        $productDiscount  = $this->repository->productDiscount($productId);
        $customerDiscount = $customer?->discountPercent ?? 0.0;
        $taxPercent       = $this->repository->taxRate();

        $categoryDiscount = 0.0;
        $categoryAumento  = 0.0;
        $notes            = [];

        if (config('bmh.pricing.apply_category_modifiers') && $categoryId !== null) {
            $modifiers        = $this->repository->categoryModifiers($categoryId);
            $categoryDiscount = $modifiers['descuento'];
            $categoryAumento  = $modifiers['aumento'];
        }

        $net = $listPrice;

        if ($categoryAumento > 0.0) {
            $net *= 1 + ($categoryAumento / 100);
        }
        if ($categoryDiscount > 0.0) {
            $net *= 1 - ($categoryDiscount / 100);
        }

        $net *= 1 - ($productDiscount / 100);
        $net *= 1 - ($customerDiscount / 100);

        // Bonificación por volumen: depende del subtotal del carrito, no de una
        // línea suelta. Se informa como escala, nunca se aplica a una cotización
        // unitaria.
        $bonification = 0.0;
        if (config('bmh.pricing.apply_bonificacion')) {
            $notes[] = 'La bonificación por volumen depende del total del pedido y no está aplicada en este precio unitario.';
        }

        $withTax = $net * (1 + ($taxPercent / 100));

        $resale = null;
        if ($customer !== null && $customer->hasResale()) {
            $resale = $net * (1 + ($customer->resalePercent / 100));
        }

        return new PriceQuote(
            productId: $productId,
            status: PriceQuote::STATUS_VERIFIED,
            listPrice: $listPrice,
            customerDiscountPercent: $customerDiscount,
            productDiscountPercent: $productDiscount,
            categoryDiscountPercent: $categoryDiscount,
            bonificationPercent: $bonification,
            taxPercent: $taxPercent,
            netPrice: round($net, 2),
            priceWithTax: round($withTax, 2),
            resalePrice: $resale === null ? null : round($resale, 2),
            currency: $this->currency(),
            calculationSource: 'verified_pricing_engine',
            notes: $notes,
        );
    }

    /**
     * Escala de bonificación por volumen, para mostrarla como información.
     *
     * @return list<array{desde:float, hasta:float, porcentaje:float}>
     */
    public function bonificationScale(): array
    {
        return $this->repository->bonificationTiers();
    }

    /** Qué bonificación correspondería a un subtotal dado. Informativo. */
    public function bonificationFor(float $subtotal): float
    {
        foreach ($this->repository->bonificationTiers() as $tier) {
            if ($subtotal >= $tier['desde'] && $subtotal <= $tier['hasta']) {
                return $tier['porcentaje'];
            }
        }

        return 0.0;
    }

    private function currency(): string
    {
        return (string) config('bmh.pricing.currency', 'ARS');
    }
}
