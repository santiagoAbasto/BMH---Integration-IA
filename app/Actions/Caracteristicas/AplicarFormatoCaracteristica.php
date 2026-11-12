<?php

declare(strict_types=1);

namespace App\Actions\Caracteristicas;

use App\Models\Caracteristica;
use App\Support\Caracteristicas\FormatoCaracteristica;
use Illuminate\Support\Facades\DB;

/**
 * Pasar un formato nuevo a los valores ya cargados de una característica (admin → Características, al
 * cambiar el formato). Nunca en silencio: primero vistaPrevia() dice cuántos cambian y cuáles no se pueden
 * convertir, y el usuario decide. Lo que no se puede convertir (un texto en una numérica) queda como está.
 */
final class AplicarFormatoCaracteristica
{
    private const EJEMPLOS = 6;

    /**
     * @return array{total: int, cambian: int, iguales: int, ejemplos: list<array{codigo: string, antes: string, despues: string}>,
     *               no_convertibles: list<array{codigo: string, valor: string}>, no_convertibles_total: int}
     */
    public function vistaPrevia(Caracteristica $caracteristica, FormatoCaracteristica $formato): array
    {
        $resultado = ['total' => 0, 'cambian' => 0, 'iguales' => 0, 'ejemplos' => [], 'no_convertibles' => [], 'no_convertibles_total' => 0];

        foreach ($this->valores($caracteristica) as $fila) {
            $resultado['total']++;
            $nuevo = $this->convertir($formato, (string) $fila->valor);
            if ($nuevo === null) {
                $resultado['no_convertibles_total']++;
                if (count($resultado['no_convertibles']) < 20) {
                    $resultado['no_convertibles'][] = ['codigo' => (string) $fila->codigo, 'valor' => (string) $fila->valor];
                }
            } elseif ($nuevo === $fila->valor) {
                $resultado['iguales']++;
            } else {
                $resultado['cambian']++;
                if (count($resultado['ejemplos']) < self::EJEMPLOS) {
                    $resultado['ejemplos'][] = ['codigo' => (string) $fila->codigo, 'antes' => (string) $fila->valor, 'despues' => $nuevo];
                }
            }
        }

        return $resultado;
    }

    /** Cambia los valores que se pueden convertir. Devuelve cuántos cambiaron. */
    public function ejecutar(Caracteristica $caracteristica, FormatoCaracteristica $formato): int
    {
        $cambiados = 0;

        DB::transaction(function () use ($caracteristica, $formato, &$cambiados): void {
            foreach ($this->valores($caracteristica) as $fila) {
                $nuevo = $this->convertir($formato, (string) $fila->valor);
                if ($nuevo !== null && $nuevo !== $fila->valor) {
                    DB::table('caracteristica_producto')
                        ->where('producto_id', $fila->producto_id)
                        ->where('caracteristica_id', $caracteristica->id)
                        ->update(['valor' => $nuevo, 'updated_at' => now()]);
                    $cambiados++;
                }
            }
        });

        return $cambiados;
    }

    /** El valor con el formato nuevo, o null si no se puede convertir (queda como está). */
    private function convertir(FormatoCaracteristica $formato, string $valor): ?string
    {
        $nuevo = $formato->normalizar($valor, $error);

        return $error === null && $nuevo !== null ? mb_substr($nuevo, 0, 255) : null;
    }

    /** @return iterable<object{producto_id: int, valor: string, codigo: ?string}> */
    private function valores(Caracteristica $caracteristica): iterable
    {
        return DB::table('caracteristica_producto as cp')
            ->join('productos as p', 'p.id', '=', 'cp.producto_id')
            ->where('cp.caracteristica_id', $caracteristica->id)
            ->orderBy('p.codigo')
            ->get(['cp.producto_id', 'cp.valor', 'p.codigo']);
    }
}
