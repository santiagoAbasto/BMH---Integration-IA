<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const ADMIN = '/dashboard';

    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        /*
        | Asesor IA. Se limita por la identidad autenticada: cliente en la zona
        | pública y administrador en el laboratorio. Así, talleres o testers
        | que comparten una salida NAT no se consumen la cuota entre sí.
        */
        RateLimiter::for('assistant', function (Request $request) {
            $admin = $request->user('admin');

            if ($admin !== null) {
                return Limit::perMinute(30)->by(
                    'assistant:admin:'.$admin->getAuthIdentifier()
                );
            }

            return Limit::perMinute(30)->by(
                'assistant:customer:'.($request->user()?->getAuthIdentifier() ?: $request->ip())
            );
        });

        /*
        | Sugerencias del buscador del header. El JS ya espera a que se deje de
        | tipear (debounce) y cancela lo viejo, así que un visitante normal
        | queda muy lejos del techo; esto corta scrapers del catálogo.
        */
        RateLimiter::for('buscador', function (Request $request) {
            return Limit::perMinute(120)->by('buscador:'.$request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
