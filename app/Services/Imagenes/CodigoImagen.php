<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use Illuminate\Support\Str;

/**
 * Formato de los códigos de la biblioteca de imágenes: letras, números,
 * guion, guion bajo y punto, en mayúsculas. Nunca comas ni punto y coma,
 * porque separan los códigos en la columna IMÁGENES del Excel.
 *
 * Los códigos de producto pasan por el mismo formato: «LASX 43-48» → LASX-43-48.
 */
final class CodigoImagen
{
    public const LARGO_MAXIMO = 60;

    public const PREFIJO_GENERICO = 'IMG-';

    public static function normalizar(?string $codigo): string
    {
        $codigo = Str::ascii(trim((string) $codigo));
        $codigo = mb_strtoupper($codigo);
        $codigo = (string) preg_replace('/[^A-Z0-9._-]+/', '-', $codigo);
        $codigo = (string) preg_replace('/-{2,}/', '-', $codigo);
        $codigo = trim($codigo, '-.');

        return mb_substr($codigo, 0, self::LARGO_MAXIMO);
    }

    /** ¿Ya tiene el formato válido? (Para avisar al renombrar en vez de cambiarlo en silencio.) */
    public static function esValido(string $codigo): bool
    {
        return $codigo !== '' && self::normalizar($codigo) === $codigo;
    }
}
