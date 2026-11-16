<?php

namespace App\Mail;

use App\Support\MailMarca;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mail al cliente con un texto que se carga en el panel (Mails: bienvenida al registrarse y aviso de cuenta
 * habilitada), dentro del formato de BMH.
 */
class CompraMail extends Mailable
{
    public function __construct(public $subject, public $content, public $data = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), MailMarca::NOMBRE),
            subject: (string) $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.mensaje',
            text: 'mail.mensaje-texto',
            with: ['titulo' => (string) $this->subject, 'contenido' => (string) $this->content],
        );
    }
}
