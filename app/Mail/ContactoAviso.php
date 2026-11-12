<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Contacto;
use App\Models\ContactoMensaje;
use App\Support\MailMarca;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso a BMH de una consulta nueva desde /contacto. «Responder» le escribe directo a quien consultó. */
final class ContactoAviso extends Mailable
{
    public function __construct(public readonly ContactoMensaje $mensaje, public readonly ?Contacto $contacto = null) {}

    public function envelope(): Envelope
    {
        $asunto = ($this->contacto ?? new Contacto())->asuntoAviso();

        return new Envelope(
            from: new Address((string) config('mail.from.address'), MailMarca::NOMBRE.' · Web'),
            replyTo: [new Address($this->mensaje->email, $this->mensaje->nombre)],
            subject: $asunto.': '.($this->mensaje->motivo ? $this->mensaje->motivo.' · ' : '').$this->mensaje->nombre,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contacto.aviso', text: 'mail.contacto.aviso-texto');
    }
}
