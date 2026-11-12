<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Filtro «Por equivalencia» del buscador (sitio público y Zona de Clientes).
 *
 * Busca un código sólo en los campos que son equivalencias de verdad:
 *
 *  - las características de equivalencia (CODIGO GV, EQUIVALENCIA NOSSO…) que
 *    la categoría del producto declara: las mismas que el sitio muestra
 *    (Producto::caracteristicasVisibles). Un valor que el sitio no muestra no
 *    debe traer resultados que el visitante no pueda entender;
 *  - la tabla `equivalencias`.
 *
 * Aplicaciones y partes relacionadas no son equivalencias: esas las cubre el
 * buscador principal.
 *
 * La coincidencia es parcial, pero cada producto lleva el grado de su mejor
 * coincidencia para ordenar primero las exactas.
 */
final class BusquedaPorEquivalencia
{
    public const EXACTA = 0;

    public const EMPIEZA = 1;

    public const CONTIENE = 2;

    /**
     * Separadores entre varios códigos cargados en un mismo campo. La barra y
     * el guion sólo separan con espacios al lado: en «CA-988/1» son parte del
     * código, en «1006210111 -1006209818» separan dos códigos.
     */
    private const SEPARADORES = '/[,;|\n]+|\s+\/\s*|\s*\/\s+|\s+-\s*|\s*-\s+/';

    /** ¿La característica con este nombre guarda una equivalencia? */
    public static function esCampoDeEquivalencia(?string $nombre): bool
    {
        $nombre = strtoupper(trim(Str::ascii((string) $nombre)));

        if ($nombre === '') {
            return false;
        }

        return str_starts_with($nombre, 'EQUIVALEN')        // EQUIVALENCIA X, EQUIVALENTE
            || str_starts_with($nombre, 'REEMPLAZ')         // REEMPLAZA A / REEMPLAZO POR
            || (bool) preg_match('/^N\S{0,2}\s*ORIGINAL|^NUMERO\s+ORIGINAL/', $nombre)
            // CODIGO GV, CODIGO IMPULSOR ZEN, CODIGO EQUIVALENTE… pero no
            // CODIGO DE BARRAS ni CODIGO DE MOTOR.
            || (bool) preg_match('/^CODIGO\s+(?!DE\s)/', $nombre);
    }

    /** Minúsculas y sin espacios, guiones, puntos ni barras: «RNI 1690» = «rni1690». */
    public static function normalizar(string $valor): string
    {
        return mb_strtolower((string) preg_replace('/[\s\-.\/]+/u', '', $valor));
    }

    /**
     * Productos con alguna equivalencia que contiene el término, y qué tan
     * buena es la mejor coincidencia de cada uno.
     *
     * @return array<int, int> producto_id => EXACTA | EMPIEZA | CONTIENE
     */
    public function coincidencias(string $termino): array
    {
        $buscado = self::normalizar($termino);

        if ($buscado === '') {
            return [];
        }

        $like = '%'.addcslashes($buscado, '\\%_').'%';
        $grados = [];

        $anotar = function (int $productoId, ?string $valor) use (&$grados, $buscado): void {
            $grado = $this->grado((string) $valor, $buscado);
            if ($grado !== null && (! isset($grados[$productoId]) || $grado < $grados[$productoId])) {
                $grados[$productoId] = $grado;
            }
        };

        foreach ($this->enCaracteristicas($like) as $fila) {
            $anotar((int) $fila->producto_id, $fila->valor);
        }

        foreach (DB::table('equivalencias')->whereRaw(self::sqlNormalizado('valor').' LIKE ?', [$like])->get(['producto_id', 'valor']) as $fila) {
            $anotar((int) $fila->producto_id, $fila->valor);
        }

        return $grados;
    }

    /**
     * Deja en la consulta sólo los productos que coinciden.
     *
     * @param  array<int, int>  $coincidencias
     */
    public function filtrar(EloquentBuilder|QueryBuilder $query, array $coincidencias): void
    {
        if ($coincidencias === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('productos.id', array_keys($coincidencias));
    }

    /**
     * Ordena exactas, después las que empiezan con el término y al final las
     * que lo contienen. Va antes de cualquier otro orden de la consulta.
     *
     * @param  array<int, int>  $coincidencias
     */
    public function ordenar(EloquentBuilder|QueryBuilder $query, array $coincidencias): void
    {
        $porGrado = [];
        foreach ($coincidencias as $productoId => $grado) {
            $porGrado[$grado][] = (int) $productoId;
        }

        if (count($porGrado) < 2) {
            return;
        }

        $casos = [];
        foreach ([self::EXACTA, self::EMPIEZA] as $grado) {
            if (! empty($porGrado[$grado])) {
                $casos[] = 'WHEN productos.id IN ('.implode(',', $porGrado[$grado]).') THEN '.$grado;
            }
        }

        $query->orderByRaw('CASE '.implode(' ', $casos).' ELSE '.self::CONTIENE.' END');
    }

    /** Puntaje para sumar al orden por relevancia del buscador público. */
    public static function puntaje(?int $grado): int
    {
        return match ($grado) {
            self::EXACTA => 3000,
            self::EMPIEZA => 2000,
            self::CONTIENE => 1000,
            default => 0,
        };
    }

    // ------------------------------------------------------------ fuentes

    /**
     * Valores de características de equivalencia que la categoría del producto declara.
     *
     * @return iterable<object{producto_id: int, valor: string}>
     */
    private function enCaracteristicas(string $like): iterable
    {
        $ids = DB::table('caracteristicas')->get(['id', 'nombre'])
            ->filter(fn ($c) => self::esCampoDeEquivalencia($c->nombre))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('caracteristica_producto as cp')
            ->join('productos as p', 'p.id', '=', 'cp.producto_id')
            ->join('caracteristica_categoria as cc', function ($join): void {
                $join->on('cc.categoria_id', '=', 'p.categoria_id')
                    ->on('cc.caracteristica_id', '=', 'cp.caracteristica_id');
            })
            ->whereIn('cp.caracteristica_id', $ids)
            ->whereRaw(self::sqlNormalizado('cp.valor').' LIKE ?', [$like])
            ->get(['cp.producto_id', 'cp.valor']);
    }

    // ---------------------------------------------------------- utilidades

    /** La misma normalización que normalizar(), del lado de la base. */
    private static function sqlNormalizado(string $columna): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE({$columna}, ' ', ''), '-', ''), '.', ''), '/', ''))";
    }

    /**
     * Qué tan bien coincide un campo con el término. Un campo puede tener
     * varios códigos: se mira cada uno por separado, así una coincidencia que
     * cruza de un código al siguiente no cuenta (la base la trae porque
     * compara el campo entero sin espacios ni guiones).
     */
    private function grado(string $valor, string $buscado): ?int
    {
        $partes = array_filter(array_map([self::class, 'normalizar'], preg_split(self::SEPARADORES, $valor) ?: []));

        $mejor = null;
        foreach ($partes as $parte) {
            $grado = match (true) {
                $parte === $buscado => self::EXACTA,
                str_starts_with($parte, $buscado) => self::EMPIEZA,
                str_contains($parte, $buscado) => self::CONTIENE,
                default => null,
            };
            if ($grado !== null && ($mejor === null || $grado < $mejor)) {
                $mejor = $grado;
            }
        }

        return $mejor;
    }
}
