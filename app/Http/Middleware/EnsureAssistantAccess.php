<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de lanzamiento gradual del asesor.
 *
 * La autenticación y el rol los resuelven `cliente` / `customer.api` antes de
 * llegar acá. Este middleware decide si ESE cliente ya participa de la beta.
 * Un 404 evita exponer la existencia del asesor a cuentas no habilitadas.
 */
final class EnsureAssistantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user === null) {
            abort(404);
        }

        if (config('bmh.assistant_access.mode') === 'all') {
            return $next($request);
        }

        $allowedIds = array_map('intval', (array) config('bmh.assistant_access.customer_ids', []));

        abort_unless(in_array((int) $user->getAuthIdentifier(), $allowedIds, true), 404);

        return $next($request);
    }
}
