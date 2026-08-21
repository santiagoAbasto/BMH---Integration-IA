<?php

declare(strict_types=1);

namespace App\Domain\Orders\DTO;

/**
 * Una línea histórica de pedido.
 *
 * El historial es una SEÑAL, no la verdad actual: que el cliente haya comprado
 * algo no significa que siga vigente, ni que el precio sea el mismo. El precio
 * de acá es el de aquel momento y nunca se muestra como precio actual.
 *
 * @see docs/ai-architecture.md §"Historial ≠ verdad actual"
 */
final readonly class PurchasedLine implements \JsonSerializable
{
    public function __construct(
        public int $orderId,
        public int $productId,
        public string $productCode,
        public string $productName,
        public ?int $categoryId,
        public ?string $categoryName,
        public float $quantity,
        public float $historicUnitPrice,
        public float $historicDiscountedPrice,
        public ?string $orderDate,
        public int $timesPurchased = 1,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'order_id'        => $this->orderId,
            'product_id'      => $this->productId,
            'product_code'    => $this->productCode,
            'product_name'    => $this->productName,
            'category'        => $this->categoryName,
            'quantity'        => $this->quantity,
            'order_date'      => $this->orderDate,
            'times_purchased' => $this->timesPurchased,
            // Etiquetado explícito para que nadie lo confunda con el precio de hoy.
            'historic_price'  => [
                'unit'       => $this->historicUnitPrice,
                'discounted' => $this->historicDiscountedPrice,
                'note'       => 'precio_historico_no_vigente',
            ],
        ];
    }
}
