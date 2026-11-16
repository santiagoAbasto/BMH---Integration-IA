<?php

namespace App\Mail;

use App\Models\Pedido;
use App\Support\MailMarca;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Confirmación al cliente de su pedido (formato BMH): el detalle, el total y cómo sigue la entrega. */
class PedidoClienteMail extends Mailable
{
    public function __construct(public Pedido $data, public ?string $mensaje = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), MailMarca::NOMBRE),
            subject: 'Recibimos tu pedido #'.$this->data->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.pedido.cliente',
            text: 'mail.pedido.cliente-texto',
            with: ['pedido' => $this->data, 'mensajeEnvio' => $this->mensaje],
        );
    }
}
