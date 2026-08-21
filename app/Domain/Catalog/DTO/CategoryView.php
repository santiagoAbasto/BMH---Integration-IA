<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTO;

final readonly class CategoryView implements \JsonSerializable
{
    /** @param list<int> $attributeSlots slots con etiqueta en esta categoría */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $alias,
        public array $attributeSlots = [],
        public int $productCount = 0,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'alias'         => $this->alias,
            'product_count' => $this->productCount,
        ];
    }
}
