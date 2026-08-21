<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use Illuminate\Support\Facades\Auth;

/**
 * Datos mínimos que necesita el widget del asesor para montarse.
 *
 * Se inyecta en el layout de la Zona de Clientes, así que corre en CADA página:
 * por eso es una sola consulta y nada más. El historial de compras y el resto
 * del contexto los pide el widget recién cuando el cliente lo abre, que es
 * cuando hacen falta.
 */
final class AdvisorBootstrap
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly AiProviderManager $providers,
    ) {
    }

    /** ¿Corresponde mostrar el asesor en esta página? */
    public function shouldRender(): bool
    {
        $user = Auth::guard('web')->user();

        return $user !== null
            && in_array($user->rol, ['cliente', 'vendedor'], true)
            && config('bmh.features.ai') !== false;
    }

    /** @return array<string, mixed>|null */
    public function payload(): ?array
    {
        $user = Auth::guard('web')->user();

        if ($user === null) {
            return null;
        }

        $customer = $this->customers->find((int) $user->id);

        if ($customer === null) {
            return null;
        }

        return [
            // Sólo lo que la UI necesita. Sin DNI, sin dirección, sin teléfono.
            'customer' => [
                'name'    => explode(' ', trim($customer->displayName))[0],
                'code'    => $customer->code,
                'segment' => $customer->commercialSegment(),
                'enabled' => $customer->enabled,
                'resale'  => $customer->hasResale(),
            ],
            // Se carga al abrir, no en cada page load.
            'recentPurchases' => [],
            'settings'        => [
                'provider' => $this->providers->describe(),
                'features' => [
                    'vision'  => (bool) config('bmh.features.vision'),
                    'history' => (bool) config('bmh.features.customer_history'),
                    'debug'   => (bool) config('bmh.features.debug'),
                ],
                'canAssertStock' => (bool) config('bmh.inventory.semantics_verified'),
            ],
        ];
    }
}
