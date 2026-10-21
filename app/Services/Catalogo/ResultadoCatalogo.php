<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

/**
 * Productos que cumplen los filtros (ya ordenados) y, para cada filtro del
 * sidebar, cuántos productos quedarían al elegir cada opción.
 */
final class ResultadoCatalogo
{
    /**
     * @param  list<int>  $ids  ids en el orden en que se muestran
     * @param  array<int, int>  $categorias  categoria_id => cantidad
     * @param  array<int, int>  $estados  estado => cantidad
     * @param  array<string, int>  $marcas  marca del repuesto => cantidad
     * @param  array<string, int>  $vehiculos  marca del vehículo => cantidad
     * @param  array<string, int>  $modelos  modelo => cantidad: los de la marca elegida, o
     *         sin marca los de todas si se pidieron (CatalogoFiltrado::filtrar)
     * @param  array<string, array{etiqueta: string, opciones: array<string, int>}>  $atributos  columna_N => opciones
     * @param  array<string, list<string>>  $marcasDeModelos  modelo => marcas (sólo con los de todas)
     */
    public function __construct(
        public readonly array $ids,
        public readonly int $totalSinCategoria,
        public readonly array $categorias,
        public readonly array $estados,
        public readonly array $marcas,
        public readonly array $vehiculos,
        public readonly array $modelos,
        public readonly array $atributos,
        public readonly array $marcasDeModelos = [],
    ) {
    }

    public function total(): int
    {
        return count($this->ids);
    }
}
