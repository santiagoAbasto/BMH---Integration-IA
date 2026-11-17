<?php

namespace App\Providers;

use App\Services\LogosSitio;
use App\View\Composers\MenuProductosComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
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
        // Sitio y panel navegan sin recargar (Turbo). Sus estilos y scripts de Vite van marcados: si cambian (otro
        // deploy, o pasar del sitio al panel, que usan otros archivos), Turbo hace una carga completa en vez de
        // mezclar los dos <head>.
        Vite::useScriptTagAttributes(["data-turbo-track" => "reload"]);
        Vite::useStyleTagAttributes(["data-turbo-track" => "reload"]);

         if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer(['layouts.partials.menu-productos', 'layouts.partials.sitio-header'], MenuProductosComposer::class);

    }
}
