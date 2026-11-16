<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Pedido;
use App\Support\MailMarca;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Aviso a BMH de un pedido nuevo: detalle, datos de entrega, nota del cliente y el archivo que adjuntó. */
final class AvisoPedidoNuevo extends Mailable
{
    public function __construct(
        public readonly Pedido $pedido,
        public readonly ?UploadedFile $archivo = null,
        public readonly ?string $mensajeEnvio = null,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = filter_var($this->pedido->mail, FILTER_VALIDATE_EMAIL) ? [new Address($this->pedido->mail, (string) $this->pedido->nombre)] : [];

        return new Envelope(
            from: new Address((string) config('mail.from.address'), MailMarca::NOMBRE.' · Pedidos'),
            replyTo: $replyTo,
            subject: 'Pedido nuevo #'.$this->pedido->id.' · '.$this->pedido->nombre,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.avisos.pedido-nuevo', text: 'mail.avisos.pedido-nuevo-texto');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        if ($this->archivo === null || ! $this->archivo->isValid()) {
            return [];
        }

        return [Attachment::fromPath($this->archivo->getRealPath())->as($this->archivo->getClientOriginalName())->withMime((string) $this->archivo->getClientMimeType())];
    }
}
