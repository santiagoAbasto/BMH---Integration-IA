<?php

declare(strict_types=1);

namespace App\Services\Ai\Tools;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DuplicateProductResolver;
use App\Domain\Customer\DTO\CustomerAccount;
use App\Domain\Inventory\InventoryService;
use App\Domain\Orders\Contracts\OrderHistoryRepositoryInterface;
use App\Domain\Orders\DTO\PurchasedLine;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Search\CandidateDisambiguationService;
use App\Domain\Search\DTO\Candidate;
use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\HybridProductSearchService;

/**
 * Las herramientas que la IA puede pedir.
 *
 * El modelo NO tiene acceso a MySQL. Sólo puede nombrar una de estas tools y
 * pasarle argumentos. Laravel valida el nombre contra el registro, sanea los
 * argumentos, ejecuta la consulta que él decide y devuelve JSON.
 *
 * En ningún escenario se ejecuta SQL producido por un modelo.
 *
 * El `customer` se inyecta desde la sesión del backend al construir el
 * registro: no viaja como argumento, así que el modelo no puede pedir datos de
 * otro cliente ni aunque lo intente.
 *
 * @see docs/security.md §"Tool authorization"
 */
final class ToolRegistry
{
    private array $executionLog = [];

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly HybridProductSearchService $search,
        private readonly CandidateDisambiguationService $disambiguation,
        private readonly DuplicateProductResolver $duplicates,
        private readonly PricingEngine $pricing,
        private readonly OrderHistoryRepositoryInterface $orders,
        private readonly InventoryService $inventory,
        private readonly ?CustomerAccount $customer = null,
    ) {
    }

    public function withCustomer(?CustomerAccount $customer): self
    {
        return new self(
            $this->catalog,
            $this->search,
            $this->disambiguation,
            $this->duplicates,
            $this->pricing,
            $this->orders,
            $this->inventory,
            $customer,
        );
    }

    /** Definiciones en JSON Schema para el proveedor. @return list<array<string,mixed>> */
    public function definitions(): array
    {
        return [
            [
                'name'        => 'search_products',
                'description' => 'Busca productos en el catálogo de BMH combinando código, rubro, marca, modelo y características técnicas. Es la herramienta principal.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'text'       => ['type' => 'string', 'description' => 'Descripción libre de lo que busca el cliente'],
                        'code'       => ['type' => 'string', 'description' => 'Código si lo mencionó o se leyó en la foto'],
                        'brand'      => ['type' => 'string'],
                        'model'      => ['type' => 'string'],
                        'category'   => ['type' => 'string', 'description' => 'Nombre del rubro, por ejemplo ROTORES'],
                        'attributes' => [
                            'type'        => 'object',
                            'description' => 'Características técnicas: voltage, amperes, diameter, total_length, splines, teeth, pins',
                        ],
                    ],
                ],
            ],
            [
                'name'        => 'search_by_code',
                'description' => 'Busca por código exacto o normalizado. Un código exacto tiene más autoridad que cualquier parecido visual.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => ['code' => ['type' => 'string']],
                    'required'   => ['code'],
                ],
            ],
            [
                'name'        => 'search_by_equivalence',
                'description' => 'Busca por un código de otro fabricante (Bosch, Valeo, ZEN, PH, etc.) declarado como equivalencia.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => ['code' => ['type' => 'string']],
                    'required'   => ['code'],
                ],
            ],
            [
                'name'        => 'get_product',
                'description' => 'Devuelve la ficha completa de un producto por su id.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => ['product_id' => ['type' => 'integer']],
                    'required'   => ['product_id'],
                ],
            ],
            [
                'name'        => 'compare_products',
                'description' => 'Compara 2 a 4 productos y devuelve sólo los atributos en los que difieren.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'product_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                    ],
                    'required' => ['product_ids'],
                ],
            ],
            [
                'name'        => 'get_customer_price',
                'description' => 'Calcula el precio del cliente autenticado para un producto. NUNCA calcules vos un precio: usá siempre esta herramienta.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => ['product_id' => ['type' => 'integer']],
                    'required'   => ['product_id'],
                ],
            ],
            [
                'name'        => 'get_customer_order_history',
                'description' => 'Compras anteriores del cliente autenticado. Sirve para "el que compro siempre".',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'category' => ['type' => 'string', 'description' => 'Filtrar por rubro (opcional)'],
                        'limit'    => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name'        => 'list_categories',
                'description' => 'Lista los rubros del catálogo con su cantidad de productos.',
                'parameters'  => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name'        => 'check_availability',
                'description' => 'Consulta disponibilidad. Puede devolver que no es verificable: en ese caso NO afirmes que hay stock.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => ['product_id' => ['type' => 'integer']],
                    'required'   => ['product_id'],
                ],
            ],
            [
                'name'        => 'request_human_assistance',
                'description' => 'Deriva la consulta a un asesor de BMH cuando no se puede resolver con confianza.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => ['reason' => ['type' => 'string']],
                    'required'   => ['reason'],
                ],
            ],
        ];
    }

    public function has(string $name): bool
    {
        foreach ($this->definitions() as $definition) {
            if ($definition['name'] === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ejecuta una tool. Todo lo que sale de acá es JSON serializable.
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(string $name, array $arguments): array
    {
        if (! $this->has($name)) {
            return ['error' => 'unknown_tool'];
        }

        $started = microtime(true);

        $result = match ($name) {
            'search_products'            => $this->searchProducts($arguments),
            'search_by_code'             => $this->searchByCode($arguments),
            'search_by_equivalence'      => $this->searchByEquivalence($arguments),
            'get_product'                => $this->getProduct($arguments),
            'compare_products'           => $this->compareProducts($arguments),
            'get_customer_price'         => $this->getCustomerPrice($arguments),
            'get_customer_order_history' => $this->getOrderHistory($arguments),
            'list_categories'            => $this->listCategories(),
            'check_availability'         => $this->checkAvailability($arguments),
            'request_human_assistance'   => $this->requestHuman($arguments),
            default                      => ['error' => 'unknown_tool'],
        };

        $this->executionLog[] = [
            'tool'       => $name,
            'arguments'  => $this->redact($arguments),
            'latency_ms' => round((microtime(true) - $started) * 1000, 2),
            'ok'         => ! isset($result['error']),
        ];

        return $result;
    }

    /** @return list<array<string,mixed>> */
    public function executionLog(): array
    {
        return $this->executionLog;
    }

    // -----------------------------------------------------------------
    // Implementaciones
    // -----------------------------------------------------------------

    private function searchProducts(array $arguments): array
    {
        $query = new SearchQuery(
            rawText: $this->str($arguments['text'] ?? null),
            code: $this->str($arguments['code'] ?? null),
            brand: $this->str($arguments['brand'] ?? null),
            model: $this->str($arguments['model'] ?? null),
            categoryIds: $this->resolveCategoryIds($this->str($arguments['category'] ?? null)),
            attributes: $this->sanitizeAttributes($arguments['attributes'] ?? []),
            customerProductIds: $this->customerProductIds(),
        );

        $candidates = $this->search->search($query);

        return $this->candidatePayload($candidates, $query);
    }

    private function searchByCode(array $arguments): array
    {
        $code = (string) ($arguments['code'] ?? '');

        $products = $this->catalog->findByCode($code);

        if ($products === []) {
            $products = $this->catalog->findByNormalizedCode($code);
        }

        if ($products === []) {
            return [
                'total'      => 0,
                'candidates' => [],
                'message'    => 'Ese código no figura en el catálogo de BMH.',
            ];
        }

        $resolution = $this->duplicates->resolve($products);

        if ($resolution['ambiguous']) {
            return [
                'total'                    => count($products),
                'duplicate_code'           => true,
                'requires_disambiguation'  => true,
                'distinguishing_attributes'=> $resolution['distinguishing'],
                'candidates'               => array_map(
                    static fn ($p): array => $p->forAiTool(),
                    $products
                ),
                'message' => 'Ese código figura en más de un artículo. Hay que distinguirlos antes de confirmar.',
            ];
        }

        $resolved = $resolution['resolved'];

        return [
            'total'      => $resolved === null ? count($products) : 1,
            'candidates' => $resolved === null
                ? array_map(static fn ($p): array => $p->forAiTool(), $products)
                : [$resolved->forAiTool()],
        ];
    }

    private function searchByEquivalence(array $arguments): array
    {
        $products = $this->catalog->findByEquivalence((string) ($arguments['code'] ?? ''));

        return [
            'total'      => count($products),
            'candidates' => array_map(static fn ($p): array => $p->forAiTool(), $products),
            'message'    => $products === []
                ? 'No hay ninguna equivalencia declarada con ese código.'
                : null,
        ];
    }

    private function getProduct(array $arguments): array
    {
        $product = $this->catalog->find((int) ($arguments['product_id'] ?? 0));

        if ($product === null) {
            return ['error' => 'product_not_found'];
        }

        return ['product' => $product->forAiTool()];
    }

    private function compareProducts(array $arguments): array
    {
        $ids = array_slice(
            array_map('intval', (array) ($arguments['product_ids'] ?? [])),
            0,
            (int) config('bmh.ranking.comparable_limit', 4)
        );

        $products = $this->catalog->findMany($ids);

        if (count($products) < 2) {
            return ['error' => 'need_at_least_two_products'];
        }

        return [
            'products'    => array_map(static fn ($p): array => $p->forAiTool(), $products),
            // Sólo lo que difiere: es lo único útil para elegir.
            'differences' => $this->duplicates->distinguishingAttributes($products),
        ];
    }

    /**
     * Precio.
     *
     * El id de cliente sale de la sesión, no de los argumentos. Si no hay
     * cliente autenticado, no se cotiza.
     */
    private function getCustomerPrice(array $arguments): array
    {
        if ($this->customer === null) {
            return ['error' => 'not_authenticated', 'message' => 'Hay que iniciar sesión para ver precios.'];
        }

        if (! $this->customer->enabled) {
            return ['error' => 'account_disabled', 'message' => 'La cuenta todavía no está habilitada para ver precios.'];
        }

        $productId = (int) ($arguments['product_id'] ?? 0);
        $product   = $this->catalog->find($productId);

        if ($product === null) {
            return ['error' => 'product_not_found'];
        }

        $quote = $this->pricing->quote($productId, $this->customer, $product->category?->id);

        return $quote->forAiTool();
    }

    private function getOrderHistory(array $arguments): array
    {
        if ($this->customer === null) {
            return ['error' => 'not_authenticated'];
        }

        if (! config('bmh.features.customer_history')) {
            return ['error' => 'feature_disabled'];
        }

        $limit      = min(25, max(1, (int) ($arguments['limit'] ?? 10)));
        $categoryId = $this->resolveCategoryIds($this->str($arguments['category'] ?? null))[0] ?? null;

        $lines = $categoryId === null
            ? $this->orders->linesForCustomer($this->customer->id, $limit)
            : $this->orders->linesForCustomerInCategory($this->customer->id, $categoryId, $limit);

        return [
            'total' => count($lines),
            'lines' => array_map(static fn (PurchasedLine $l): array => $l->jsonSerialize(), $lines),
            'note'  => 'El historial es una señal, no la verdad actual: confirmá siempre contra el catálogo y el precio vigente.',
        ];
    }

    private function listCategories(): array
    {
        return [
            'categories' => array_map(
                static fn ($c): array => ['id' => $c->id, 'name' => $c->name, 'products' => $c->productCount],
                $this->catalog->categories()
            ),
        ];
    }

    private function checkAvailability(array $arguments): array
    {
        $productId = (int) ($arguments['product_id'] ?? 0);

        $availability = $this->inventory->availabilityFor($productId);

        return [
            'availability' => $availability['availability'],
            'can_assert'   => $availability['can_assert'],
            'message'      => $availability['message'],
        ];
    }

    private function requestHuman(array $arguments): array
    {
        return [
            'handoff_requested' => true,
            'reason'            => mb_substr((string) ($arguments['reason'] ?? 'unspecified'), 0, 200),
            'message'           => (string) config('bmh.handoff.message'),
        ];
    }

    // -----------------------------------------------------------------
    // Soporte
    // -----------------------------------------------------------------

    /** @param list<Candidate> $candidates */
    public function candidatePayload(array $candidates, SearchQuery $query): array
    {
        $presented = array_slice($candidates, 0, (int) config('bmh.ranking.max_presented', 3));
        $next      = $this->disambiguation->nextQuestion($candidates);

        return [
            'total'      => count($candidates),
            'strategy'   => $this->search->strategyFor($query),
            'candidates' => array_map(
                static fn (Candidate $c): array => array_merge(
                    $c->product->forAiTool(),
                    [
                        'confidence'       => round($c->confidence(), 3),
                        'confidence_label' => $c->confidenceLabel(),
                        'matched_on'       => array_values(array_unique($c->matchedOn)),
                    ]
                ),
                $presented
            ),
            'next_question' => $next['should_ask'] ? [
                'attribute' => $next['attribute']['key'],
                'label'     => $next['attribute']['label'],
                'options'   => $next['attribute']['options'],
            ] : null,
            'disambiguation_reason' => $next['reason'],
        ];
    }

    /** @return list<int> */
    private function resolveCategoryIds(?string $name): array
    {
        if ($name === null || trim($name) === '') {
            return [];
        }

        $needle = $this->fold($name);
        $ids    = [];

        foreach ($this->catalog->categories() as $category) {
            $haystack = $this->fold($category->name);

            if ($haystack === $needle || str_contains($haystack, $needle) || str_contains($needle, $haystack)) {
                $ids[] = $category->id;
            }
        }

        return $ids;
    }

    /** @return list<int> */
    private function customerProductIds(): array
    {
        if ($this->customer === null || ! config('bmh.features.customer_history')) {
            return [];
        }

        return $this->orders->purchasedProductIds($this->customer->id);
    }

    /**
     * Sólo se aceptan claves de atributo conocidas. Cualquier otra cosa que
     * mande el modelo se descarta.
     *
     * @return array<string,string>
     */
    private function sanitizeAttributes(mixed $attributes): array
    {
        if (! is_array($attributes)) {
            return [];
        }

        $clean = [];

        foreach ($attributes as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $canonical = \App\Domain\Catalog\LegacyAttributeMap::slotForKey((string) $key) !== null
                ? (string) $key
                : \App\Domain\Catalog\LegacyAttributeMap::resolveTerm((string) $key);

            if ($canonical === null) {
                continue;
            }

            $clean[$canonical] = mb_substr(trim((string) $value), 0, 60);
        }

        return $clean;
    }

    private function str(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, 200);
    }

    private function redact(array $arguments): array
    {
        return array_map(
            static fn ($v) => is_scalar($v) ? mb_substr((string) $v, 0, 80) : $v,
            $arguments
        );
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    }
}
