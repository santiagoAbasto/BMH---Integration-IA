<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

/**
 * El margen de «Emparejar bordes», que se elige en la Biblioteca de imágenes: cuánto aire queda a cada lado del
 * producto, en % del lado de la foto. Uno para las fotos de productos y otro para las de categorías (la tarjeta de la
 * categoría recorta la foto, por eso su margen de fábrica es mayor).
 *
 * Se guarda en un archivo JSON (storage/app), sin tabla: así funciona igual en la web actual y en el rediseño.
 */
final class AjustesBordes
{
    public const PRODUCTO = 'producto';

    public const CATEGORIA = 'categoria';

    /** Margen de fábrica (%, a cada lado): con estos valores, las fotos emparejadas se llaman «…-parejo.webp». */
    public const DEFECTO = [self::PRODUCTO => 8, self::CATEGORIA => 16];

    public const LIMITES = [self::PRODUCTO => [2, 25], self::CATEGORIA => [5, 30]];

    /** @return array{producto: int, categoria: int} */
    public static function actuales(): array
    {
        $guardados = [];
        $ruta = self::ruta();
        if (is_file($ruta)) {
            $guardados = json_decode((string) @file_get_contents($ruta), true);
            $guardados = is_array($guardados) ? $guardados : [];
        }

        $margenes = [];
        foreach (self::DEFECTO as $perfil => $defecto) {
            $margenes[$perfil] = self::acotar($perfil, (int) ($guardados[$perfil] ?? $defecto));
        }

        return $margenes;
    }

    public static function margen(string $perfil): int
    {
        return self::actuales()[$perfil] ?? self::DEFECTO[self::PRODUCTO];
    }

    /** @param array{producto?: int, categoria?: int} $margenes */
    public static function guardar(array $margenes): void
    {
        $nuevos = array_merge(self::actuales(), array_intersect_key($margenes, self::DEFECTO));
        foreach ($nuevos as $perfil => $valor) {
            $nuevos[$perfil] = self::acotar($perfil, (int) $valor);
        }
        $ruta = self::ruta();
        if (! is_dir(dirname($ruta))) {
            @mkdir(dirname($ruta), 0775, true);
        }
        file_put_contents($ruta, json_encode($nuevos, JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Cuánto ocupa el producto con el margen de categorías, respecto del de fábrica (1 = como siempre). Escala el
     * ancho, el alto y la superficie máximos del perfil de categoría.
     */
    public static function escalaCategoria(?int $margen = null): float
    {
        $margen ??= self::margen(self::CATEGORIA);

        return (100 - 2 * $margen) / (100 - 2 * self::DEFECTO[self::CATEGORIA]);
    }

    public static function ruta(): string
    {
        return (string) config('imagenes.ajustes_bordes', storage_path('app/ajustes-bordes.json'));
    }

    private static function acotar(string $perfil, int $valor): int
    {
        [$min, $max] = self::LIMITES[$perfil];

        return max($min, min($max, $valor));
    }
}
