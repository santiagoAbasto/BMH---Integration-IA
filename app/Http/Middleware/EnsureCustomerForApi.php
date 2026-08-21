<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Igual que `cliente`, pero para las rutas JSON del asesor.
 *
 * El middleware `cliente` existente redirige a `/`, que es lo correcto para una
 * navegación pero rompe un fetch: el frontend recibiría un 200 con HTML. Acá se
 * devuelve 401 y el chat puede mostrar "tu sesión expiró" en vez de romperse.
 */
final class EnsureCustomerForApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Tu sesión expiró. Volvé a iniciar sesión para seguir.',
                'code'    => 'unauthenticated',
            ], 401);
        }

        if (! in_array($user->rol, ['cliente', 'vendedor'], true)) {
            return response()->json([
                'message' => 'Esta sección es para clientes de BMH.',
                'code'    => 'forbidden',
            ], 403);
        }

        return $next($request);
    }
}
