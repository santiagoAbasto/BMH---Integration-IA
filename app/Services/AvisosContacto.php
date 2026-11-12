<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ContactoAcuse;
use App\Mail\ContactoAviso;
use App\Models\Contacto;
use App\Models\ContactoMensaje;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Los mails de cada consulta de /contacto: el aviso a BMH (a los emails de Contacto › Contenido o, si no hay, al
 * email de contacto del sitio) y el acuse de recibo a quien escribió. Un SMTP caído no pierde la consulta: ya
 * quedó guardada en Contacto › Mensajes; el error se loguea (sin datos personales) y se sigue.
 */
final class AvisosContacto
{
    public function enviar(ContactoMensaje $mensaje): void
    {
        $contacto = Contacto::query()->find(1) ?? new Contacto();
        $destinatarios = $contacto->destinatariosAviso();

        if ($destinatarios === []) {
            Log::warning('Consulta de contacto sin destinatario para el aviso: cargá un email en Contacto › Contenido.', ['mensaje_id' => $mensaje->id]);
        } elseif ($this->mandar(fn () => Mail::to($destinatarios)->send(new ContactoAviso($mensaje, $contacto)), 'aviso', $mensaje) && $mensaje->exists) {
            $mensaje->forceFill(['aviso_enviado_at' => now()])->saveQuietly();
        }

        if ($contacto->mandaAcuse()) {
            $this->mandar(fn () => Mail::to($mensaje->email, $mensaje->nombre)->send(new ContactoAcuse($mensaje, $contacto, $destinatarios)), 'acuse', $mensaje);
        }
    }

    /** Cierra la conexión SMTP para que el reintento abra una nueva (tras un 550 la sesión puede quedar rechazada). */
    private function reconectar(): bool
    {
        try {
            $transporte = Mail::mailer()->getSymfonyTransport();
            if (method_exists($transporte, 'stop')) {
                $transporte->stop();
            }
        } catch (Throwable) {
            // Sin transporte SMTP (log, array): se reintenta igual.
        }

        return true;
    }

    private function mandar(callable $envio, string $tipo, ContactoMensaje $mensaje): bool
    {
        try {
            // Dos reintentos (a los 2 y 5 segundos) si el SMTP lo rechaza de momento (p. ej. «demasiados mails por segundo»):
            // corre después de responder, así que no demora a quien escribió.
            retry([2000, 5000], $envio, 0, fn (Throwable $e) => $e instanceof TransportExceptionInterface && $this->reconectar());

            return true;
        } catch (Throwable $e) {
            Log::error("No se pudo enviar el {$tipo} de una consulta de contacto.", [
                'mensaje_id' => $mensaje->id,
                'error' => $e::class.': '.\Illuminate\Support\Str::limit($e->getMessage(), 300),
            ]);

            return false;
        }
    }
}
