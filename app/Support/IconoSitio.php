<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Íconos del rediseño (resources/svg/sitio, sacados del Figma) como SVG inline: usan
 * currentColor, así toman el color de donde se ponen (la barra del header en la Home es
 * blanca y en el resto gris, sin dos juegos de archivos).
 */
final class IconoSitio
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function svg(string $nombre): HtmlString
    {
        if (! preg_match('/^[a-z0-9-]+$/', $nombre)) {
            throw new InvalidArgumentException("Ícono inválido: {$nombre}");
        }

        return new HtmlString(self::$cache[$nombre] ??= self::leer($nombre));
    }

    private static function leer(string $nombre): string
    {
        $ruta = resource_path("svg/sitio/{$nombre}.svg");

        if (! is_file($ruta)) {
            throw new InvalidArgumentException("No existe el ícono {$nombre}");
        }

        // Decorativo: el texto de al lado (o el aria-label del link) ya dice qué es.
        return str_replace('<svg ', '<svg aria-hidden="true" focusable="false" ', trim((string) file_get_contents($ruta)));
    }
}
