<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Carrito\CarritoCompartido;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * El carrito del cliente logueado sale de la base en cada request y vuelve
 * a la base si el request lo cambió: es el mismo en todos sus dispositivos.
 * Los invitados siguen con el de la sesión; al loguearse se une
 * (CarritoCompartido::unirAlIngresar, desde el evento Login).
 */
class SincronizarCarrito
{
    public function __construct(private readonly CarritoCompartido $carrito)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userId = Auth::guard('web')->id();
        if ($userId === null) {
            try {
                $this->carrito->quitarProductosInexistentes();
            } catch (QueryException $e) {
                report($e);
            }

            return $next($request);
        }

        // Si la base falla, el carrito sigue andando como antes (en la sesión)
        // y el error queda en el log: esto no puede tirar abajo la página.
        try {
            $this->carrito->cargar((int) $userId);
            // Un producto que se borró del admin sale también del carrito guardado.
            if ($this->carrito->quitarProductosInexistentes()) {
                $this->carrito->guardar((int) $userId);
            }
        } catch (QueryException $e) {
            report($e);

            return $next($request);
        }
        $antes = $this->carrito->huella();

        $response = $next($request);

        // Si cerró sesión en este request, su carrito guardado queda como estaba.
        if (Auth::guard('web')->id() === $userId && $this->carrito->huella() !== $antes) {
            try {
                $this->carrito->guardar((int) $userId);
            } catch (QueryException $e) {
                report($e);
            }
        }

        return $response;
    }
}
