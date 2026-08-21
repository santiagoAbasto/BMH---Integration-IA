<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Search\DTO\Candidate;

/**
 * La pregunta más inteligente.
 *
 * El objetivo NO es preguntar siempre marca/modelo/año. Es calcular, sobre los
 * candidatos que efectivamente hay, qué dato partiría mejor el conjunto.
 *
 * Se usa ganancia de información normalizada: para cada atributo, se mira cómo
 * se reparten los candidatos entre sus valores. Un atributo que deja 8
 * candidatos en un solo grupo no sirve; uno que los parte en 4 grupos de 2
 * reduce muchísimo. Un atributo que ni siquiera está cargado en todos los
 * candidatos se penaliza, porque preguntarlo puede no resolver nada.
 *
 * @see docs/catalog-search.md §"Disambiguation"
 */
final class CandidateDisambiguationService
{
    /**
     * @param  list<Candidate> $candidates
     * @return array{
     *     should_ask: bool,
     *     attribute: ?array{key:string,label:string,options:list<string>,gain:float},
     *     alternatives: list<array{key:string,label:string,gain:float}>,
     *     reason: string
     * }
     */
    public function nextQuestion(array $candidates): array
    {
        $presentThreshold = (int) config('bmh.disambiguation.present_threshold', 3);

        if (count($candidates) === 0) {
            return $this->result(false, null, [], 'no_candidates');
        }

        if (count($candidates) <= $presentThreshold) {
            // Pocos candidatos: mostrarlos es mejor que seguir preguntando.
            return $this->result(false, null, [], 'few_enough_to_present');
        }

        // Si el primero ya es una coincidencia muy alta, tampoco hay que preguntar.
        if ($candidates[0]->confidenceBand() === 'very_high') {
            return $this->result(false, null, [], 'high_confidence_match');
        }

        $ranked = $this->rankAttributesByGain($candidates);

        if ($ranked === []) {
            return $this->result(false, null, [], 'no_discriminating_attribute');
        }

        $best    = $ranked[0];
        $minGain = (float) config('bmh.disambiguation.min_information_gain', 0.15);

        if ($best['gain'] < $minGain) {
            return $this->result(false, null, [], 'gain_below_threshold');
        }

        return $this->result(
            true,
            [
                'key'     => $best['key'],
                'label'   => $best['label'],
                'options' => $best['options'],
                'gain'    => round($best['gain'], 4),
            ],
            array_map(
                static fn (array $a): array => [
                    'key'   => $a['key'],
                    'label' => $a['label'],
                    'gain'  => round($a['gain'], 4),
                ],
                array_slice($ranked, 1, 3)
            ),
            'discriminating_attribute_found'
        );
    }

    /**
     * @param  list<Candidate> $candidates
     * @return list<array{key:string,label:string,gain:float,options:list<string>,coverage:float}>
     */
    public function rankAttributesByGain(array $candidates): array
    {
        $total   = count($candidates);
        $neverAsk = array_map(
            static fn (string $s): string => mb_strtoupper($s),
            (array) config('bmh.disambiguation.never_ask', [])
        );

        /** @var array<string, array{label:string, values:array<string,int>, present:int}> $buckets */
        $buckets = [];

        foreach ($candidates as $candidate) {
            $product = $candidate->product;

            foreach ($product->attributes as $attribute) {
                if (in_array(mb_strtoupper($attribute->label), $neverAsk, true)) {
                    continue;
                }

                $key = $attribute->key;
                $buckets[$key]['label'] ??= $attribute->label;
                $buckets[$key]['present'] = ($buckets[$key]['present'] ?? 0) + 1;

                $value = mb_strtoupper(trim($attribute->displayValue()));
                $buckets[$key]['values'][$value] = ($buckets[$key]['values'][$value] ?? 0) + 1;
            }

            // Marca y modelo no son atributos del EAV pero sí datos que el
            // cliente suele saber, así que compiten en igualdad de condiciones.
            if ($product->brand !== null) {
                $buckets['brand']['label']   = 'Marca';
                $buckets['brand']['present'] = ($buckets['brand']['present'] ?? 0) + 1;
                $value                       = mb_strtoupper($product->brand);
                $buckets['brand']['values'][$value] = ($buckets['brand']['values'][$value] ?? 0) + 1;
            }
            if ($product->model !== null) {
                $buckets['model']['label']   = 'Modelo';
                $buckets['model']['present'] = ($buckets['model']['present'] ?? 0) + 1;
                $value                       = mb_strtoupper($product->model);
                $buckets['model']['values'][$value] = ($buckets['model']['values'][$value] ?? 0) + 1;
            }
        }

        $ranked = [];

        foreach ($buckets as $key => $bucket) {
            $values = $bucket['values'] ?? [];

            // Un solo valor distinto no parte nada.
            if (count($values) < 2) {
                continue;
            }

            $coverage = ($bucket['present'] ?? 0) / $total;
            $gain     = $this->normalizedEntropy($values, $total) * $coverage;

            $ranked[] = [
                'key'      => (string) $key,
                'label'    => (string) ($bucket['label'] ?? $key),
                'gain'     => $gain,
                'coverage' => $coverage,
                // Las opciones sirven para ofrecer quick-replies en la UI.
                'options'  => $this->topOptions($values),
            ];
        }

        usort($ranked, static fn (array $a, array $b): int => $b['gain'] <=> $a['gain']);

        return $ranked;
    }

    /**
     * Entropía de Shannon normalizada a 0..1.
     *
     * 1.0 = el atributo parte los candidatos en grupos perfectamente parejos
     * (máxima reducción esperada). 0.0 = todos caen en el mismo valor.
     *
     * @param array<string,int> $values
     */
    private function normalizedEntropy(array $values, int $total): float
    {
        if ($total <= 1 || count($values) < 2) {
            return 0.0;
        }

        $entropy = 0.0;

        foreach ($values as $count) {
            $p = $count / $total;
            if ($p > 0.0) {
                $entropy -= $p * log($p, 2);
            }
        }

        // El máximo posible con esta cantidad de valores distintos.
        $maxEntropy = log(min(count($values), $total), 2);

        return $maxEntropy > 0.0 ? $entropy / $maxEntropy : 0.0;
    }

    /** @param array<string,int> $values @return list<string> */
    private function topOptions(array $values): array
    {
        arsort($values);

        return array_slice(array_keys($values), 0, 6);
    }

    private function result(bool $shouldAsk, ?array $attribute, array $alternatives, string $reason): array
    {
        return [
            'should_ask'   => $shouldAsk,
            'attribute'    => $attribute,
            'alternatives' => $alternatives,
            'reason'       => $reason,
        ];
    }
}
