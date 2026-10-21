<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use RuntimeException;

/**
 * Al corregir el código de una imagen sin asignar, el código elegido ya lo
 * tiene otra foto de ese producto: hay que elegir si reemplazarla o en qué
 * lugar poner la nueva (BibliotecaImagenes::renombrar con $modo).
 */
final class CodigoOcupado extends RuntimeException
{
    /**
     * @param  array{codigo: string, posicion: int, productos: list<array{id: int, codigo: string, nombre: string}>}  $destino
     * @param  string  $objetivo  path de la foto que tiene el código
     */
    public function __construct(
        public readonly string $codigo,
        public readonly array $destino,
        public readonly string $objetivo,
    ) {
        parent::__construct("El código {$codigo} ya lo tiene otra foto de ese producto.");
    }
}
