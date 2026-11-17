<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Registro público de clientes (además del filtro anti-bots `human-form`). El alta desde el panel (misma ruta)
        // no tiene límite. Por IP para frenar ráfagas, y por usuario/email para que un bot que rota IPs no insista.
        RateLimiter::for('registro', function (Request $request) {
            if (Auth::guard('admin')->check()) {
                return Limit::none();
            }
            $mensaje = 'Hubo demasiados intentos de registro desde esta conexión. Esperá un rato antes de volver a intentar.';
            $aviso = fn (Request $request, array $headers) => $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $mensaje, 'errors' => ['username' => [$mensaje]]], 429, $headers)
                : back(303)->withErrors(['username' => $mensaje])->with('warning', $mensaje)
                    ->withInput($request->except(['_token', 'password', 'password_confirmation', 'website', '_form_token']));
            $cuenta = mb_strtolower(trim((string) ($request->input('email') ?: $request->input('username')))) ?: 'ip:'.$request->ip();

            return [
                Limit::perMinute(5)->by('registro:ip:'.$request->ip())->response($aviso),
                Limit::perDay(20)->by('registro:ip-dia:'.$request->ip())->response($aviso),
                Limit::perHour(5)->by('registro:cuenta:'.$cuenta)->response($aviso),
            ];
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
