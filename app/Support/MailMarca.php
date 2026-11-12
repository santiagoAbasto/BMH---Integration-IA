<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Contacto;
use App\Models\Imagen;
use Illuminate\Mail\Message;
use Throwable;

/**
 * Marca de los emails de BMH (components/mail/layout), como MailBrand en Conelec.
 *
 * El logo va embebido en el mail (CID) y no como link: se ve aunque el cliente de correo bloquee las imágenes
 * externas y no depende de que el sitio esté en línea. Es el logo blanco del header (sector `logo`, PNG de
 * 360 × 90 que se muestra a 180 px: nítido en pantallas retina) sobre la franja tinta del encabezado. Gmail,
 * Outlook y Apple Mail no muestran SVG ni WebP.
 */
final class MailMarca
{
    public const NOMBRE = 'BMH Distribuidora';

    public const TINTA = '#0a0e14';

    public const AZUL = '#0098da';

    /** Azul para texto y botones con texto blanco (5:1); el #0098da queda para acentos. */
    public const AZUL_TEXTO = '#0076a8';

    public const FUENTE_TITULOS = "Montserrat,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    public const FUENTE_TEXTO = "Inter,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    /** CID ya embebido en cada mensaje: Blade evalúa los atributos del componente más de una vez. */
    private static ?\WeakMap $embebidos = null;

    /** "cid:…" al enviar; la URL pública al previsualizar el mail en el navegador; null si no hay logo. */
    public static function logo(?Message $message = null): ?string
    {
        $archivo = self::archivoLogo();
        if ($archivo === null) {
            return null;
        }

        if ($message === null) {
            return asset('imagenes/'.basename($archivo));
        }

        self::$embebidos ??= new \WeakMap;

        return self::$embebidos[$message] ??= $message->embed($archivo);
    }

    /** Pie de los emails: la marca, la dirección y el teléfono cargados en Contacto › Contenido. */
    public static function pie(): string
    {
        $contacto = self::contacto();

        return collect([self::NOMBRE, $contacto?->direccion, $contacto?->tel])->filter()->implode(' · ');
    }

    public static function contacto(): ?Contacto
    {
        try {
            return Contacto::query()->find(1);
        } catch (Throwable) {
            return null;
        }
    }

    private static function archivoLogo(): ?string
    {
        try {
            $path = Imagen::query()->where('sector', 'logo')->value('path');
        } catch (Throwable) {
            return null;
        }

        $archivo = $path ? public_path('imagenes/'.$path) : null;

        return $archivo && is_file($archivo) ? $archivo : null;
    }
}
