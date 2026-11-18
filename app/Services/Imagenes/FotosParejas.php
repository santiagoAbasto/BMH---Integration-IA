<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use Illuminate\Support\Facades\DB;

/**
 * Emparejar bordes y volver a la original, con la base al día: donde se usaba la foto (la ficha del producto, la
 * biblioteca de imágenes, la categoría…) pasa a usarse la otra. Ningún archivo se borra: la original queda para
 * poder volver, y la emparejada queda por si se vuelve a emparejar.
 */
final class FotosParejas
{
    /** Columnas que nombran una imagen pero no la usan (el nombre con el que se subió): no se cambian. */
    private const NO_SON_USOS = ['biblioteca_imagenes.nombre_original'];

    /** @var list<string>|null "tabla.columna" que guardan nombres de imágenes (se calcula una vez por request) */
    private ?array $columnas = null;

    public function __construct(
        private readonly EmparejarBordes $emparejar,
        private readonly ReferenciasImagenes $referencias,
    ) {
    }

    /** Empareja la foto y la reemplaza donde se use. Devuelve el nombre nuevo. */
    public function emparejar(string $nombre, string $perfil): string
    {
        $pareja = $this->emparejar->emparejar($nombre, $perfil);
        if ($pareja !== $nombre) {
            $this->mover($nombre, $pareja);
            // Rehecha con otro margen: la emparejada anterior ya no la usa nadie (la original sí queda siempre).
            if (EmparejarBordes::esPareja($nombre)) {
                $this->borrarSiNoSeUsa($nombre);
            }
        }

        return $pareja;
    }

    /** Vuelve a la foto original donde se use la emparejada. Devuelve el nombre de la original. */
    public function deshacer(string $pareja): string
    {
        $original = $this->emparejar->original($pareja) ?? throw new NoSePuedeEmparejar('No se encontró la foto original.');
        $this->mover($pareja, $original);

        return $original;
    }

    private function borrarSiNoSeUsa(string $nombre): void
    {
        $this->columnas ??= array_values(array_diff($this->referencias->columnasConReferencias(), self::NO_SON_USOS));
        if (array_sum($this->referencias->de($nombre, $this->columnas)['exactas']) === 0) {
            @unlink(rtrim((string) config('imagenes.directorio'), '/\\').DIRECTORY_SEPARATOR.basename($nombre));
        }
    }

    private function mover(string $de, string $a): void
    {
        $this->columnas ??= array_values(array_diff($this->referencias->columnasConReferencias(), self::NO_SON_USOS));
        $usos = $this->referencias->de($de, $this->columnas);
        if ($usos['embebida']) {
            throw new NoSePuedeEmparejar('La foto también se usa dentro de un texto: no se puede cambiar sola.');
        }

        DB::transaction(function () use ($usos, $de, $a): void {
            foreach ($usos['exactas'] as $clave => $cantidad) {
                if ($cantidad > 0) {
                    [$tabla, $columna] = explode('.', $clave, 2);
                    DB::table($tabla)->where($columna, $de)->update([$columna => $a]);
                }
            }
        });

        if (($usos['exactas']['biblioteca_imagenes.path'] ?? 0) > 0) {
            $ruta = rtrim((string) config('imagenes.directorio'), '/\\').DIRECTORY_SEPARATOR.$a;
            $medidas = @getimagesize($ruta);
            DB::table('biblioteca_imagenes')->where('path', $a)->update([
                'bytes' => @filesize($ruta) ?: null, 'ancho' => $medidas[0] ?? null, 'alto' => $medidas[1] ?? null, 'updated_at' => now(),
            ]);
        }

        // Una columna que antes no tenía imágenes (p. ej. la primera categoría con foto): la próxima vez se recalcula.
        if (array_sum($usos['exactas']) === 0) {
            $this->columnas = null;
        }
    }
}
