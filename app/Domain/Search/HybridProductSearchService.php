<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Catalog\CatalogFamilyResolver;
use App\Domain\Catalog\LegacyAttributeMap;
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
        private readonly CatalogFamilyResolver $familias,
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
     * De qué familia de piezas habla la consulta.
     *
     * Puede venir ya resuelta desde la conversación o estar sólo en las palabras
     * que escribió el cliente: "necesito el regulador 17705" dice el rubro
     * aunque nadie lo haya seleccionado.
     *
     * @return list<int>
     */
    private function rubrosDeLaConsulta(SearchQuery $query): array
    {
        if ($query->categoryIds !== []) {
            return $query->categoryIds;
        }

        return $query->hasText() ? $this->familias->rubros([(string) $query->rawText]) : [];
    }

    /**
     * @param  list<ProductView> $productos
     * @return array<int, ProductView>
     */
    private function porId(array $productos): array
    {
        $porId = [];

        foreach ($productos as $producto) {
            $porId[$producto->id] = $producto;
        }

        return $porId;
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
                /*
                 * Un dato que salió de mirar una foto no descarta.
                 *
                 * Con la ficha vista de canto el modelo contó 3 pines donde hay
                 * 5, y ese error borraba del listado la pieza que el cliente
                 * buscaba: peor que ofrecerla tercera, porque ya no hay pregunta
                 * que la recupere. Igual sigue puntuando en el ranking, así que
                 * cuando la observación es buena la pieza correcta sale arriba.
                 */
                if ($query->isObserved((string) $key)) {
                    continue;
                }

                $attribute = $product->attribute((string) $key);

                if ($attribute === null) {
                    continue; // dato faltante: no es contradicción
                }

                if (! $this->valuesAgree($attribute->value, (string) $expected, (string) $key)) {
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
    /**
     * ¿El valor del catálogo y el que se busca son el mismo dato?
     *
     * La tolerancia depende de QUÉ se está comparando. Un milímetro de más en un
     * diámetro es la misma pieza medida con otra regla; un diente de más en un
     * piñón, o un pin de más en una ficha, es OTRA pieza.
     *
     * Con la tolerancia fija de ±1 que había antes, una foto donde se contaban 5
     * pines devolvía también los reguladores de 4, y el de 4 llegaba a salir
     * primero. Los conteos tienen que coincidir exactos.
     */
    private function valuesAgree(string $actual, string $expected, string $key = ''): bool
    {
        $actualNumber   = $this->numeric($actual);
        $expectedNumber = $this->numeric($expected);

        if ($actualNumber !== null && $expectedNumber !== null) {
            return abs($actualNumber - $expectedNumber) <= $this->toleranciaDe($key);
        }

        return $this->loosely($expected, $actual);
    }

    /** Cuánto puede diferir un valor y seguir siendo el mismo dato. */
    private function toleranciaDe(string $key): float
    {
        $slot = LegacyAttributeMap::slotForKey($key);

        if ($slot === null) {
            return 1.0;
        }

        return match (LegacyAttributeMap::type($slot)) {
            // Contar es exacto: no se cuentan 4,5 dientes.
            LegacyAttributeMap::TYPE_COUNT      => 0.0,
            // Medir no lo es: la base guarda "88.8" y "89" para la misma pieza.
            LegacyAttributeMap::TYPE_DIMENSION  => 1.0,
            LegacyAttributeMap::TYPE_ELECTRICAL => 0.01,
            default                             => 1.0,
        };
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

            /*
             * El cliente escribe el número que está grabado en la pieza —"17705"—
             * y el catálogo lo guarda con la familia adelante: REG17705 si es un
             * regulador, POL17705 si es una polea. Sabiendo de qué habla, el
             * número deja de ser una coincidencia parcial y pasa a ser el código
             * exacto, y la polea deja de aparecer al lado del regulador.
             */
            $familias = $this->rubrosDeLaConsulta($query);

            if ($pool === [] && $familias !== []) {
                $completados = $this->familias->completarCodigo($code, $familias);

                $add($completados);

                // Reconstruir el código no es una coincidencia parcial: es el
                // código exacto, escrito como lo escribe BMH. El ranking tiene
                // que verlo así o el acierto queda en confianza baja.
                if ($completados !== []) {
                    $query->code = $completados[0]->code;
                }
            }

            $add($this->catalog->findByEquivalence($code));

            // Si el código exacto ya resolvió, no hace falta ensuciar el pool
            // con coincidencias parciales.
            if ($pool === []) {
                $add($this->catalog->searchByPartialCode($code));
            }

            $pool = $this->porId($this->familias->preferirDelRubro(array_values($pool), $familias));
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

                /*
                 * Si TODO lo que se está filtrando salió de mirar una foto, el
                 * rubro entero entra igual detrás.
                 *
                 * No alcanza con que una observación equivocada deje de
                 * descartar: si además arma sola el conjunto de candidatos, la
                 * pieza correcta nunca se busca. Contando 3 pines donde hay 5,
                 * el regulador de 5 no aparecía por ningún lado.
                 *
                 * Los que coinciden con lo observado siguen puntuando más alto,
                 * así que cuando la foto acierta la pieza sigue saliendo
                 * primera; cuando se equivoca, al menos está.
                 */
                $confirmados = array_diff_key($attributes, array_flip($query->observedAttributes));

                if ($confirmados === [] && $query->categoryIds !== []) {
                    $add($this->catalog->searchByAttributes([], $query->categoryIds, $query->limit * 2));
                }
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
