<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

/**
 * Lo que el visitante eligió en el catálogo (/productos y /buscar): el texto
 * buscado y los filtros del sidebar.
 *
 * Inmutable: los links del sidebar («sacar este filtro», «elegir esta
 * categoría») se arman con con() a partir de los filtros actuales.
 */
final class FiltrosCatalogo
{
    public const NUEVO = 1;

    public const RECONSTRUIDO = 2;

    /** Valor en la URL ⇄ productos.estado. */
    public const ESTADOS = ['nuevo' => self::NUEVO, 'reconstruido' => self::RECONSTRUIDO];

    /** Orden por código: primero los códigos de puros números, o primero los que tienen letras. */
    public const ORDEN_NUMERICO = 'numerico';

    public const ORDEN_ALFABETICO = 'alfabetico';

    /** Valor en la URL ⇄ texto del selector. */
    public const ORDENES = [self::ORDEN_NUMERICO => 'Numérico', self::ORDEN_ALFABETICO => 'Alfabético'];

    /**
     * @param  list<int>  $estados  productos.estado (NUEVO / RECONSTRUIDO)
     * @param  array<string, string>  $atributos  columna_N => valor
     * @param  string|null  $orden  ORDEN_NUMERICO / ORDEN_ALFABETICO; null es el orden de siempre
     */
    public function __construct(
        public readonly string $q = '',
        public readonly ?int $categoria = null,
        public readonly array $estados = [],
        public readonly ?string $marca = null,
        public readonly ?string $vehiculo = null,
        public readonly ?string $modelo = null,
        public readonly ?string $equivalencia = null,
        public readonly array $atributos = [],
        public readonly ?string $orden = null,
    ) {
    }

    public function hayBusqueda(): bool
    {
        return $this->q !== '';
    }

    /** Filtros del sidebar en uso, sin contar el texto buscado ni la categoría. */
    public function cantidadActivos(): int
    {
        return count($this->estados)
            + ($this->marca !== null ? 1 : 0)
            + ($this->vehiculo !== null ? 1 : 0)
            + ($this->modelo !== null ? 1 : 0)
            + ($this->equivalencia !== null ? 1 : 0)
            + count($this->atributos);
    }

    /**
     * Parámetros de la URL, con cambios: null saca un filtro.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    public function query(array $cambios = []): array
    {
        $actual = [
            'q' => $this->q !== '' ? $this->q : null,
            'categoria' => $this->categoria,
            'estado' => array_values(array_map(
                fn (int $e) => array_search($e, self::ESTADOS, true),
                $this->estados
            )) ?: null,
            'marca' => $this->marca,
            'vehiculo' => $this->vehiculo,
            'modelo' => $this->modelo,
            'equivalencia' => $this->equivalencia,
            'atributo' => $this->atributos ?: null,
            'orden' => $this->orden,
        ];

        // Cambiar de categoría invalida las medidas, que son de cada categoría.
        // El modelo, en cambio, sobrevive al cambio de marca: las marcas que
        // se ofrecen ya están filtradas por el modelo elegido, y sin marca el
        // modelo filtra solo.
        if (array_key_exists('categoria', $cambios) && $cambios['categoria'] !== $this->categoria) {
            $actual['atributo'] = null;
        }

        return array_filter(array_replace($actual, $cambios), fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /** Sin el estado indicado (para el chip «×» de ese estado). */
    public function sinEstado(int $estado): array
    {
        $resto = array_values(array_diff($this->estados, [$estado]));

        return $this->query(['estado' => array_map(fn (int $e) => array_search($e, self::ESTADOS, true), $resto) ?: null]);
    }

    /**
     * Sin varios filtros a la vez, con los nombres de CatalogoFiltrado:
     * categoria, estado, marca, vehiculo, modelo, equivalencia o atributo:columna_N.
     *
     * @param  list<string>  $filtros
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    public function sin(array $filtros, array $cambios = []): array
    {
        $atributos = $this->atributos;

        foreach ($filtros as $filtro) {
            if (str_starts_with($filtro, 'atributo:')) {
                unset($atributos[substr($filtro, strlen('atributo:'))]);
            } else {
                $cambios[$filtro] = null;
            }
        }

        // Las medidas son de la categoría: sin categoría no van.
        $cambios['atributo'] = in_array('categoria', $filtros, true) ? null : ($atributos ?: null);

        return $this->query($cambios);
    }

    /** Sin el atributo indicado (para el chip «×» de esa medida). */
    public function sinAtributo(string $columna): array
    {
        $resto = $this->atributos;
        unset($resto[$columna]);

        return $this->query(['atributo' => $resto ?: null]);
    }
}
