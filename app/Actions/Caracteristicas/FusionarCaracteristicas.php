<?php

declare(strict_types=1);

namespace App\Actions\Caracteristicas;

use App\Models\Caracteristica;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Juntar dos características que son la misma (p. ej. «DIAMETRO ORIFICIOS - [DA] (MM)» y
 * «DIAMETRO ORIFICIOS -(DA) MM»): la que se elimina pasa sus valores y sus categorías a la que queda.
 *
 * Si un producto tiene las dos cargadas con valores distintos, el usuario elige cuál conservar (lo ve
 * antes en vistaPrevia()). Nada se pierde sin que lo haya visto.
 */
final class FusionarCaracteristicas
{
    public const CONSERVAR_DESTINO = 'destino';

    public const CONSERVAR_ORIGEN = 'origen';

    /**
     * @return array{valores: int, se_pasan: int, iguales: int, conflictos: list<array{codigo: string, nombre: string, origen: string, destino: string}>,
     *               categorias: int, categorias_nuevas: int}
     */
    public function vistaPrevia(Caracteristica $origen, Caracteristica $destino): array
    {
        $this->validar($origen, $destino);
        $destinoValores = $this->valoresDe($destino)->keyBy('producto_id');

        $resultado = ['valores' => 0, 'se_pasan' => 0, 'iguales' => 0, 'conflictos' => [], 'categorias' => 0, 'categorias_nuevas' => 0];
        foreach ($this->valoresDe($origen) as $fila) {
            $resultado['valores']++;
            $existente = $destinoValores->get($fila->producto_id);
            if ($existente === null) {
                $resultado['se_pasan']++;
            } elseif (mb_strtoupper(trim((string) $existente->valor)) === mb_strtoupper(trim((string) $fila->valor))) {
                $resultado['iguales']++;
            } else {
                $resultado['conflictos'][] = [
                    'codigo' => (string) $fila->codigo,
                    'nombre' => (string) $fila->nombre,
                    'origen' => (string) $fila->valor,
                    'destino' => (string) $existente->valor,
                ];
            }
        }

        $categoriasDestino = $destino->categorias()->pluck('categorias.id')->flip();
        foreach ($origen->categorias()->pluck('categorias.id') as $categoriaId) {
            $resultado['categorias']++;
            if (! isset($categoriasDestino[$categoriaId])) {
                $resultado['categorias_nuevas']++;
            }
        }

        return $resultado;
    }

    /** Pasa todo de $origen a $destino y elimina $origen. */
    public function ejecutar(Caracteristica $origen, Caracteristica $destino, string $enConflicto = self::CONSERVAR_DESTINO): void
    {
        $this->validar($origen, $destino);

        DB::transaction(function () use ($origen, $destino, $enConflicto): void {
            $destinoValores = $this->valoresDe($destino)->keyBy('producto_id');
            $ahora = now();

            foreach ($this->valoresDe($origen) as $fila) {
                if (! $destinoValores->has($fila->producto_id)) {
                    DB::table('caracteristica_producto')->insert([
                        'producto_id' => $fila->producto_id,
                        'caracteristica_id' => $destino->id,
                        'valor' => $fila->valor,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);
                } elseif ($enConflicto === self::CONSERVAR_ORIGEN) {
                    DB::table('caracteristica_producto')
                        ->where('producto_id', $fila->producto_id)
                        ->where('caracteristica_id', $destino->id)
                        ->update(['valor' => $fila->valor, 'updated_at' => $ahora]);
                }
            }

            $destino->categorias()->syncWithoutDetaching($origen->categorias()->pluck('categorias.id')->all());

            // Los vínculos de la que se elimina se van con ella (claves foráneas en cascada).
            $origen->delete();
        });
    }

    private function validar(Caracteristica $origen, Caracteristica $destino): void
    {
        if ($origen->is($destino)) {
            throw new InvalidArgumentException('Una característica no se puede fusionar consigo misma.');
        }
    }

    /** @return \Illuminate\Support\Collection<int, object{producto_id: int, valor: string, codigo: ?string, nombre: ?string}> */
    private function valoresDe(Caracteristica $caracteristica): \Illuminate\Support\Collection
    {
        return DB::table('caracteristica_producto as cp')
            ->join('productos as p', 'p.id', '=', 'cp.producto_id')
            ->where('cp.caracteristica_id', $caracteristica->id)
            ->orderBy('p.codigo')
            ->get(['cp.producto_id', 'cp.valor', 'p.codigo', 'p.nombre']);
    }
}
