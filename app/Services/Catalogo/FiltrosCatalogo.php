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

    /**
     * @param  list<int>  $estados  productos.estado (NUEVO / RECONSTRUIDO)
     * @param  array<string, string>  $atributos  columna_N => valor
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
        ];

        // Cambiar de categoría o de vehículo invalida lo que dependía de ellos.
        if (array_key_exists('categoria', $cambios) && $cambios['categoria'] !== $this->categoria) {
            $actual['atributo'] = null;
        }
        if (array_key_exists('vehiculo', $cambios) && $cambios['vehiculo'] !== $this->vehiculo) {
            $actual['modelo'] = null;
        }

        return array_filter(array_replace($actual, $cambios), fn ($v) => $v !== null && $v !== [] && $v !== '');
    }

    /** Sin el estado indicado (para el chip «×» de ese estado). */
    public function sinEstado(int $estado): array
    {
        $resto = array_values(array_diff($this->estados, [$estado]));

        return $this->query(['estado' => array_map(fn (int $e) => array_search($e, self::ESTADOS, true), $resto) ?: null]);
    }

    /** Sin el atributo indicado (para el chip «×» de esa medida). */
    public function sinAtributo(string $columna): array
    {
        $resto = $this->atributos;
        unset($resto[$columna]);

        return $this->query(['atributo' => $resto ?: null]);
    }
}
