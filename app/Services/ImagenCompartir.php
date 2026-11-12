<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SeoSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Imagen para compartir en redes (admin → Metadatos SEO): se recorta al centro a 1200 × 630 (la medida de
 * Facebook, WhatsApp, LinkedIn y X) y se guarda como JPG liviano en public/imagenes/seo.
 */
final class ImagenCompartir
{
    private const ANCHO = 1200;

    private const ALTO = 630;

    public function guardar(UploadedFile $archivo): string
    {
        $origen = imagecreatefromstring((string) file_get_contents($archivo->getRealPath()));
        abort_if($origen === false, 422, 'No se pudo leer la imagen.');

        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $escala = max(self::ANCHO / $ancho, self::ALTO / $alto);
        $recorteAncho = (int) round(self::ANCHO / $escala);
        $recorteAlto = (int) round(self::ALTO / $escala);

        $destino = imagecreatetruecolor(self::ANCHO, self::ALTO);
        // Fondo blanco por si la imagen trae transparencia (el JPG no la tiene).
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
        imagecopyresampled($destino, $origen, 0, 0, intdiv($ancho - $recorteAncho, 2), intdiv($alto - $recorteAlto, 2), self::ANCHO, self::ALTO, $recorteAncho, $recorteAlto);

        $carpeta = public_path('imagenes/'.SeoSetting::DIRECTORY);
        File::ensureDirectoryExists($carpeta);
        $nombre = 'compartir-'.Str::lower(Str::random(12)).'.jpg';
        imagejpeg($destino, $carpeta.'/'.$nombre, 85);

        return SeoSetting::DIRECTORY.'/'.$nombre;
    }

    /** Borra sólo imágenes de la carpeta seo (nunca otra de public/imagenes). */
    public function borrar(?string $path): void
    {
        if ($path !== null && str_starts_with($path, SeoSetting::DIRECTORY.'/') && ! str_contains($path, '..')) {
            File::delete(public_path('imagenes/'.$path));
        }
    }
}
