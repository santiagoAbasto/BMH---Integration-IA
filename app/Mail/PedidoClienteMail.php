<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PedidoClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $mensaje;

    /**
     * Create a new message instance.
     */
    public function __construct($data, $mensaje)
    {
        $this->data = $data;
        $this->mensaje = $mensaje;

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Gracias por tu compra!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'pedido-mail',
            with: [
                'pedido' => $this->data,
                'mensaje' => $this->mensaje,

            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
