<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dónde se usa cada archivo de public/imagenes en la base.
 *
 * Recorre todas las columnas de texto, no una lista fija: si mañana otra tabla
 * guarda imágenes, el optimizador la tiene en cuenta sin tocar código.
 */
final class ReferenciasImagenes
{
    private const EXTENSIONES = ['.png', '.jpg', '.jpeg', '.webp', '.gif'];

    /**
     * Columnas que tienen al menos un nombre de imagen.
     *
     * @return list<string> "tabla.columna"
     */
    public function columnasConReferencias(): array
    {
        $encontradas = [];

        foreach ($this->columnasDeTexto() as [$tabla, $columna]) {
            $hay = DB::table($tabla)->where(function ($q) use ($columna): void {
                foreach (self::EXTENSIONES as $extension) {
                    $q->orWhere($columna, 'LIKE', '%'.$extension.'%');
                }
            })->exists();

            if ($hay) {
                $encontradas[] = "{$tabla}.{$columna}";
            }
        }

        return $encontradas;
    }

    /**
     * Referencias a un archivo: cuántas filas lo tienen como valor exacto en
     * cada columna, y si aparece metido dentro de otro texto (HTML, listas),
     * caso en el que no se puede renombrar con seguridad.
     *
     * @param  list<string>  $columnas
     * @return array{exactas: array<string, int>, embebida: bool}
     */
    public function de(string $nombre, array $columnas): array
    {
        $exactas = [];
        $embebida = false;
        $palabra = '/(?<![A-Za-z0-9_\-.])'.preg_quote($nombre, '/').'(?![A-Za-z0-9_\-.])/';
        $like = '%'.addcslashes($nombre, '\\%_').'%';

        foreach ($columnas as $clave) {
            [$tabla, $columna] = explode('.', $clave, 2);

            $exactas[$clave] = DB::table($tabla)->where($columna, $nombre)->count();

            if (! $embebida) {
                $parecidos = DB::table($tabla)->where($columna, 'LIKE', $like)->where($columna, '<>', $nombre)->pluck($columna);
                foreach ($parecidos as $valor) {
                    if (preg_match($palabra, (string) $valor)) {
                        $embebida = true;
                        break;
                    }
                }
            }
        }

        return ['exactas' => $exactas, 'embebida' => $embebida];
    }

    /**
     * Archivos de sectores que no se convierten (logos, favicon).
     *
     * @return list<string>
     */
    public function excluidasPorSector(): array
    {
        $sectores = config('imagenes.sectores_excluidos', []);
        if ($sectores === [] || ! Schema::hasTable('imagenes')) {
            return [];
        }

        return DB::table('imagenes')->whereIn('sector', $sectores)->pluck('path')->filter()->values()->all();
    }

    /** @return list<array{string, string}> [tabla, columna] */
    private function columnasDeTexto(): array
    {
        if (DB::getDriverName() === 'mysql') {
            // Sólo tablas: una vista que muestre imagenes.path contaría la
            // misma referencia dos veces y el control de filas fallaría.
            $filas = DB::select(
                "SELECT c.TABLE_NAME AS t, c.COLUMN_NAME AS c FROM information_schema.COLUMNS c
                 JOIN information_schema.TABLES tb ON tb.TABLE_SCHEMA = c.TABLE_SCHEMA AND tb.TABLE_NAME = c.TABLE_NAME
                 WHERE c.TABLE_SCHEMA = ? AND tb.TABLE_TYPE = 'BASE TABLE'
                   AND c.DATA_TYPE IN ('char','varchar','tinytext','text','mediumtext','longtext')",
                [DB::getDatabaseName()]
            );

            return array_map(fn ($f) => [$f->t, $f->c], $filas);
        }

        $columnas = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'") as $tabla) {
            foreach (Schema::getColumnListing($tabla->name) as $columna) {
                $columnas[] = [$tabla->name, $columna];
            }
        }

        return $columnas;
    }
}
