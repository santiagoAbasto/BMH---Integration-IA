<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

use Illuminate\Support\Facades\DB;

/**
 * Las partes relacionadas van en los dos sentidos: si NFSO42 tiene a 0078,
 * 0078 tiene a NFSO42.
 *
 * Se llama después de guardar la lista de un producto: a las partes que
 * quedaron en la lista se les agrega el producto (al final de su lista) si
 * no lo tenían, y a las que se sacaron se les saca. Así la relación no queda
 * de un solo lado ni vuelve a aparecer al guardar la otra parte.
 */
final class PartesReciprocas
{
    /**
     * @param  list<int>  $partes    las partes que quedaron en la lista del producto
     * @param  list<int>  $quitadas  las que se sacaron en este guardado
     */
    public function sincronizar(int $productoId, array $partes, array $quitadas): void
    {
        $quitadas = array_values(array_diff(array_map('intval', $quitadas), [$productoId]));
        if ($quitadas !== []) {
            DB::table('partes_relacionadas')
                ->whereIn('producto_id', $quitadas)
                ->where('parte_id', $productoId)
                ->delete();
        }

        $partes = array_values(array_unique(array_diff(array_map('intval', $partes), [$productoId])));
        if ($partes === []) {
            return;
        }

        // Una sola consulta para todas: cuántas partes tiene cada una y si ya
        // tiene al producto. Las que no tienen ninguna parte no aparecen.
        $listas = DB::table('partes_relacionadas')
            ->whereIn('producto_id', $partes)
            ->groupBy('producto_id')
            ->selectRaw('producto_id, COUNT(*) AS cantidad, SUM(CASE WHEN parte_id = ? THEN 1 ELSE 0 END) AS ya_lo_tiene', [$productoId])
            ->get()
            ->keyBy('producto_id');

        $ahora = now();
        $filas = [];
        foreach ($partes as $parteId) {
            $lista = $listas->get($parteId);
            if ($lista !== null && (int) $lista->ya_lo_tiene > 0) {
                continue;
            }
            $filas[] = [
                'producto_id' => $parteId,
                'parte_id' => $productoId,
                // Al final de su lista: el orden es la posición en códigos de
                // dos letras (aa, ab, …), como lo arma el formulario.
                'orden' => self::codigoOrden($lista === null ? 0 : (int) $lista->cantidad),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        if ($filas !== []) {
            // insertOrIgnore: si otro guardado la agregó recién, la clave única
            // (producto_id, parte_id) la descarta en vez de romper el guardado.
            DB::table('partes_relacionadas')->insertOrIgnore($filas);
        }
    }

    /** Posición → código de dos letras (aa, ab, …, zz), igual que ProductoController::codigoOrden. */
    private static function codigoOrden(int $i): string
    {
        $i = max(0, min($i, 675)); // 26 * 26 - 1

        return chr(97 + intdiv($i, 26)) . chr(97 + $i % 26);
    }
}
