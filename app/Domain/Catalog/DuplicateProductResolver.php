<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\DTO\AttributeValue;
use App\Domain\Catalog\DTO\ProductView;

/**
 * Resuelve colisiones de código.
 *
 * `productos.codigo` NO es único: hay 11 códigos repartidos en 22 productos.
 * Seis de esas colisiones son entre PLAQUETA RECTIFICADORA y REGULADOR DE
 * VOLTAJE, o sea que son piezas realmente distintas con el mismo código, no
 * duplicados accidentales.
 *
 * Por eso la regla es: ante una colisión NO se elige el primero. Se calcula
 * qué distingue a los candidatos y se pregunta.
 *
 * @see docs/data-quality-report.md §"Códigos duplicados"
 */
final class DuplicateProductResolver
{
    /**
     * @param  list<ProductView> $candidates productos que comparten código
     * @return array{
     *     resolved: ?ProductView,
     *     ambiguous: bool,
     *     reason: string,
     *     distinguishing: list<array{key:string,label:string,values:array<int,string>}>
     * }
     */
    public function resolve(array $candidates): array
    {
        if ($candidates === []) {
            return $this->result(null, false, 'no_candidates', []);
        }

        if (count($candidates) === 1) {
            return $this->result($candidates[0], false, 'unique', []);
        }

        // Si sólo uno es publicable, no hay ambigüedad real para el cliente.
        $public = array_values(array_filter(
            $candidates,
            static fn (ProductView $p): bool => $p->condition->isPublic()
        ));

        if (count($public) === 1) {
            return $this->result($public[0], false, 'single_public_product', []);
        }

        $pool = $public !== [] ? $public : $candidates;

        if (count($pool) === 1) {
            return $this->result($pool[0], false, 'single_public_product', []);
        }

        $distinguishing = $this->distinguishingAttributes($pool);

        // Categorías distintas es la diferencia más fuerte y la más fácil de
        // preguntar: "¿es la plaqueta o el regulador?".
        $categories = array_unique(array_map(
            static fn (ProductView $p): ?string => $p->category?->name,
            $pool
        ));

        if (count($categories) > 1) {
            array_unshift($distinguishing, [
                'key'    => 'category',
                'label'  => 'Rubro',
                'values' => array_reduce(
                    $pool,
                    static function (array $carry, ProductView $p): array {
                        $carry[$p->id] = $p->category?->name ?? '-';

                        return $carry;
                    },
                    []
                ),
            ]);
        }

        return $this->result(null, true, 'duplicate_code_requires_disambiguation', $distinguishing);
    }

    /**
     * Atributos en los que los candidatos difieren. Sólo estos sirven para
     * desambiguar: preguntar por algo en lo que coinciden no reduce nada.
     *
     * @param  list<ProductView> $candidates
     * @return list<array{key:string,label:string,values:array<int,string>}>
     */
    public function distinguishingAttributes(array $candidates): array
    {
        $byKey = [];

        foreach ($candidates as $product) {
            foreach ($product->attributes as $attribute) {
                $byKey[$attribute->key]['label']              = $attribute->label;
                $byKey[$attribute->key]['values'][$product->id] = $attribute->displayValue();
            }
        }

        // Marca y modelo viven fuera del EAV pero también distinguen.
        foreach ($candidates as $product) {
            if ($product->brand !== null) {
                $byKey['brand']['label']               = 'Marca';
                $byKey['brand']['values'][$product->id] = $product->brand;
            }
            if ($product->model !== null) {
                $byKey['model']['label']               = 'Modelo';
                $byKey['model']['values'][$product->id] = $product->model;
            }
        }

        $distinguishing = [];

        foreach ($byKey as $key => $data) {
            $values = $data['values'] ?? [];

            // Tiene que estar cargado en todos y no ser el mismo valor.
            if (count($values) !== count($candidates)) {
                continue;
            }
            if (count(array_unique($values)) < 2) {
                continue;
            }

            $distinguishing[] = [
                'key'    => (string) $key,
                'label'  => (string) ($data['label'] ?? $key),
                'values' => $values,
            ];
        }

        return $distinguishing;
    }

    private function result(?ProductView $resolved, bool $ambiguous, string $reason, array $distinguishing): array
    {
        return [
            'resolved'       => $resolved,
            'ambiguous'      => $ambiguous,
            'reason'         => $reason,
            'distinguishing' => $distinguishing,
        ];
    }
}
