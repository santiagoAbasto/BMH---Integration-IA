<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

use Illuminate\Support\Facades\DB;

/**
 * Unifica voltaje y diámetro ya cargados al formato de la carga nueva
 * (UnidadesProducto: se escribe el número y se agrega la unidad): «12»,
 * «12V» y «12 V» pasan a «12v»; «60», «60mm» y «60 MM» a «60MM».
 *
 * Sólo toca lo que es un número, con o sin esa unidad. Lo demás (rangos
 * como «12/24V», «128x138», «12 PULGADAS») queda igual y se lista para
 * revisarlo a mano: adivinar qué quiso decir sería arriesgado. Los valores
 * que significan «sin dato» («-», «.») tampoco se tocan.
 *
 * Revisa los dos lugares donde se guardan: las columnas de la categoría
 * (productos.columna_N, con el nombre en categorias.columna_N) y las
 * características (producto_caracteristica).
 */
final class NormalizadorUnidades
{
    /** El valor en el formato de la carga nueva, o null si no es un número con (o sin) esa unidad. */
    public static function canonico(string $valor, string $unidad): ?string
    {
        $patron = '/^\s*(\d+(?:[.,]\d+)?)\s*(?:'.preg_quote($unidad, '/').')?\s*$/i';
        if (preg_match($patron, $valor, $partes) !== 1) {
            return null;
        }

        return self::numero($partes[1]).$unidad;
    }

    /**
     * Un mismo número siempre se escribe igual, así en el filtro del catálogo
     * es una sola opción: coma decimal a punto y sin ceros de más
     * («89,50» → 89.5, «12.0» → 12, «012» → 12). Lo usa también el guardado
     * del admin (UnidadesProducto).
     */
    public static function numero(string $numero): string
    {
        $numero = str_replace(',', '.', trim($numero));
        if (str_contains($numero, '.')) {
            $numero = rtrim(rtrim($numero, '0'), '.');
        }
        $numero = ltrim($numero, '0');

        return $numero === '' || $numero[0] === '.' ? '0'.$numero : $numero;
    }

    /**
     * @param  bool  $aplicar  false: sólo cuenta, no cambia nada
     * @return array{columnas: int, caracteristicas: int, ya_estaban: int, revisar: list<array{codigo: string, nombre: string, campo: string, valor: string}>}
     */
    public function normalizar(bool $aplicar): array
    {
        $resultado = ['columnas' => 0, 'caracteristicas' => 0, 'ya_estaban' => 0, 'revisar' => []];
        $cambiosColumnas = [];
        $cambiosCaracteristicas = [];

        foreach (DB::table('categorias')->get() as $categoria) {
            foreach ((array) $categoria as $campo => $nombre) {
                if (! str_starts_with((string) $campo, 'columna_') || ! is_string($nombre)) {
                    continue;
                }
                $unidad = UnidadesProducto::unidad($nombre);
                if ($unidad === null) {
                    continue;
                }

                $productos = DB::table('productos')->where('categoria_id', $categoria->id)
                    ->whereNotNull($campo)->where($campo, '<>', '')
                    ->get(['id', 'codigo', 'nombre', $campo]);
                foreach ($productos as $producto) {
                    $valor = (string) $producto->{$campo};
                    $nuevo = $this->analizar($valor, $unidad, $resultado, (string) $producto->codigo, (string) $producto->nombre, trim($nombre));
                    if ($nuevo !== null) {
                        $cambiosColumnas[] = [$producto->id, $campo, $nuevo];
                    }
                }
            }
        }

        $filas = DB::table('producto_caracteristica as pc')
            ->join('caracteristicas as c', 'c.id', '=', 'pc.caracteristica_id')
            ->leftJoin('productos as p', 'p.id', '=', 'pc.producto_id')
            ->whereNotNull('pc.valor')->where('pc.valor', '<>', '')
            ->get(['pc.producto_id', 'pc.caracteristica_id', 'pc.valor', 'c.nombre as caracteristica', 'p.codigo', 'p.nombre']);
        foreach ($filas as $fila) {
            $unidad = UnidadesProducto::unidad($fila->caracteristica);
            if ($unidad === null) {
                continue;
            }
            $nuevo = $this->analizar((string) $fila->valor, $unidad, $resultado, (string) $fila->codigo, (string) $fila->nombre, trim((string) $fila->caracteristica));
            if ($nuevo !== null) {
                $cambiosCaracteristicas[] = [$fila->producto_id, $fila->caracteristica_id, (string) $fila->valor, $nuevo];
            }
        }

        $resultado['columnas'] = count($cambiosColumnas);
        $resultado['caracteristicas'] = count($cambiosCaracteristicas);

        if ($aplicar) {
            DB::transaction(function () use ($cambiosColumnas, $cambiosCaracteristicas): void {
                foreach ($cambiosColumnas as [$id, $campo, $nuevo]) {
                    DB::table('productos')->where('id', $id)->update([$campo => $nuevo]);
                }
                foreach ($cambiosCaracteristicas as [$productoId, $caracteristicaId, $viejo, $nuevo]) {
                    DB::table('producto_caracteristica')
                        ->where('producto_id', $productoId)->where('caracteristica_id', $caracteristicaId)->where('valor', $viejo)
                        ->update(['valor' => $nuevo]);
                }
            });
        }

        return $resultado;
    }

    /** El valor nuevo si hay que cambiarlo; null si ya está bien o no se puede (lo anota para revisar). */
    private function analizar(string $valor, string $unidad, array &$resultado, string $codigo, string $nombre, string $campo): ?string
    {
        if (CatalogoFiltrado::normalizar($valor) === '') {
            return null; // «-», «.»: sin dato
        }
        $nuevo = self::canonico($valor, $unidad);
        if ($nuevo === null) {
            $resultado['revisar'][] = ['codigo' => $codigo, 'nombre' => $nombre, 'campo' => $campo, 'valor' => $valor];

            return null;
        }
        if ($nuevo === $valor) {
            $resultado['ya_estaban']++;

            return null;
        }

        return $nuevo;
    }
}
