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

/** Acuse de recibo para quien escribió desde /contacto. Si lo responde, le llega a BMH. */
final class ContactoAcuse extends Mailable
{
    /** @param  list<string>  $responderA  Los destinatarios de los avisos de BMH. */
    public function __construct(
        public readonly ContactoMensaje $mensaje,
        public readonly ?Contacto $contacto = null,
        public readonly array $responderA = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), MailMarca::NOMBRE),
            replyTo: array_map(fn (string $email) => new Address($email, MailMarca::NOMBRE), array_slice($this->responderA, 0, 1)),
            subject: 'Recibimos tu consulta · '.MailMarca::NOMBRE,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.contacto.acuse', text: 'mail.contacto.acuse-texto');
    }
}
