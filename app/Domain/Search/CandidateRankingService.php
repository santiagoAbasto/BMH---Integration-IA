<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Catalog\CatalogSemanticLayer;
use App\Domain\Catalog\DTO\CrossReference;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Search\DTO\Candidate;
use App\Domain\Search\DTO\SearchQuery;

/**
 * Ranking multifactor.
 *
 * Regla dura, no negociable: **la similitud visual nunca le gana a un código
 * exacto**. El peso de `exact_code` (100) está un orden de magnitud por encima
 * de `vision_similarity` (3) justamente para que ninguna combinación de señales
 * blandas pueda dar vuelta un match duro.
 *
 * Los pesos viven en `config/bmh.php`, no acá.
 */
final class CandidateRankingService
{
    /**
     * @param  list<ProductView> $products
     * @return list<Candidate>
     */
    public function rank(array $products, SearchQuery $query): array
    {
        $weights    = (array) config('bmh.ranking.weights');
        $candidates = [];

        foreach ($products as $product) {
            $candidate = new Candidate($product);

            $this->scoreCode($candidate, $query, $weights);
            $this->scoreEquivalence($candidate, $query, $weights);
            $this->scoreBrandModel($candidate, $query, $weights);
            $this->scoreCategory($candidate, $query, $weights);
            $this->scoreAttributes($candidate, $query, $weights);
            $this->scoreText($candidate, $query, $weights);
            $this->scoreHistory($candidate, $query, $weights);

            $candidates[] = $candidate;
        }

        usort($candidates, static fn (Candidate $a, Candidate $b): int => $b->score <=> $a->score);

        return array_slice($candidates, 0, (int) config('bmh.ranking.max_candidates', 24));
    }

    private function scoreCode(Candidate $candidate, SearchQuery $query, array $weights): void
    {
        $needle = $query->code ?? ($query->rawText ?? '');
        $needle = trim($needle);

        if ($needle === '') {
            return;
        }

        $productCode = trim($candidate->product->code);

        if (strcasecmp($productCode, $needle) === 0) {
            $candidate->addSignal('exact_code', (float) $weights['exact_code']);
            $candidate->matchedOn[] = 'código exacto';

            return;
        }

        $normalizedNeedle  = CatalogSemanticLayer::normalizeCode($needle);
        $normalizedProduct = CatalogSemanticLayer::normalizeCode($productCode);

        if ($normalizedNeedle !== '' && $normalizedNeedle === $normalizedProduct) {
            $candidate->addSignal('normalized_code', (float) $weights['normalized_code']);
            $candidate->matchedOn[] = 'código';

            return;
        }

        if (mb_strlen($normalizedNeedle) >= 3 && str_contains($normalizedProduct, $normalizedNeedle)) {
            // Cuanto mayor la porción del código cubierta, más fuerte la señal.
            $strength = mb_strlen($normalizedNeedle) / max(1, mb_strlen($normalizedProduct));
            $candidate->addSignal('partial_code', (float) $weights['partial_code'], $strength);
            $candidate->matchedOn[] = 'código parcial';
        }
    }

    private function scoreEquivalence(Candidate $candidate, SearchQuery $query, array $weights): void
    {
        $needle = CatalogSemanticLayer::normalizeCode($query->code ?? ($query->rawText ?? ''));

        if (mb_strlen($needle) < 3) {
            return;
        }

        foreach ($candidate->product->equivalences as $reference) {
            if (CatalogSemanticLayer::normalizeCode($reference->code) === $needle) {
                $candidate->addSignal('equivalence', (float) $weights['equivalence']);
                $candidate->matchedOn[] = 'equivalencia ' . $reference->label;

                return;
            }
        }
    }

    private function scoreBrandModel(Candidate $candidate, SearchQuery $query, array $weights): void
    {
        if ($query->brand !== null && $candidate->product->brand !== null) {
            if ($this->softMatch($query->brand, $candidate->product->brand)) {
                $candidate->addSignal('brand_match', (float) $weights['brand_match']);
                $candidate->matchedOn[] = 'marca';
            }
        }

        if ($query->model !== null && $candidate->product->model !== null) {
            if ($this->softMatch($query->model, $candidate->product->model)) {
                $candidate->addSignal('model_match', (float) $weights['model_match']);
                $candidate->matchedOn[] = 'modelo';
            }
        }
    }

    private function scoreCategory(Candidate $candidate, SearchQuery $query, array $weights): void
    {
        if ($query->categoryIds === [] || $candidate->product->category === null) {
            return;
        }

        if (in_array($candidate->product->category->id, $query->categoryIds, true)) {
            $candidate->addSignal('category_match', (float) $weights['category_match']);
        }
    }

    private function scoreAttributes(Candidate $candidate, SearchQuery $query, array $weights): void
    {
        if ($query->attributes === []) {
            return;
        }

        $matched = 0;

        foreach ($query->attributes as $key => $expected) {
            $attribute = $candidate->product->attribute((string) $key);

            if ($attribute === null) {
                continue;
            }

            if ($this->attributeMatches($attribute->value, (string) $expected)) {
                $matched++;
                $candidate->matchedOn[] = $attribute->label;
            }
        }

        if ($matched > 0) {
            // Se pondera por proporción: matchear 3 de 3 vale más que 1 de 3.
            $strength = $matched / max(1, count($query->attributes));
            $candidate->addSignal('attribute_match', (float) $weights['attribute_match'] * $matched, $strength);
        }

        // Si la búsqueda vino de una foto, se acredita aparte y con poco peso.
        if ($query->fromVision && $matched > 0) {
            $candidate->addSignal('vision_similarity', (float) $weights['vision_similarity']);
        }
    }

    private function scoreText(Candidate $candidate, SearchQuery $query, array $weights): void
    {
        if (! $query->hasText()) {
            return;
        }

        $haystack = mb_strtolower(
            $candidate->product->name . ' '
            . ($candidate->product->brand ?? '') . ' '
            . ($candidate->product->model ?? '')
        );

        $terms   = $this->terms((string) $query->rawText);
        $matched = 0;

        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                $matched++;
            }
        }

        if ($matched > 0 && $terms !== []) {
            $candidate->addSignal('text_similarity', (float) $weights['text_similarity'], $matched / count($terms));
        }
    }

    private function scoreHistory(Candidate $candidate, SearchQuery $query, array $weights): void
    {
        if ($query->customerProductIds === []) {
            return;
        }

        if (in_array($candidate->product->id, $query->customerProductIds, true)) {
            $candidate->addSignal('customer_history', (float) $weights['customer_history']);
            $candidate->matchedOn[] = 'comprado antes';
        }
    }

    /**
     * Comparación numérica tolerante para dimensiones ("88.8" ≈ "88,8" ≈ "88.8 mm"),
     * textual para el resto.
     */
    private function attributeMatches(string $actual, string $expected): bool
    {
        $actualNumeric   = $this->numeric($actual);
        $expectedNumeric = $this->numeric($expected);

        if ($actualNumeric !== null && $expectedNumeric !== null) {
            return abs($actualNumeric - $expectedNumeric) <= 1.0;
        }

        return $this->softMatch($expected, $actual);
    }

    private function softMatch(string $needle, string $haystack): bool
    {
        $needle   = $this->fold($needle);
        $haystack = $this->fold($haystack);

        if ($needle === '' || $haystack === '') {
            return false;
        }

        return str_contains($haystack, $needle) || str_contains($needle, $haystack);
    }

    private function numeric(string $value): ?float
    {
        if (! preg_match('/-?\d+(?:[.,]\d+)?/', $value, $m)) {
            return null;
        }

        return (float) str_replace(',', '.', $m[0]);
    }

    /** @return list<string> */
    private function terms(string $text): array
    {
        $parts = preg_split('/[\s,]+/u', $this->fold($text)) ?: [];

        return array_values(array_filter($parts, static fn (string $p): bool => mb_strlen($p) >= 3));
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    }
}
