<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Contacto;
use App\Models\ContactoRespuesta;
use App\Support\MailMarca;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Respuesta de BMH a una consulta de /contacto, escrita en Contacto › Mensajes. Si la persona contesta el mail, le
 * llega a la cuenta de avisos de consultas.
 */
final class RespuestaContacto extends Mailable
{
    public function __construct(public readonly ContactoRespuesta $respuesta) {}

    public function envelope(): Envelope
    {
        $avisos = Contacto::actual()->destinatariosAviso();

        return new Envelope(
            from: new Address((string) config('mail.from.address'), MailMarca::NOMBRE),
            replyTo: $avisos !== [] ? [new Address($avisos[0], MailMarca::NOMBRE)] : [],
            subject: $this->respuesta->asunto,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contacto.respuesta',
            text: 'mail.contacto.respuesta-texto',
            with: ['mensaje' => $this->respuesta->mensaje],
        );
    }
}
