<?php

declare(strict_types=1);

namespace App\Actions\Caracteristicas;

use App\Models\Caracteristica;
use App\Models\Producto;
use App\Support\Caracteristicas\FormatoCaracteristica;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El único lugar donde se guardan los valores de las características de un producto (admin de productos,
 * altas y cualquier importación).
 *
 * Recibe [caracteristica_id => valor] y:
 *  1. normaliza cada valor con el formato de su característica (mayúsculas, numérica, unidad);
 *  2. si alguno no es válido no guarda nada y devuelve el error en el campo `caracteristicas.ID`, que es
 *     el nombre del input del formulario, así el mensaje vuelve junto al valor que lo causó;
 *  3. sincroniza: lo que vino vacío (o «-») y lo que no vino se quita del producto.
 */
final class SincronizarCaracteristicasProducto
{
    /**
     * Valida y normaliza sin guardar.
     *
     * @param  array<int|string, mixed>  $valores  caracteristica_id => valor escrito
     * @return array{valores: array<int, string>, errores: array<string, string>}
     *         valores: caracteristica_id => valor como se guarda (sin los vacíos);
     *         errores: 'caracteristicas.ID' => mensaje
     */
    public function preparar(array $valores): array
    {
        $ids = array_values(array_filter(
            array_map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]), array_keys($valores)),
            fn ($id) => $id !== false,
        ));
        $caracteristicas = Caracteristica::query()->whereKey($ids)->get()->keyBy('id');

        $limpios = [];
        $errores = [];
        foreach ($valores as $id => $valor) {
            $campo = 'caracteristicas.'.$id;
            if (! is_scalar($valor) && $valor !== null) {
                $errores[$campo] = 'El valor no es válido.';

                continue;
            }
            $caracteristica = $caracteristicas->get((int) $id);
            if ($caracteristica === null) {
                // Una fila de un formulario viejo, de una característica que se borró mientras tanto.
                if (! FormatoCaracteristica::vacio(is_scalar($valor) ? (string) $valor : null)) {
                    $errores[$campo] = 'Esta característica ya no existe: sacala de la lista.';
                }

                continue;
            }

            $normalizado = $caracteristica->formato()->normalizar($valor === null ? null : (string) $valor, $error);
            if ($error !== null) {
                $errores[$campo] = $error;

                continue;
            }
            if ($normalizado !== null) {
                $limpios[(int) $caracteristica->id] = mb_substr($normalizado, 0, 255);
            }
        }

        return ['valores' => $limpios, 'errores' => $errores];
    }

    /**
     * Guarda los valores del producto: los que vienen quedan con su valor normalizado y los demás se quitan.
     *
     * @param  array<int|string, mixed>  $valores  caracteristica_id => valor escrito
     * @return array<int, string> lo que quedó guardado
     *
     * @throws ValidationException si algún valor no respeta el formato de su característica
     */
    public function ejecutar(Producto $producto, array $valores): array
    {
        ['valores' => $limpios, 'errores' => $errores] = $this->preparar($valores);

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        DB::transaction(function () use ($producto, $limpios): void {
            $producto->caracteristicas()->sync(
                array_map(fn (string $valor) => ['valor' => $valor], $limpios)
            );
        });

        return $limpios;
    }
}
