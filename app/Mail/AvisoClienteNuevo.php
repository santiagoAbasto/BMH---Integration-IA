<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use App\Support\MailMarca;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso a BMH: un cliente se creó una cuenta en la Zona de Clientes (queda para habilitar en el panel). */
final class AvisoClienteNuevo extends Mailable
{
    public function __construct(public readonly User $cliente) {}

    public function envelope(): Envelope
    {
        $replyTo = filter_var($this->cliente->email, FILTER_VALIDATE_EMAIL) ? [new Address($this->cliente->email, (string) $this->cliente->name)] : [];

        return new Envelope(
            from: new Address((string) config('mail.from.address'), MailMarca::NOMBRE.' · Web'),
            replyTo: $replyTo,
            subject: 'Cliente nuevo: '.$this->cliente->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.avisos.cliente-nuevo', text: 'mail.avisos.cliente-nuevo-texto');
    }
}
