<?php

declare(strict_types=1);

namespace App\Domain\Search\DTO;

use App\Domain\Catalog\DTO\ProductView;

/**
 * Un producto candidato con su score y el detalle de por qué puntuó así.
 *
 * El desglose (`signals`) no es decorativo: es lo que se muestra en el AI debug
 * mode y lo que permite explicar por qué un código exacto le ganó a una
 * coincidencia visual.
 */
final class Candidate
{
    /** @param array<string, float> $signals señal => aporte al score */
    public function __construct(
        public readonly ProductView $product,
        public float $score = 0.0,
        public array $signals = [],
        public array $matchedOn = [],
    ) {
    }

    public function addSignal(string $signal, float $weight, float $strength = 1.0): void
    {
        $contribution = $weight * $strength;

        $this->signals[$signal] = ($this->signals[$signal] ?? 0.0) + $contribution;
        $this->score += $contribution;
    }

    /**
     * Confianza normalizada 0..1.
     *
     * El score crudo no tiene techo natural, así que se lo compara con el peso
     * de un código exacto: si hubo match exacto de código, la confianza es
     * máxima; si no, se escala sobre lo que aportaron el resto de las señales.
     */
    public function confidence(): float
    {
        $exactWeight = (float) config('bmh.ranking.weights.exact_code', 100.0);

        if (isset($this->signals['exact_code'])) {
            return min(0.99, 0.90 + min(0.09, $this->score / ($exactWeight * 4)));
        }

        if (isset($this->signals['normalized_code']) || isset($this->signals['equivalence'])) {
            return min(0.89, 0.72 + min(0.17, $this->score / ($exactWeight * 2)));
        }

        // Sin código, el techo es deliberadamente bajo: la similitud por
        // atributos o por imagen no alcanza para afirmar una pieza.
        return min(0.74, $this->score / $exactWeight);
    }

    public function confidenceBand(): string
    {
        $confidence = $this->confidence();

        return match (true) {
            $confidence >= (float) config('bmh.confidence.very_high') => 'very_high',
            $confidence >= (float) config('bmh.confidence.high')      => 'high',
            $confidence >= (float) config('bmh.confidence.ambiguous') => 'ambiguous',
            default                                                   => 'low',
        };
    }

    public function confidenceLabel(): string
    {
        return (string) config('bmh.confidence.labels.' . $this->confidenceBand());
    }

    /** Versión corta, para el badge de la card. */
    public function confidenceShortLabel(): string
    {
        return (string) config('bmh.confidence.short_labels.' . $this->confidenceBand());
    }

    public function toArray(bool $withDebug = false): array
    {
        $payload = [
            'product'          => $this->product,
            'confidence'       => round($this->confidence(), 4),
            'confidence_band'  => $this->confidenceBand(),
            'confidence_label' => $this->confidenceLabel(),
            'confidence_short' => $this->confidenceShortLabel(),
            'matched_on'       => $this->matchedOn,
        ];

        if ($withDebug) {
            $payload['debug'] = [
                'score'   => round($this->score, 3),
                'signals' => array_map(static fn (float $v): float => round($v, 3), $this->signals),
            ];
        }

        return $payload;
    }
}
