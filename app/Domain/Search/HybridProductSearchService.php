<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Search\DTO\Candidate;
use App\Domain\Search\DTO\SearchQuery;

/**
 * Búsqueda híbrida sobre el catálogo.
 *
 * Combina, en este orden de autoridad:
 *   1. código exacto
 *   2. código normalizado (sin espacios/guiones)
 *   3. equivalencias declaradas
 *   4. código parcial
 *   5. filtros estructurados por atributo
 *   6. palabras clave sobre nombre/marca/modelo
 *   7. historial del cliente
 *
 * El resultado se une y se rankea una sola vez: una pieza que aparece por
 * varias vías acumula señales, que es exactamente lo que queremos.
 *
 * No hay Elasticsearch ni vector store. Con 5.054 productos, MySQL con las
 * consultas bien armadas responde en decenas de milisegundos. Ver
 * docs/catalog-search.md §"Por qué no un vector store".
 */
final class HybridProductSearchService
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly CandidateRankingService $ranking,
        private readonly QueryRouter $router,
    ) {
    }

    /** @return list<Candidate> */
    public function search(SearchQuery $query, bool $hasImage = false): array
    {
        if ($query->isEmpty()) {
            return [];
        }

        $strategy = $this->router->route($query, $hasImage);
        $products = $this->collect($query, $strategy);

        // Los productos ocultos (estado = 0) no se le ofrecen al cliente.
        $products = array_values(array_filter(
            $products,
            static fn (ProductView $p): bool => $p->condition->isPublic()
        ));

        // Un match exacto de código no se filtra por atributos: el código manda.
        if (! ($strategy === QueryRouter::EXACT && $products !== [])) {
            $products = $this->discardContradictions($products, $query);
        }

        return $this->ranking->rank($products, $query);
    }

    public function strategyFor(SearchQuery $query, bool $hasImage = false): string
    {
        return $this->router->route($query, $hasImage);
    }

    /**
     * Descarta lo que CONTRADICE un dato que ya tenemos.
     *
     * El pool se arma como unión de varias estrategias, así que sin este paso
     * agregar información no reduce nada y el cliente no ve progreso: contesta
     * "largo total 152" y le siguen apareciendo 24 opciones.
     *
     * La regla es asimétrica a propósito:
     *
     *  - si el producto tiene el atributo y NO coincide → se descarta;
     *  - si el producto NO tiene el atributo cargado → se conserva.
     *
     * Lo segundo importa: 518 productos no tienen marca y muchos tienen
     * atributos vacíos. Descartar por dato faltante haría que la respuesta
     * correcta desaparezca por un agujero del catálogo, que es peor que
     * mostrarla un poco más abajo en el ranking.
     *
     * @param  list<ProductView> $products
     * @return list<ProductView>
     */
    private function discardContradictions(array $products, SearchQuery $query): array
    {
        if ($query->attributes === [] && $query->brand === null) {
            return $products;
        }

        $filtered = array_values(array_filter($products, function (ProductView $product) use ($query): bool {
            if ($query->brand !== null && $product->brand !== null
                && ! $this->loosely($query->brand, $product->brand)) {
                return false;
            }

            foreach ($query->attributes as $key => $expected) {
                $attribute = $product->attribute((string) $key);

                if ($attribute === null) {
                    continue; // dato faltante: no es contradicción
                }

                if (! $this->valuesAgree($attribute->value, (string) $expected)) {
                    return false;
                }
            }

            return true;
        }));

        // Si los filtros dejaron el conjunto vacío, algo está mal en los datos o
        // en lo que entendimos. Es preferible mostrar el pool sin filtrar y
        // dejar que el ranking ordene, antes que decirle al cliente que no
        // existe nada.
        return $filtered === [] ? $products : $filtered;
    }

    /** Comparación tolerante: "88.8", "88,8" y "88.8 mm" son el mismo valor. */
    private function valuesAgree(string $actual, string $expected): bool
    {
        $actualNumber   = $this->numeric($actual);
        $expectedNumber = $this->numeric($expected);

        if ($actualNumber !== null && $expectedNumber !== null) {
            return abs($actualNumber - $expectedNumber) <= 1.0;
        }

        return $this->loosely($expected, $actual);
    }

    private function loosely(string $needle, string $haystack): bool
    {
        $needle   = mb_strtolower(trim($needle));
        $haystack = mb_strtolower(trim($haystack));

        return $needle === '' || str_contains($haystack, $needle) || str_contains($needle, $haystack);
    }

    private function numeric(string $value): ?float
    {
        if (! preg_match('/-?\d+(?:[.,]\d+)?/', $value, $m)) {
            return null;
        }

        return (float) str_replace(',', '.', $m[0]);
    }

    /**
     * Ejecuta las estrategias que correspondan y devuelve la unión sin repetir.
     *
     * @return list<ProductView>
     */
    private function collect(SearchQuery $query, string $strategy): array
    {
        /** @var array<int, ProductView> $pool */
        $pool = [];

        $add = static function (array $products) use (&$pool): void {
            foreach ($products as $product) {
                $pool[$product->id] ??= $product;
            }
        };

        // Un código puede venir explícito o embebido en la frase.
        $code = $query->code;
        if ($code === null && $query->hasText()) {
            $code = $this->router->extractCode((string) $query->rawText);
        }

        if ($code !== null && trim($code) !== '') {
            $add($this->catalog->findByCode($code));
            $add($this->catalog->findByNormalizedCode($code));
            $add($this->catalog->findByEquivalence($code));

            // Si el código exacto ya resolvió, no hace falta ensuciar el pool
            // con coincidencias parciales.
            if ($pool === []) {
                $add($this->catalog->searchByPartialCode($code));
            }
        }

        if ($strategy === QueryRouter::EXACT && $pool !== []) {
            return array_values($pool);
        }

        if ($query->hasStructuredFilters()) {
            $attributes = $query->attributes;

            if ($query->brand !== null) {
                $add($this->catalog->searchByKeywords($query->brand, $query->categoryIds));
            }

            if ($attributes !== []) {
                $add($this->catalog->searchByAttributes($attributes, $query->categoryIds));
            }
        }

        if ($query->hasText()) {
            $add($this->catalog->searchByKeywords((string) $query->rawText, $query->categoryIds));
        }

        // Sólo rubro conocido (típico después de una foto): traemos una muestra
        // acotada del rubro para poder calcular qué preguntar.
        if ($pool === [] && $query->categoryIds !== []) {
            $add($this->catalog->searchByAttributes([], $query->categoryIds, 60));
        }

        if ($query->customerProductIds !== []) {
            $add($this->catalog->findMany($query->customerProductIds));
        }

        return array_values($pool);
    }
}
