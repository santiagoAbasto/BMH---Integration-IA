<?php

namespace App\Providers;

use App\Services\LogosSitio;
use Illuminate\Support\Facades\URL;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        

    }
}
