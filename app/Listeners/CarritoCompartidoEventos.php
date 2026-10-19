<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Carrito\CarritoCompartido;
use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\QueryException;
use Illuminate\Events\Dispatcher;

/**
 * El carrito guardado en la base (CarritoCompartido) al entrar y salir.
 */
class CarritoCompartidoEventos
{
    public function __construct(private readonly CarritoCompartido $carrito)
    {
    }

    /** Lo que agregó como invitado se une con su carrito de los otros dispositivos. */
    public function alIngresar(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        // Un problema con la base no puede impedir que el cliente entre.
        try {
            $this->carrito->unirAlIngresar((int) $event->user->getAuthIdentifier());
        } catch (QueryException $e) {
            report($e);
        }
    }

    /**
     * El logout no invalida la sesión: sin esto el carrito quedaría en el
     * navegador y se uniría al de la próxima persona que entre ahí (una
     * compu compartida, un vendedor). Está guardado en la base: no se pierde.
     */
    public function alSalir(Logout $event): void
    {
        if ($event->guard === 'web') {
            Cart::destroy();
        }
    }

    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'alIngresar',
            Logout::class => 'alSalir',
        ];
    }
}
