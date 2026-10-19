<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

/**
 * Lo que se muestra cuando ningún producto cumple todo lo que se pidió: los
 * más parecidos (CatalogoFiltrado::parecidos).
 */
final class ParecidosCatalogo
{
    /**
     * @param  list<int>  $ids  los que se muestran, del más al menos parecido
     * @param  list<string>  $sinFiltros  filtros que no cumplen los primeros: categoria,
     *         estado, marca, vehiculo, modelo, equivalencia o atributo:columna_N
     * @param  int  $total  cuántos hay al sacar esos filtros
     * @param  string|null  $termino  lo que se buscó en lugar del texto escrito
     *         (corregido: «alternadro» → «alternador»)
     * @param  bool  $parcial  coinciden sólo algunas de las palabras buscadas
     */
    public function __construct(
        public readonly array $ids,
        public readonly array $sinFiltros,
        public readonly int $total,
        public readonly ?string $termino = null,
        public readonly bool $parcial = false,
    ) {
    }

    /** Hay una página de resultados con todos ellos (sacando los filtros o con el texto corregido). */
    public function tienePagina(): bool
    {
        return ! $this->parcial;
    }
}
