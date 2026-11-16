<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Envía un mail sin romper lo que lo dispara (un pedido, un registro, una respuesta): dos reintentos si el SMTP lo
 * rechaza de momento («demasiados mails por segundo») y, si igual falla, lo deja en el log sin datos personales.
 */
final class EnvioMail
{
    /** @param  array<string, mixed>  $contexto  Ids para encontrar el caso en el log (nunca emails ni nombres). */
    public function enviar(callable $envio, string $que, array $contexto = []): bool
    {
        try {
            retry([2000, 5000], $envio, 0, fn (Throwable $e) => $e instanceof TransportExceptionInterface && $this->reconectar());

            return true;
        } catch (Throwable $e) {
            Log::error("No se pudo enviar el mail: {$que}.", $contexto + ['error' => $e::class.': '.Str::limit($e->getMessage(), 300)]);

            return false;
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
}
