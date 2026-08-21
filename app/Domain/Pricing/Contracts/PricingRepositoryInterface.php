<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Contracts;

interface PricingRepositoryInterface
{
    /** Porcentaje de IVA vigente (`impuestos`). */
    public function taxRate(): float;

    /** Descuento de producto (`productos.descuento`). */
    public function productDiscount(int $productId): float;

    /** Descuento/aumento de categoría (`categorias`). @return array{descuento:float, aumento:float} */
    public function categoryModifiers(int $categoryId): array;

    /** Escala de bonificación por volumen. @return list<array{desde:float, hasta:float, porcentaje:float}> */
    public function bonificationTiers(): array;

    public function listPrice(int $productId): ?float;
}
