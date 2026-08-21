<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\Legacy\LegacyBmhCatalogRepository;
use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\Legacy\LegacyBmhCustomerRepository;
use App\Domain\Orders\Contracts\OrderHistoryRepositoryInterface;
use App\Domain\Orders\Legacy\LegacyBmhOrderHistoryRepository;
use App\Domain\Pricing\Contracts\PricingRepositoryInterface;
use App\Domain\Pricing\Legacy\LegacyBmhPricingRepository;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\Contracts\AiProviderInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Cablea el asesor.
 *
 * Las interfaces del dominio se resuelven a las implementaciones legacy. El día
 * que BMH migre el esquema, se cambia acá y nada más.
 */
final class BmhAdvisorServiceProvider extends ServiceProvider
{
    public array $bindings = [
        CatalogRepositoryInterface::class      => LegacyBmhCatalogRepository::class,
        CustomerRepositoryInterface::class     => LegacyBmhCustomerRepository::class,
        OrderHistoryRepositoryInterface::class => LegacyBmhOrderHistoryRepository::class,
        PricingRepositoryInterface::class      => LegacyBmhPricingRepository::class,
    ];

    public function register(): void
    {
        // Singleton para que el cache de proveedores y cualquier `extend()`
        // valgan para todo el request.
        $this->app->singleton(AiProviderManager::class);

        // El provider concreto lo elige el manager según config + disponibilidad
        // de API key. Sin key, cae solo a MockAiProvider y la demo sigue andando.
        $this->app->bind(
            AiProviderInterface::class,
            static fn ($app): AiProviderInterface => $app->make(AiProviderManager::class)->primary()
        );
    }

    public function boot(): void
    {
        //
    }
}
