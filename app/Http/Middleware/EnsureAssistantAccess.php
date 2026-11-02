<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Ai\AssistantAccess;
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
        abort_unless(AssistantAccess::permite(Auth::guard('web')->user()), 404);

        return $next($request);
    }
}
