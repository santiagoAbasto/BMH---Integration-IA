<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\RespuestaContacto;
use App\Models\Admin;
use App\Models\ContactoMensaje;
use App\Models\ContactoRespuesta;
use Illuminate\Support\Facades\Mail;

/**
 * Responder desde Contacto › Mensajes (como en Conelec y Rapivac): la respuesta queda siempre en la conversación y
 * se manda por email, con el formato de BMH, a quien escribió. Si el email no sale, queda marcada para reenviarla.
 */
final class RespuestasContacto
{
    public function __construct(private readonly EnvioMail $envio) {}

    public function responder(ContactoMensaje $mensaje, string $cuerpo, ?Admin $autor): ContactoRespuesta
    {
        $respuesta = $mensaje->respuestas()->create([
            'admin_id' => $autor?->id,
            'autor' => $autor?->name,
            'asunto' => $mensaje->asuntoRespuesta(),
            'cuerpo' => $cuerpo,
        ]);
        $mensaje->marcarLeido();

        return $this->enviar($respuesta);
    }

    /** Manda (o vuelve a mandar) el email de una respuesta y registra si salió. */
    public function enviar(ContactoRespuesta $respuesta): ContactoRespuesta
    {
        $mensaje = $respuesta->mensaje;
        $salio = $this->envio->enviar(
            fn () => Mail::to($mensaje->email, $mensaje->nombre)->send(new RespuestaContacto($respuesta)),
            'respuesta a una consulta de contacto',
            ['mensaje_id' => $mensaje->id, 'respuesta_id' => $respuesta->id],
        );

        $respuesta->forceFill($salio ? ['enviado_at' => now(), 'fallido_at' => null] : ['fallido_at' => now()])->save();

        return $respuesta;
    }
}
