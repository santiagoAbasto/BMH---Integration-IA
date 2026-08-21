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
        | Asesor IA. Se limita por cliente autenticado, no por IP: varios
        | talleres pueden compartir una salida NAT y no queremos que uno le
        | consuma la cuota al otro. El techo contiene el costo de tokens de una
        | sesión desbocada sin molestar a un uso normal.
        */
        RateLimiter::for('assistant', function (Request $request) {
            return Limit::perMinute(30)->by(
                'assistant:' . ($request->user()?->id ?: $request->ip())
            );
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
