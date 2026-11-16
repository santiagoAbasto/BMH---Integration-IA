<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AvisoClienteNuevo;
use App\Mail\AvisoPedidoNuevo;
use App\Models\Contacto;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos a BMH por email: un cliente que se registró y un pedido nuevo. Van a las cuentas de Contacto › Contenido ›
 * Avisos por email (cada lista vacía usa la de las consultas). Nunca lanza: el registro o el pedido ya quedaron
 * guardados y se ven en el panel.
 */
final class AvisosBmh
{
    public function __construct(private readonly EnvioMail $envio) {}

    public function clienteNuevo(User $cliente): bool
    {
        $destinatarios = Contacto::actual()->destinatariosRegistro();
        if ($destinatarios === []) {
            Log::warning('Cliente nuevo sin destinatario para el aviso: cargá un email en Contacto › Contenido › Avisos por email.', ['user_id' => $cliente->id]);

            return false;
        }

        return $this->envio->enviar(fn () => Mail::to($destinatarios)->send(new AvisoClienteNuevo($cliente)), 'aviso de cliente nuevo', ['user_id' => $cliente->id]);
    }

    public function pedidoNuevo(Pedido $pedido, ?UploadedFile $archivo = null, ?string $mensajeEnvio = null): bool
    {
        $destinatarios = Contacto::actual()->destinatariosPedidos();
        if ($destinatarios === []) {
            Log::warning('Pedido sin destinatario para el aviso: cargá un email en Contacto › Contenido › Avisos por email.', ['pedido_id' => $pedido->id]);

            return false;
        }

        return $this->envio->enviar(fn () => Mail::to($destinatarios)->send(new AvisoPedidoNuevo($pedido, $archivo, $mensajeEnvio)), 'aviso de pedido nuevo', ['pedido_id' => $pedido->id]);
    }
}
