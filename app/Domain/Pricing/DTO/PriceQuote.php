<?php

declare(strict_types=1);

namespace App\Domain\Pricing\DTO;

/**
 * El resultado del PricingEngine.
 *
 * La IA NUNCA calcula un precio. Recibe este objeto ya resuelto y sólo lo
 * comunica. Si `status` no es `verified`, el asistente tiene prohibido dar un
 * número como definitivo.
 */
final readonly class PriceQuote implements \JsonSerializable
{
    public const STATUS_VERIFIED            = 'verified';
    public const STATUS_REQUIRES_VALIDATION = 'requires_validation';
    public const STATUS_UNAVAILABLE         = 'unavailable';

    /** @param list<string> $notes */
    public function __construct(
        public int $productId,
        public string $status,
        public ?float $listPrice,
        public float $customerDiscountPercent,
        public float $productDiscountPercent,
        public float $categoryDiscountPercent,
        public float $bonificationPercent,
        public float $taxPercent,
        public ?float $netPrice,
        public ?float $priceWithTax,
        public ?float $resalePrice,
        public string $currency,
        public string $calculationSource,
        public array $notes = [],
    ) {
    }

    public static function unavailable(int $productId, string $reason): self
    {
        return new self(
            productId: $productId,
            status: self::STATUS_UNAVAILABLE,
            listPrice: null,
            customerDiscountPercent: 0.0,
            productDiscountPercent: 0.0,
            categoryDiscountPercent: 0.0,
            bonificationPercent: 0.0,
            taxPercent: 0.0,
            netPrice: null,
            priceWithTax: null,
            resalePrice: null,
            currency: (string) config('bmh.pricing.currency', 'ARS'),
            calculationSource: 'pricing_engine',
            notes: [$reason],
        );
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED && $this->netPrice !== null;
    }

    /** Lo que se le pasa a la IA: el número ya resuelto y su estado. */
    public function forAiTool(): array
    {
        if (! $this->isVerified()) {
            return [
                'status'  => $this->status,
                'message' => 'El precio no pudo confirmarse. No lo comuniques como definitivo; ofrecé que lo confirme un asesor.',
                'notes'   => $this->notes,
            ];
        }

        return [
            'status'        => $this->status,
            'net_price'     => round($this->netPrice, 2),
            'price_with_tax'=> $this->priceWithTax === null ? null : round($this->priceWithTax, 2),
            'currency'      => $this->currency,
            'tax_percent'   => $this->taxPercent,
            'tax_included'  => false,
            'source'        => $this->calculationSource,
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'product_id'          => $this->productId,
            'status'              => $this->status,
            'list_price'          => $this->listPrice,
            'customer_discount'   => $this->customerDiscountPercent,
            'product_discount'    => $this->productDiscountPercent,
            'category_discount'   => $this->categoryDiscountPercent,
            'bonification'        => $this->bonificationPercent,
            'tax'                 => $this->taxPercent,
            'net_price'           => $this->netPrice,
            'price_with_tax'      => $this->priceWithTax,
            'resale_price'        => $this->resalePrice,
            'currency'            => $this->currency,
            'calculation_source'  => $this->calculationSource,
            'notes'               => $this->notes,
        ];
    }
}
