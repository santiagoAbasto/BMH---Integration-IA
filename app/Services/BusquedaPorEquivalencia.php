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
 *  - las columnas viejas `columna_N` cuyo nombre, en la categoría del producto,
 *    es de equivalencia (CODIGO GV, EQUIVALENCIA NOSSO…). La misma columna
 *    guarda un diámetro en otra categoría, por eso se decide por categoría;
 *  - las características de equivalencia de `producto_caracteristica`, con las
 *    mismas reglas que usa el sitio para mostrarlas (Producto::productCaracteristicas);
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

    /**
     * ¿El campo con este nombre guarda una equivalencia? Vale para las columnas
     * viejas (nombres en `categorias`) y para las características.
     */
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

        foreach ($this->enColumnasViejas($like) as [$productoId, $valor]) {
            $anotar($productoId, $valor);
        }

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
     * Columnas viejas: cada categoría declara qué columnas son equivalencias.
     *
     * @return iterable<array{int, ?string}>
     */
    private function enColumnasViejas(string $like): iterable
    {
        $porCategoria = $this->columnasDeEquivalenciaPorCategoria();

        if ($porCategoria === []) {
            return [];
        }

        $columnas = array_values(array_unique(array_merge(...array_values($porCategoria))));

        $filas = DB::table('productos')
            ->select(['id', 'categoria_id', ...$columnas])
            ->where(function (QueryBuilder $q) use ($porCategoria, $like): void {
                foreach ($porCategoria as $categoriaId => $cols) {
                    $q->orWhere(function (QueryBuilder $w) use ($categoriaId, $cols, $like): void {
                        $w->where('categoria_id', $categoriaId)
                            ->where(function (QueryBuilder $o) use ($cols, $like): void {
                                foreach ($cols as $col) {
                                    $o->orWhereRaw(self::sqlNormalizado($col).' LIKE ?', [$like]);
                                }
                            });
                    });
                }
            })
            ->get();

        $valores = [];
        foreach ($filas as $fila) {
            foreach ($porCategoria[$fila->categoria_id] ?? [] as $col) {
                $valores[] = [(int) $fila->id, $fila->{$col}];
            }
        }

        return $valores;
    }

    /** @return array<int, list<string>> categoria_id => columnas de equivalencia */
    private function columnasDeEquivalenciaPorCategoria(): array
    {
        $mapa = [];

        foreach (DB::table('categorias')->get() as $categoria) {
            foreach ((array) $categoria as $columna => $nombre) {
                if (preg_match('/^columna_\d+$/', $columna) && self::esCampoDeEquivalencia($nombre)) {
                    $mapa[$categoria->id][] = $columna;
                }
            }
        }

        return $mapa;
    }

    /**
     * Características de equivalencia visibles en el sitio: no borradas, la
     * última fila de cada producto/característica, y declaradas por la
     * categoría del producto. Un valor que el sitio no muestra no debe traer
     * resultados que el visitante no pueda entender.
     */
    private function enCaracteristicas(string $like): iterable
    {
        $ids = DB::table('caracteristicas')->get(['id', 'nombre'])
            ->filter(fn ($c) => self::esCampoDeEquivalencia($c->nombre))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        return DB::table('producto_caracteristica as pc')
            ->join('productos as p', 'p.id', '=', 'pc.producto_id')
            ->whereIn('pc.caracteristica_id', $ids)
            ->whereNull('pc.deleted_at')
            ->whereRaw(self::sqlNormalizado('pc.valor').' LIKE ?', [$like])
            ->whereIn('pc.id', function (QueryBuilder $q): void {
                $q->selectRaw('MAX(pc_latest.id)')
                    ->from('producto_caracteristica as pc_latest')
                    ->whereNull('pc_latest.deleted_at')
                    ->whereColumn('pc_latest.producto_id', 'pc.producto_id')
                    ->whereColumn('pc_latest.caracteristica_id', 'pc.caracteristica_id');
            })
            ->whereExists(function (QueryBuilder $q): void {
                $q->selectRaw('1')
                    ->from('categoria_caracteristica as cc')
                    ->whereColumn('cc.categoria_id', 'p.categoria_id')
                    ->whereColumn('cc.caracteristica_id', 'pc.caracteristica_id')
                    ->whereNull('cc.deleted_at');
            })
            ->get(['pc.producto_id', 'pc.valor']);
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
