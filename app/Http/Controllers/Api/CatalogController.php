<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Catalog\DuplicateProductResolver;
use App\Domain\Catalog\Legacy\ProductImageService;
use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Inventory\InventoryService;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Search\DTO\Candidate;
use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\HybridProductSearchService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Catálogo de sólo lectura.
 *
 * Es el fallback cuando la IA está caída o apagada: buscar por código, ver una
 * ficha y consultar precio siguen funcionando sin tocar un modelo.
 */
final class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly HybridProductSearchService $search,
        private readonly PricingEngine $pricing,
        private readonly CustomerRepositoryInterface $customers,
        private readonly ProductImageService $images,
        private readonly InventoryService $inventory,
        private readonly DuplicateProductResolver $duplicates,
    ) {
    }

    public function categories(): JsonResponse
    {
        return response()->json(['categories' => $this->catalog->categories()]);
    }

    public function show(int $productId): JsonResponse
    {
        $product = $this->catalog->find($productId);

        if ($product === null) {
            return response()->json(['message' => 'El producto no existe.'], 404);
        }

        return response()->json([
            'product'      => $this->present($product),
            'price'        => $this->priceFor($product)?->jsonSerialize(),
            'availability' => $this->inventory->availabilityFor($product->id),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text'        => ['nullable', 'string', 'max:200'],
            'code'        => ['nullable', 'string', 'max:64'],
            'brand'       => ['nullable', 'string', 'max:64'],
            'category_id' => ['nullable', 'integer'],
            'attributes'  => ['nullable', 'array'],
        ]);

        $query = new SearchQuery(
            rawText: $validated['text'] ?? null,
            code: $validated['code'] ?? null,
            brand: $validated['brand'] ?? null,
            categoryIds: isset($validated['category_id']) ? [(int) $validated['category_id']] : [],
            attributes: array_map(
                static fn ($v): string => mb_substr((string) $v, 0, 60),
                array_filter((array) ($validated['attributes'] ?? []), 'is_scalar')
            ),
        );

        $candidates = $this->search->search($query);

        return response()->json([
            'total'    => count($candidates),
            'strategy' => $this->search->strategyFor($query),
            'results'  => array_map(
                fn (Candidate $c): array => array_merge(
                    $c->toArray((bool) config('bmh.features.debug')),
                    ['product' => $this->present($c->product)],
                ),
                array_slice($candidates, 0, 20)
            ),
        ]);
    }

    /** Comparador: sólo devuelve lo que difiere, que es lo único que ayuda a elegir. */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids'   => ['required', 'array', 'min:2', 'max:' . config('bmh.ranking.comparable_limit', 4)],
            'product_ids.*' => ['integer', 'min:1'],
        ]);

        $products = $this->catalog->findMany(array_map('intval', $validated['product_ids']));

        if (count($products) < 2) {
            return response()->json(['message' => 'Hacen falta al menos dos productos válidos.'], 422);
        }

        return response()->json([
            'products'    => array_map(fn (ProductView $p): array => $this->present($p), $products),
            'differences' => $this->duplicates->distinguishingAttributes($products),
            'prices'      => array_reduce(
                $products,
                function (array $carry, ProductView $p): array {
                    $carry[$p->id] = $this->priceFor($p)?->jsonSerialize();

                    return $carry;
                },
                []
            ),
        ]);
    }

    /** Serializa un producto agregando URLs de imagen resueltas. */
    private function present(ProductView $product): array
    {
        $payload = $product->jsonSerialize();

        $payload['images'] = array_map(
            fn (string $file): array => ['file' => $file, 'url' => $this->images->url($file)],
            $product->images,
        );

        return $payload;
    }

    private function priceFor(ProductView $product)
    {
        $user = Auth::guard('web')->user();

        if ($user === null) {
            return null;
        }

        $customer = $this->customers->find((int) $user->id);

        if ($customer === null || ! $customer->enabled) {
            return null;
        }

        return $this->pricing->quote($product->id, $customer, $product->category?->id);
    }
}
