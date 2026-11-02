<?php

declare(strict_types=1);

namespace App\Services\Ai\Support;

use Illuminate\Support\Facades\Log;

/**
 * Prepara imágenes para mandarle al modelo.
 *
 * Las fotos del catálogo pesan hasta 4,5 MB. Mandar cuatro de esas en base64 son
 * ~24 MB de subida por comparación: es lo que hacía que el turno se colgara y
 * que PHP se muriera por max_execution_time antes de recibir respuesta.
 *
 * Y era desperdicio puro: con `detail: low` el modelo mira una versión de 512 px
 * de todos modos. Achicar acá no pierde nada —el modelo veía 512 px igual— y
 * baja la subida unas cien veces.
 */
final class ImagePayload
{
    /** Lado máximo en píxeles. Es lo que consume el modo `low` de los modelos. */
    private const LADO_MAXIMO = 512;

    private const CALIDAD_JPEG = 82;

    /** Arriba de esto no vale la pena ni intentar redimensionar en memoria. */
    private const BYTES_MAXIMOS = 25_000_000;

    /**
     * Devuelve [mime, base64] ya reducido.
     *
     * Si el redimensionado falla por cualquier motivo —formato raro, GD
     * ausente, archivo corrupto— se manda el original: es mejor una llamada
     * lenta que ninguna.
     *
     * @return array{0: string, 1: string}
     */
    public static function encode(string $ruta): array
    {
        $mimeOriginal = mime_content_type($ruta) ?: 'image/jpeg';

        $reducida = self::reducir($ruta);

        if ($reducida !== null) {
            return ['image/jpeg', base64_encode($reducida)];
        }

        return [$mimeOriginal, base64_encode((string) file_get_contents($ruta))];
    }

    /** Data URI listo para el campo `image_url` de OpenAI. */
    public static function dataUri(string $ruta): string
    {
        [$mime, $base64] = self::encode($ruta);

        return "data:{$mime};base64,{$base64}";
    }

    /** Sólo el base64, para el `inlineData` de Gemini. */
    public static function base64(string $ruta): string
    {
        return self::encode($ruta)[1];
    }

    /** El mime que corresponde a lo que realmente se va a mandar. */
    public static function mime(string $ruta): string
    {
        return self::encode($ruta)[0];
    }

    /** JPEG reducido en memoria, o null si no se pudo. */
    private static function reducir(string $ruta): ?string
    {
        if (! is_file($ruta) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $bytes = (int) (filesize($ruta) ?: 0);

        if ($bytes === 0 || $bytes > self::BYTES_MAXIMOS) {
            return null;
        }

        $medidas = @getimagesize($ruta);

        if ($medidas === false) {
            return null;
        }

        [$ancho, $alto] = $medidas;

        if ($ancho <= 0 || $alto <= 0) {
            return null;
        }

        // Ya es chica: no se toca. Reencodear sólo agregaría pérdida.
        if ($ancho <= self::LADO_MAXIMO && $alto <= self::LADO_MAXIMO && $bytes < 400_000) {
            return null;
        }

        try {
            $origen = @imagecreatefromstring((string) file_get_contents($ruta));

            if ($origen === false) {
                return null;
            }

            $escala = min(self::LADO_MAXIMO / $ancho, self::LADO_MAXIMO / $alto, 1.0);
            $nuevoAncho = max(1, (int) round($ancho * $escala));
            $nuevoAlto  = max(1, (int) round($alto * $escala));

            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

            // Fondo blanco: los PNG con transparencia si no salen con fondo negro
            // y el modelo ve una silueta en vez de una pieza.
            $blanco = imagecolorallocate($destino, 255, 255, 255);
            imagefilledrectangle($destino, 0, 0, $nuevoAncho, $nuevoAlto, $blanco);

            imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

            ob_start();
            imagejpeg($destino, null, self::CALIDAD_JPEG);
            $salida = (string) ob_get_clean();

            imagedestroy($origen);
            imagedestroy($destino);

            return $salida === '' ? null : $salida;
        } catch (\Throwable $e) {
            Log::warning('bmh.ai.image_payload.resize_failed', [
                'exception' => $e::class,
                'bytes'     => $bytes,
            ]);

            return null;
        }
    }
}
