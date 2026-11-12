<?php

namespace App\Providers;

use App\Services\LogosSitio;
use App\View\Composers\MenuProductosComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Una instancia por request: cada card de producto pide la imagen por
        // defecto, y así se consulta la tabla `imagenes` una sola vez.
        $this->app->scoped(LogosSitio::class, fn () => new LogosSitio());
        // Metadatos SEO de la página en curso (uno por request): ver layouts/partials/seo.
        $this->app->scoped(\App\Support\Seo\SeoContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer(['layouts.partials.menu-productos', 'layouts.partials.sitio-header'], MenuProductosComposer::class);

    }
}
