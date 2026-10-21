<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

use App\Models\Caracteristica;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class UnidadesProducto
{
    public static function unidad(?string $nombre): ?string
    {
        if (preg_match('/\bvoltaje\b/iu', $nombre ?? '') === 1) {
            return 'v';
        }

        return preg_match('/\bdi[aá]metro\b/iu', $nombre ?? '') === 1 ? 'MM' : null;
    }

    /** Valida antes de guardar y conserva la unidad en los datos del catálogo. */
    public function preparar(Request $request, ?int $categoriaActual = null): void
    {
        $categoria = Categoria::find($request->input('categoria', $categoriaActual));
        $cambios = [];

        foreach ($categoria?->getAttributes() ?? [] as $campo => $nombre) {
            if (str_starts_with($campo, 'columna_') && $request->has($campo)) {
                $unidad = self::unidad($nombre);
                if ($unidad !== null) {
                    $cambios[$campo] = $this->normalizar($request->input($campo), $campo, $unidad);
                }
            }
        }

        $valores = $request->input('caracteristicas', []);
        if (is_array($valores) && $valores !== []) {
            $caracteristicas = Caracteristica::whereIn('id', array_keys($valores))->get(['id', 'nombre']);
            foreach ($caracteristicas as $caracteristica) {
                $unidad = self::unidad($caracteristica->nombre);
                if ($unidad !== null) {
                    $valores[$caracteristica->id] = $this->normalizar(
                        $valores[$caracteristica->id],
                        'caracteristicas.'.$caracteristica->id,
                        $unidad
                    );
                }
            }
            $cambios['caracteristicas'] = $valores;
        }

        $request->merge($cambios);
    }

    private function normalizar(mixed $valor, string $campo, string $unidad): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        // Acepta valores anteriores con su unidad, sin duplicarla al guardar.
        $patron = '/^\s*(\d+(?:[.,]\d+)?)\s*(?:'.preg_quote($unidad, '/').')?\s*$/i';
        if (!is_scalar($valor) || preg_match($patron, (string) $valor, $partes) !== 1) {
            $nombre = $unidad === 'v' ? 'voltaje' : 'diámetro';
            throw ValidationException::withMessages([
                $campo => "El {$nombre} debe ser un número mayor o igual a cero. La unidad {$unidad} se agrega automáticamente.",
            ]);
        }

        // Mismo formato que los valores viejos ya unificados (NormalizadorUnidades).
        return NormalizadorUnidades::numero($partes[1]).$unidad;
    }
}
