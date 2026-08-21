<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PedidoEmpresaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $archivo;
    public $mensaje;

    /**
     * Create a new message instance.
     */
    public function __construct($data, $archivo, $mensaje)
    {
        $this->data = $data;
        $this->archivo = $archivo;
        $this->mensaje = $mensaje;

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo pedido (Orden #'.$this->data->id.')',
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
                'empresa' => 1

            ],
        );
    }

    public function build()
    {
        $mail = $this->view('pedido-mail');
    
        if (isset($this->archivo) && $this->archivo) {
            $mail->attach($this->archivo->getRealPath(), [
                'as' => $this->archivo->getClientOriginalName(),
                'mime' => $this->archivo->getClientMimeType(),
            ]);
        }
        
        return $mail;
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
