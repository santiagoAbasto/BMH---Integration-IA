<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Legacy;

use App\Domain\Catalog\CatalogSemanticLayer;
use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DTO\CategoryView;
use App\Domain\Catalog\DTO\ProductCondition;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Catalog\LegacyAttributeMap;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Implementación del catálogo sobre la base legacy de BMH.
 *
 * Es el ÚNICO lugar del sistema, junto con el semantic layer, que escribe SQL
 * contra `productos` / `categorias` / `producto_caracteristica`. La conexión
 * `mysql_legacy` usa un usuario con GRANT SELECT solamente: si algo intentara
 * escribir, MySQL lo rechaza.
 *
 * Todas las lecturas de producto pasan por hydrate(), que carga categorías,
 * características e imágenes en batch para no caer en N+1.
 */
final class LegacyBmhCatalogRepository implements CatalogRepositoryInterface
{
    private const CACHE_CATEGORIES = 'bmh:legacy:categories';
    private const CACHE_DUPLICATES = 'bmh:legacy:duplicate-codes';

    /** Filas de `categorias` cacheadas en memoria durante el request. */
    private ?array $categoryRows = null;

    public function __construct(
        private readonly CatalogSemanticLayer $semanticLayer,
        private readonly ProductImageService $images,
    ) {
    }

    private function db(): ConnectionInterface
    {
        return DB::connection('mysql_legacy');
    }

    public function find(int $id): ?ProductView
    {
        return $this->findMany([$id])[0] ?? null;
    }

    public function findMany(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->db()->table('productos')
            ->whereIn('id', $ids)
            ->get()
            ->all();

        // Preserva el orden pedido: el ranking ya decidió cuál va primero.
        $order  = array_flip(array_values($ids));
        usort($rows, static fn ($a, $b) => ($order[$a->id] ?? PHP_INT_MAX) <=> ($order[$b->id] ?? PHP_INT_MAX));

        return $this->hydrate($rows);
    }

    public function findByCode(string $code): array
    {
        $rows = $this->db()->table('productos')
            ->where('codigo', trim($code))
            ->get()
            ->all();

        return $this->hydrate($rows);
    }

    public function findByNormalizedCode(string $code): array
    {
        $normalized = CatalogSemanticLayer::normalizeCode($code);

        if ($normalized === '') {
            return [];
        }

        // La base no tiene el código normalizado, así que se normaliza del lado
        // de MySQL. Con 5.054 filas el scan es irrelevante (~8 ms medidos) y
        // evita tocar el esquema legacy para agregar una columna.
        $rows = $this->db()->table('productos')
            ->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(codigo,' ',''),'-',''),'.',''),'/','')) = ?",
                [$normalized]
            )
            ->get()
            ->all();

        return $this->hydrate($rows);
    }

    public function searchByPartialCode(string $fragment, int $limit = 25): array
    {
        $normalized = CatalogSemanticLayer::normalizeCode($fragment);

        if (mb_strlen($normalized) < 3) {
            return [];
        }

        $rows = $this->db()->table('productos')
            ->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(codigo,' ',''),'-',''),'.',''),'/','')) LIKE ?",
                ['%' . $normalized . '%']
            )
            ->limit($limit)
            ->get()
            ->all();

        return $this->hydrate($rows);
    }

    public function findByEquivalence(string $code, int $limit = 25): array
    {
        $normalized = CatalogSemanticLayer::normalizeCode($code);

        if (mb_strlen($normalized) < 3) {
            return [];
        }

        $like = '%' . $normalized . '%';

        // Fuente 1: EAV moderno (producto_caracteristica), que es donde viven
        // las equivalencias de verdad.
        $viaCharacteristics = $this->db()->table('producto_caracteristica as pc')
            ->join('caracteristicas as c', 'c.id', '=', 'pc.caracteristica_id')
            ->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(pc.valor,' ',''),'-',''),'.',''),'/','')) LIKE ?",
                [$like]
            )
            ->where(function ($q): void {
                $q->where('c.nombre', 'like', '%EQUIVALENCIA%')
                    ->orWhere('c.nombre', 'like', '%ORIGINAL%')
                    ->orWhere('c.nombre', 'like', 'CODIGO %');
            })
            ->limit($limit)
            ->pluck('pc.producto_id')
            ->all();

        // Fuente 2: slots legacy de equivalencia.
        $equivalenceSlots = array_filter(
            LegacyAttributeMap::crossReferenceSlots(),
            static function (int $slot): bool {
                $key = (string) LegacyAttributeMap::key($slot);

                return str_starts_with($key, 'equiv') || str_starts_with($key, 'code_');
            }
        );

        $query = $this->db()->table('productos')->select('id');
        $first = true;

        foreach ($equivalenceSlots as $slot) {
            $column = LegacyAttributeMap::column($slot);
            $raw    = "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(`{$column}`,' ',''),'-',''),'.',''),'/','')) LIKE ?";

            $first ? $query->whereRaw($raw, [$like]) : $query->orWhereRaw($raw, [$like]);
            $first = false;
        }

        $viaSlots = $first ? [] : $query->limit($limit)->pluck('id')->all();

        // Fuente 3: tabla nueva `equivalencias` (clave-valor ordenada).
        $viaNewEquivalences = $this->db()->table('equivalencias')
            ->whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(valor,' ',''),'-',''),'.',''),'/','')) LIKE ?",
                [$like]
            )
            ->limit($limit)
            ->pluck('producto_id')
            ->all();

        $ids = array_slice(
            array_values(array_unique([...$viaCharacteristics, ...$viaSlots, ...$viaNewEquivalences])),
            0,
            $limit
        );

        return $this->findMany(array_map('intval', $ids));
    }

    public function searchByKeywords(string $query, array $categoryIds = [], int $limit = 50): array
    {
        $terms = $this->tokenize($query);

        if ($terms === []) {
            return [];
        }

        $builder = $this->db()->table('productos');

        // AND entre términos, OR entre columnas: "rotor bosch" tiene que pedir
        // las dos cosas, no cualquiera de las dos.
        foreach ($terms as $term) {
            $like = '%' . $term . '%';
            $builder->where(function ($q) use ($like): void {
                $q->where('nombre', 'like', $like)
                    ->orWhere('marca', 'like', $like)
                    ->orWhere('modelo', 'like', $like)
                    ->orWhere('codigo', 'like', $like);
            });
        }

        if ($categoryIds !== []) {
            $builder->whereIn('categoria_id', $categoryIds);
        }

        $rows = $builder->limit($limit)->get()->all();

        return $this->hydrate($rows);
    }

    public function searchByAttributes(array $attributes, array $categoryIds = [], int $limit = 50): array
    {
        $builder  = $this->db()->table('productos');
        $appliedAny = false;

        foreach ($attributes as $key => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $slot = LegacyAttributeMap::slotForKey($key) ?? LegacyAttributeMap::slotForKey(
                (string) LegacyAttributeMap::resolveTerm($key)
            );

            if ($slot === null) {
                continue;
            }

            $column = LegacyAttributeMap::column($slot);
            $type   = LegacyAttributeMap::type($slot);

            if (in_array($type, [LegacyAttributeMap::TYPE_DIMENSION, LegacyAttributeMap::TYPE_ELECTRICAL], true)) {
                // Dimensiones: comparación numérica tolerante. La base guarda
                // "88.8", "88,8", "88.8 mm" indistintamente.
                $numeric = $this->parseNumeric($value);

                if ($numeric !== null) {
                    $tolerance = $type === LegacyAttributeMap::TYPE_DIMENSION ? 1.0 : 0.01;
                    $builder->whereRaw(
                        "ABS(CAST(REPLACE(REGEXP_SUBSTR(`{$column}`, '[0-9]+([.,][0-9]+)?'), ',', '.') AS DECIMAL(12,3)) - ?) <= ?",
                        [$numeric, $tolerance]
                    );
                    $appliedAny = true;
                    continue;
                }
            }

            $builder->where($column, 'like', '%' . $value . '%');
            $appliedAny = true;
        }

        if (! $appliedAny && $categoryIds === []) {
            return [];
        }

        if ($categoryIds !== []) {
            $builder->whereIn('categoria_id', $categoryIds);
        }

        $rows = $builder->limit($limit)->get()->all();

        return $this->hydrate($rows);
    }

    public function categories(): array
    {
        return Cache::remember(self::CACHE_CATEGORIES, 3600, function (): array {
            $counts = $this->db()->table('productos')
                ->select('categoria_id', DB::raw('COUNT(*) as total'))
                ->groupBy('categoria_id')
                ->pluck('total', 'categoria_id')
                ->all();

            return array_map(
                fn (object $row): CategoryView => new CategoryView(
                    id: (int) $row->id,
                    name: trim((string) $row->nombre),
                    alias: $row->alias ?? null,
                    attributeSlots: $this->slotsWithLabel($row),
                    productCount: (int) ($counts[$row->id] ?? 0),
                ),
                $this->categoryRows()
            );
        });
    }

    public function category(int $id): ?CategoryView
    {
        foreach ($this->categories() as $category) {
            if ($category->id === $id) {
                return $category;
            }
        }

        return null;
    }

    public function duplicateCodes(): array
    {
        return Cache::remember(self::CACHE_DUPLICATES, 3600, function (): array {
            $rows = $this->db()->table('productos')
                ->select('codigo', DB::raw('GROUP_CONCAT(id) as ids'))
                ->groupBy('codigo')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            $duplicates = [];
            foreach ($rows as $row) {
                $duplicates[(string) $row->codigo] = array_map('intval', explode(',', (string) $row->ids));
            }

            return $duplicates;
        });
    }

    // ---------------------------------------------------------------------
    // Hidratación
    // ---------------------------------------------------------------------

    /**
     * Convierte filas crudas en ProductView cargando todo lo relacionado en
     * tres queries fijas, sin importar cuántos productos vengan.
     *
     * @param  list<object> $rows
     * @return list<ProductView>
     */
    private function hydrate(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $productIds  = array_map(static fn (object $r): int => (int) $r->id, $rows);
        $categoryMap = $this->categoryRowsById();

        $characteristics = $this->characteristicsFor($productIds);
        $imageMap        = $this->images->forProducts($productIds);
        $duplicates      = $this->duplicateCodes();

        // Nuevas tablas del asesor: conviven con la info legacy, no la reemplazan.
        $relatedPartsMap   = $this->relatedPartsFor($productIds);
        $equivalencesMap   = $this->equivalencesFor($productIds);
        $applicationsMap   = $this->applicationsFor($productIds);

        $categoryCounts = [];
        foreach ($this->categories() as $category) {
            $categoryCounts[$category->id] = $category->productCount;
        }

        $views = [];

        foreach ($rows as $row) {
            $categoryId = $row->categoria_id === null ? null : (int) $row->categoria_id;

            $views[] = $this->semanticLayer->toProductView(
                product: $row,
                categoryLabels: $categoryId === null ? null : ($categoryMap[$categoryId] ?? null),
                characteristics: $characteristics[(int) $row->id] ?? [],
                images: $imageMap[(int) $row->id] ?? [],
                duplicateCode: isset($duplicates[(string) $row->codigo]),
                categoryProductCount: $categoryCounts[$categoryId] ?? 0,
                relatedPartsTable: $relatedPartsMap[(int) $row->id] ?? [],
                equivalencesTable: $equivalencesMap[(int) $row->id] ?? [],
                applicationsTable: $applicationsMap[(int) $row->id] ?? [],
            );
        }

        return $views;
    }

    /**
     * @param  list<int> $productIds
     * @return array<int, list<object>>
     */
    private function characteristicsFor(array $productIds): array
    {
        $rows = $this->db()->table('producto_caracteristica as pc')
            ->join('caracteristicas as c', 'c.id', '=', 'pc.caracteristica_id')
            ->whereIn('pc.producto_id', $productIds)
            ->whereNull('pc.deleted_at')
            ->select('pc.id', 'pc.producto_id', 'pc.valor', 'c.nombre as caracteristica')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->producto_id][] = $row;
        }

        return $grouped;
    }

    /**
     * Partes relacionadas (tabla nueva `partes_relacionadas`): cada fila trae
     * el código y nombre del producto-parte, ordenadas por `orden`.
     *
     * @param  list<int> $productIds
     * @return array<int, list<array{code:string, name:string}>>
     */
    private function relatedPartsFor(array $productIds): array
    {
        $rows = $this->db()->table('partes_relacionadas as pr')
            ->join('productos as p', 'p.id', '=', 'pr.parte_id')
            ->whereIn('pr.producto_id', $productIds)
            ->orderBy('pr.producto_id')
            ->orderBy('pr.orden')
            ->select('pr.producto_id', 'p.codigo', 'p.nombre')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->producto_id][] = [
                'code' => (string) $row->codigo,
                'name' => (string) $row->nombre,
            ];
        }

        return $grouped;
    }

    /**
     * Equivalencias (tabla nueva `equivalencias`, estructura clave-valor).
     *
     * @param  list<int> $productIds
     * @return array<int, list<array{label:?string, code:string}>>
     */
    private function equivalencesFor(array $productIds): array
    {
        $rows = $this->db()->table('equivalencias')
            ->whereIn('producto_id', $productIds)
            ->orderBy('producto_id')
            ->orderBy('orden')
            ->select('producto_id', 'nombre', 'valor')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->producto_id][] = [
                'label' => $row->nombre ?? null,
                'code'  => (string) $row->valor,
            ];
        }

        return $grouped;
    }

    /**
     * Aplicaciones (tabla nueva `aplicaciones`).
     *
     * @param  list<int> $productIds
     * @return array<int, list<array{label:?string, code:string}>>
     */
    private function applicationsFor(array $productIds): array
    {
        $rows = $this->db()->table('aplicaciones')
            ->whereIn('producto_id', $productIds)
            ->orderBy('producto_id')
            ->orderBy('orden')
            ->select('producto_id', 'nombre', 'valor')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->producto_id][] = [
                'label' => $row->nombre ?? null,
                'code'  => (string) $row->valor,
            ];
        }

        return $grouped;
    }

    /** @return list<object> */
    private function categoryRows(): array
    {
        return $this->categoryRows ??= $this->db()->table('categorias')->get()->all();
    }

    /** @return array<int, object> */
    private function categoryRowsById(): array
    {
        $map = [];
        foreach ($this->categoryRows() as $row) {
            $map[(int) $row->id] = $row;
        }

        return $map;
    }

    /** @return list<int> */
    private function slotsWithLabel(object $categoryRow): array
    {
        $slots = [];

        foreach (LegacyAttributeMap::slots() as $slot) {
            $label = $categoryRow->{LegacyAttributeMap::categoryLabelColumn($slot)} ?? null;
            if ($label !== null && trim((string) $label) !== '') {
                $slots[] = $slot;
            }
        }

        return $slots;
    }

    /** @return list<string> */
    private function tokenize(string $query): array
    {
        $query = mb_strtolower(trim($query));
        $parts = preg_split('/[\s,]+/u', $query) ?: [];

        $stopwords = [
            'de', 'del', 'la', 'el', 'los', 'las', 'un', 'una', 'para', 'con', 'por',
            'que', 'necesito', 'tenes', 'tenés', 'tienen', 'busco', 'quiero', 'hay',
            'me', 'mi', 'es', 'y', 'o', 'algo', 'este', 'esta', 'ese', 'esa',
            // Cortesías. Sin esto, un "hola" dispara un LIKE '%hola%' contra el
            // catálogo y el asesor "encuentra" tres piezas que no vienen al caso.
            'hola', 'buenas', 'buen', 'dia', 'días', 'dias', 'tardes', 'noches',
            'gracias', 'ok', 'dale', 'perfecto', 'tal', 'como', 'cómo', 'andas',
            'andás', 'estas', 'estás', 'che', 'por favor', 'favor',
        ];

        $terms = [];
        foreach ($parts as $part) {
            $part = trim($part, ".,;:¿?¡!()[]\"'");
            if (mb_strlen($part) >= 2 && ! in_array($part, $stopwords, true)) {
                $terms[] = $part;
            }
        }

        return array_slice(array_values(array_unique($terms)), 0, 6);
    }

    private function parseNumeric(string $value): ?float
    {
        if (! preg_match('/-?\d+(?:[.,]\d+)?/', $value, $m)) {
            return null;
        }

        return (float) str_replace(',', '.', $m[0]);
    }

    /** Sólo productos publicables. Se aplica en la capa de búsqueda. */
    public static function isPresentable(ProductView $product): bool
    {
        return $product->condition->isPublic();
    }
}
