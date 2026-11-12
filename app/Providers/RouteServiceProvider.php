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

        $this->limitesFormularios();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /*
    | Formularios públicos y logins (además del filtro anti-bots `human-form`). Por IP para frenar ráfagas desde
    | una conexión, y por email o usuario para que un bot que rota IPs no pueda martillar la misma cuenta ni
    | llenarle la bandeja a una persona. Con JSON se responde 429 con el aviso en `errors.{clave}` (lo muestra el
    | JS de cada formulario); sin JS se vuelve al formulario con el mismo aviso.
    */
    private function limitesFormularios(): void
    {
        $demasiados = fn (string $clave, string $mensaje) => function (Request $request, array $headers) use ($clave, $mensaje) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $mensaje, 'errors' => [$clave => [$mensaje]]], 429, $headers)
                : back(303)->withErrors([$clave => $mensaje])->with('warning', $mensaje)
                    ->withInput($request->except(['_token', 'password', 'password_confirmation', 'website', '_form_token']));
        };
        // El primer campo con valor (email o usuario), en minúsculas; vacío, la IP (no comparten un mismo balde).
        $clave = fn (Request $request, string ...$campos): string => mb_strtolower(trim((string) collect($campos)
            ->map(fn (string $campo) => $request->input($campo))->first(fn ($valor) => is_string($valor) && trim($valor) !== '')))
            ?: 'ip:'.$request->ip();

        // Contacto: 5 por minuto y 30 por día desde una IP; 5 por hora a nombre del mismo email.
        RateLimiter::for('contacto', function (Request $request) use ($demasiados, $clave) {
            $aviso = $demasiados('formulario', 'Enviaste varias consultas seguidas. Esperá un rato antes de volver a escribirnos.');

            return [
                Limit::perMinute(5)->by('contacto:ip:'.$request->ip())->response($aviso),
                Limit::perDay(30)->by('contacto:ip-dia:'.$request->ip())->response($aviso),
                Limit::perHour(5)->by('contacto:mail:'.$clave($request, 'mail'))->response($aviso),
            ];
        });

        // Registro público de clientes. El alta desde el panel (misma ruta) no tiene límite.
        RateLimiter::for('registro', function (Request $request) use ($demasiados, $clave) {
            if (Auth::guard('admin')->check()) {
                return Limit::none();
            }
            $aviso = $demasiados('username', 'Hubo demasiados intentos de registro desde esta conexión. Esperá un rato antes de volver a intentar.');

            return [
                Limit::perMinute(5)->by('registro:ip:'.$request->ip())->response($aviso),
                Limit::perDay(20)->by('registro:ip-dia:'.$request->ip())->response($aviso),
                Limit::perHour(5)->by('registro:cuenta:'.$clave($request, 'email', 'username'))->response($aviso),
            ];
        });

        // Logins: ráfagas y goteo sostenido por IP, y por cuenta (además del bloqueo por fallos de LoginRequest).
        foreach (['login-clientes' => 60, 'login-admin' => 40] as $nombre => $porHora) {
            RateLimiter::for($nombre, function (Request $request) use ($demasiados, $clave, $nombre, $porHora) {
                $aviso = $demasiados('username', 'Demasiados intentos de ingreso. Esperá unos minutos antes de volver a intentar.');

                return [
                    Limit::perMinute(10)->by($nombre.':ip:'.$request->ip())->response($aviso),
                    Limit::perHour($porHora)->by($nombre.':ip-hora:'.$request->ip())->response($aviso),
                    Limit::perMinute(5)->by($nombre.':cuenta:'.$clave($request, 'input_type', 'email', 'username'))->response($aviso),
                ];
            });
        }

        // Subida de los datos KC (/datos-kc): 5 por minuto y 30 por día desde una IP.
        RateLimiter::for('datos-kc', function (Request $request) use ($demasiados) {
            $aviso = $demasiados('file', 'Hiciste varios intentos seguidos. Esperá un rato antes de volver a intentar.');

            return [
                Limit::perMinute(5)->by('datos-kc:ip:'.$request->ip())->response($aviso),
                Limit::perDay(30)->by('datos-kc:ip-dia:'.$request->ip())->response($aviso),
            ];
        });

        // Newsletter (hoy sin formulario en el sitio): 5 por minuto y 20 por día desde una IP.
        RateLimiter::for('newsletter', function (Request $request) use ($demasiados) {
            $aviso = $demasiados('mail', 'Hiciste varios intentos seguidos. Esperá un rato antes de volver a intentar.');

            return [
                Limit::perMinute(5)->by('newsletter:ip:'.$request->ip())->response($aviso),
                Limit::perDay(20)->by('newsletter:ip-dia:'.$request->ip())->response($aviso),
            ];
        });
    }
}
