<?php

declare(strict_types=1);

namespace App\Services\Ai\DTO;

/** Consumo de una llamada, para control de costos y observabilidad. */
final readonly class AiUsage
{
    public function __construct(
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public int $imagesAnalyzed = 0,
        public ?float $estimatedCostUsd = null,
    ) {
    }

    public function total(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    public function plus(?self $other): self
    {
        if ($other === null) {
            return $this;
        }

        return new self(
            $this->inputTokens + $other->inputTokens,
            $this->outputTokens + $other->outputTokens,
            $this->imagesAnalyzed + $other->imagesAnalyzed,
            $this->estimatedCostUsd === null && $other->estimatedCostUsd === null
                ? null
                : ($this->estimatedCostUsd ?? 0.0) + ($other->estimatedCostUsd ?? 0.0),
        );
    }

    public function toArray(): array
    {
        return [
            'input_tokens'    => $this->inputTokens,
            'output_tokens'   => $this->outputTokens,
            'images_analyzed' => $this->imagesAnalyzed,
            'estimated_cost'  => $this->estimatedCostUsd,
        ];
    }
}
