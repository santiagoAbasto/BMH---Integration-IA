<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Orders\Contracts\OrderHistoryRepositoryInterface;
use App\Models\Ai\AiConversation;
use App\Services\Ai\AiProviderManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * La pantalla del asesor.
 *
 * Inertia se encarga de la página, el layout y la sesión. El chat en sí habla
 * por REST/SSE, que es lo que corresponde para streaming y subida de archivos.
 */
final class AssistantPageController extends Controller
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly OrderHistoryRepositoryInterface $orders,
        private readonly AiProviderManager $providers,
    ) {
    }

    public function show(Request $request, ?int $conversation = null): Response
    {
        $user     = Auth::guard('web')->user();
        $customer = $this->customers->find((int) $user->id);

        abort_if($customer === null, 403);

        $conversations = AiConversation::query()
            ->ownedBy($customer->id)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'status', 'updated_at']);

        // Últimas compras: alimentan el empty state con accesos que valen algo,
        // en vez de una pantalla vacía.
        $recentPurchases = config('bmh.features.customer_history')
            ? collect($this->orders->linesForCustomer($customer->id, 6))
                ->unique('productId')
                ->values()
                ->map(static fn ($line): array => [
                    'product_id' => $line->productId,
                    'code'       => $line->productCode,
                    'name'       => $line->productName,
                    'category'   => $line->categoryName,
                ])
                ->all()
            : [];

        return Inertia::render('Customer/Assistant/Index', [
            'customer' => [
                'name'    => $this->firstName($customer->displayName),
                'code'    => $customer->code,
                'segment' => $customer->commercialSegment(),
                'enabled' => $customer->enabled,
                'resale'  => $customer->hasResale(),
            ],
            'conversations'      => $conversations,
            'activeConversation' => $conversation,
            'recentPurchases'    => $recentPurchases,
            'settings'           => [
                'provider' => $this->providers->describe(),
                'features' => [
                    'vision'  => (bool) config('bmh.features.vision'),
                    'history' => (bool) config('bmh.features.customer_history'),
                    'debug'   => (bool) config('bmh.features.debug'),
                ],
                'canAssertStock' => (bool) config('bmh.inventory.semantics_verified'),
            ],
        ]);
    }

    private function firstName(string $name): string
    {
        return explode(' ', trim($name))[0];
    }
}
