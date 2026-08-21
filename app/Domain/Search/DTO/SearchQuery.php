<?php

declare(strict_types=1);

namespace App\Domain\Search\DTO;

/**
 * Lo que se sabe de la búsqueda en este momento de la conversación.
 *
 * Se va llenando turno a turno: el cliente manda una foto, después dice
 * "Bosch", después tira un número. Cada dato nuevo refina la misma query.
 */
final class SearchQuery
{
    /**
     * @param list<int>             $categoryIds
     * @param array<string, string> $attributes  clave canónica => valor
     * @param list<int>             $customerProductIds productos que el cliente ya compró
     */
    public function __construct(
        public ?string $rawText = null,
        public ?string $code = null,
        public ?string $brand = null,
        public ?string $model = null,
        public array $categoryIds = [],
        public array $attributes = [],
        public array $customerProductIds = [],
        public bool $fromVision = false,
        public int $limit = 24,
    ) {
    }

    public function hasCode(): bool
    {
        return $this->code !== null && trim($this->code) !== '';
    }

    public function hasStructuredFilters(): bool
    {
        return $this->attributes !== [] || $this->brand !== null || $this->model !== null;
    }

    public function hasText(): bool
    {
        return $this->rawText !== null && trim($this->rawText) !== '';
    }

    public function isEmpty(): bool
    {
        return ! $this->hasCode() && ! $this->hasText() && ! $this->hasStructuredFilters()
            && $this->categoryIds === [];
    }

    /** Fusiona datos nuevos sin pisar lo que ya estaba confirmado. */
    public function merge(self $other): self
    {
        $merged = clone $this;

        $merged->rawText     = $other->rawText     ?? $merged->rawText;
        $merged->code        = $other->code        ?? $merged->code;
        $merged->brand       = $other->brand       ?? $merged->brand;
        $merged->model       = $other->model       ?? $merged->model;
        $merged->fromVision  = $merged->fromVision || $other->fromVision;
        $merged->attributes  = [...$merged->attributes, ...$other->attributes];

        if ($other->categoryIds !== []) {
            $merged->categoryIds = $other->categoryIds;
        }
        if ($other->customerProductIds !== []) {
            $merged->customerProductIds = $other->customerProductIds;
        }

        return $merged;
    }

    public function toArray(): array
    {
        return array_filter([
            'text'        => $this->rawText,
            'code'        => $this->code,
            'brand'       => $this->brand,
            'model'       => $this->model,
            'categories'  => $this->categoryIds,
            'attributes'  => $this->attributes,
            'from_vision' => $this->fromVision,
        ], static fn ($v) => $v !== null && $v !== [] && $v !== false);
    }
}
