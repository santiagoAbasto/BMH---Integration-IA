<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filtro anti-bots de los formularios públicos y de los logins (reemplaza al reCAPTCHA, como en Conelec).
 * Se suma al CSRF y a los rate limits de cada ruta (AppServiceProvider::limitesFormularios). Una persona real
 * nunca lo nota. Los campos los agrega el componente <x-anti-bots /> dentro de cada <form>.
 *
 * - Honeypot: el campo `website` está fuera de pantalla y no se puede enfocar. Si llega completo, el envío queda
 *   marcado como trampa ({@see self::isTrapped()}): el controlador responde como si estuviera todo bien (o, en un
 *   login, como un usuario o contraseña incorrectos) pero no guarda, no manda mails ni inicia sesión.
 * - Token de tiempo: `_form_token` es {hora en que se mostró el formulario + un nonce al azar}, encriptado y firmado
 *   con la APP_KEY: no se puede falsificar ni editar. Se rechazan envíos sin token, adulterados, hechos en menos
 *   de MIN_SECONDS, con el formulario abierto más de MAX_SECONDS o con un token ya usado (el nonce se quema al
 *   aceptarse el envío, así un bot no puede llenar el formulario una vez y repetir el POST mil veces).
 * - Origen: se rechazan envíos desde otro sitio (Origin / Sec-Fetch-Site) o sin User-Agent (scripts pelados).
 *
 * El rechazo es siempre el mismo mensaje neutro (422), sea cual sea la causa: el bot no aprende qué lo delató.
 * Cada respuesta lleva un token nuevo en el header X-Form-Token (y en `formToken` si es JSON), para que los
 * formularios por AJAX puedan reintentar sin recargar la página. Los rechazos se loguean sin datos personales.
 *
 * Uso: ->middleware('human-form') · 'human-form:username' (clave del error) · 'human-form:username,excepto-admin'
 * (no se aplica con la sesión del panel iniciada: el alta de usuarios comparte la ruta con el registro público).
 */
final class EnsureHumanFormSubmission
{
    public const HONEYPOT = 'website';

    public const TOKEN = '_form_token';

    public const HEADER = 'X-Form-Token';

    public const MIN_SECONDS = 2;

    public const MAX_SECONDS = 2 * 60 * 60;

    public const MESSAGE = 'No pudimos enviar el formulario. Esperá unos segundos y volvé a intentarlo.';

    private const TRAPPED = 'human_form.trapped';

    private const NONCE_PREFIX = 'human-form:nonce:';

    /** Valor del campo oculto `_form_token` al mostrar el formulario. */
    public static function token(): string
    {
        return Crypt::encryptString(json_encode([
            't' => now()->timestamp,
            'n' => bin2hex(random_bytes(16)),
        ], JSON_THROW_ON_ERROR));
    }

    /** Si el envío cayó en el honeypot: se responde como si estuviera todo bien, pero no se procesa. */
    public static function isTrapped(Request $request): bool
    {
        return (bool) $request->attributes->get(self::TRAPPED, false);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $errorKey = 'formulario', string ...$options): Response
    {
        if (in_array('excepto-admin', $options, true) && Auth::guard('admin')->check()) {
            return $next($request);
        }

        if (filled($request->input(self::HONEYPOT))) {
            $this->log($request, 'honeypot');
            $request->attributes->set(self::TRAPPED, true);

            return $this->withFreshToken($next($request));
        }

        $payload = $this->payload($request->input(self::TOKEN));
        $age = $payload ? now()->timestamp - $payload['t'] : null;

        $reason = match (true) {
            ! $this->comesFromThisSite($request) => 'origin',
            blank($request->userAgent()) => 'user-agent',
            $payload === null => 'token',
            $age < self::MIN_SECONDS => 'too-fast',
            $age > self::MAX_SECONDS => 'expired',
            // Recién acá se quema el nonce: un envío rechazado por rápido puede reintentarse con el mismo token.
            ! Cache::add(self::NONCE_PREFIX.$payload['n'], 1, self::MAX_SECONDS + 60) => 'reused',
            default => null,
        };

        if ($reason === null) {
            return $this->withFreshToken($next($request));
        }

        $this->log($request, $reason);

        $token = self::token();
        $response = $request->expectsJson()
            ? response()->json([
                'success' => false,
                'message' => self::MESSAGE,
                'errors' => [$errorKey => [self::MESSAGE]],
                'formToken' => $token,
            ], 422)
            : back(303)
                ->withErrors([$errorKey => self::MESSAGE])
                ->with('warning', self::MESSAGE)
                ->withInput($request->except(['_token', 'password', 'password_confirmation', self::HONEYPOT, self::TOKEN]));

        $response->headers->set(self::HEADER, $token);

        return $response;
    }

    private function withFreshToken(Response $response): Response
    {
        $response->headers->set(self::HEADER, self::token());

        return $response;
    }

    private function comesFromThisSite(Request $request): bool
    {
        if ($request->headers->get('Sec-Fetch-Site') === 'cross-site') {
            return false;
        }

        $origin = $request->headers->get('Origin');

        // Sin Origin (navegadores viejos) alcanza con el CSRF. Un Origin "null" (iframe sandbox, file://) se rechaza.
        return $origin === null || parse_url($origin, PHP_URL_HOST) === $request->getHost();
    }

    /** @return array{t: int, n: string}|null  null si el token falta o fue adulterado. */
    private function payload(mixed $token): ?array
    {
        if (! is_string($token) || $token === '' || strlen($token) > 1024) {
            return null;
        }

        try {
            $data = json_decode(Crypt::decryptString($token), true, 4, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (! is_array($data) || ! is_int($data['t'] ?? null) || ! is_string($data['n'] ?? null) || ! preg_match('/^[a-f0-9]{32}$/', $data['n'])) {
            return null;
        }

        return ['t' => $data['t'], 'n' => $data['n']];
    }

    /** Sin datos personales: ni lo que se escribió ni la IP (sólo un hash corto, para ver si se repite). */
    private function log(Request $request, string $reason): void
    {
        Log::info('Envío de formulario bloqueado por el filtro anti-bots.', [
            'motivo' => $reason,
            'ruta' => $request->route()?->getName(),
            'origen' => substr(hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')), 0, 12),
        ]);
    }
}
