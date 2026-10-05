<?php

declare(strict_types=1);

namespace App\Services\Catalogo;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\BuscadorCatalogo;
use App\Services\BusquedaPorEquivalencia;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * El catálogo filtrado: qué productos se muestran y cuántos quedarían con
 * cada opción de cada filtro del sidebar.
 *
 * Se arma en dos pasos:
 *
 *  1. Un «pool» liviano (sólo las columnas que se filtran): el resultado de
 *     la búsqueda de texto si la hay, o el catálogo publicado entero. Son a lo
 *     sumo ~5.000 filas chicas.
 *  2. Los filtros se aplican en PHP sobre ese pool. Así cada contador del
 *     sidebar sale de aplicar todos los filtros menos el propio («si además
 *     elegís Bosch quedan 12»), sin una consulta por opción.
 *
 * La búsqueda de texto, que es lo caro, corre una sola vez por pedido.
 */
final class CatalogoFiltrado
{
    /** Lo que necesita la card de producto (frontend.components.productoBmh). */
    public const RELACIONES_CARD = [
        'categoria',
        'portadaImagen',
        'imagenesGaleria',
        'productCaracteristicas.caracteristica',
        'partesRelacionadas.portadaImagen',
        'partesRelacionadas.imagenesGaleria',
        'equivalencias',
        'aplicaciones',
    ];

    private const COLUMNAS = ['productos.id', 'codigo', 'nombre', 'marca', 'modelo', 'categoria_id', 'orden', 'estado'];

    /** Campos de la categoría que no sirven como filtro de medidas. */
    private const ATRIBUTOS_EXCLUIDOS = ['MARCA', 'APLICACION'];

    /** Valores que en la base significan «sin dato». */
    private const SIN_DATO = ['', '-', '--', '.', 'N/A', 'S/D'];

    public function __construct(
        private readonly BuscadorCatalogo $buscador,
        private readonly BusquedaPorEquivalencia $equivalencias,
    ) {
    }

    public function filtrar(FiltrosCatalogo $filtros): ResultadoCatalogo
    {
        $columnasAtributo = $filtros->categoria !== null ? $this->atributosFiltrables($filtros->categoria) : [];
        // Objetos planos: los filtros leen estos campos miles de veces y el
        // acceso a atributos de Eloquent pesa.
        $pool = $this->pool($filtros, array_keys($columnasAtributo))
            ->map(fn (Producto $p) => (object) $p->getAttributes());
        $aplicaciones = $this->aplicacionesDe($pool);

        $gradosEquivalencia = $filtros->equivalencia !== null
            ? $this->equivalencias->coincidencias($filtros->equivalencia)
            : [];

        $predicados = $this->predicados($filtros, $aplicaciones, $gradosEquivalencia, $columnasAtributo);
        $cumple = fn (object $p, ?string $salvo = null): bool => $this->cumple($p, $predicados, $salvo);

        $resultado = $pool->filter(fn (object $p) => $cumple($p))->values();

        // Con el filtro de equivalencia y sin texto, las exactas primero.
        if ($filtros->equivalencia !== null && ! $filtros->hayBusqueda()) {
            $resultado = $resultado->sortBy(fn (object $p) => $gradosEquivalencia[$p->id] ?? 9)->values();
        }

        $sinCategoria = $pool->filter(fn (object $p) => $cumple($p, 'categoria'));

        return new ResultadoCatalogo(
            ids: $resultado->pluck('id')->map(fn ($id) => (int) $id)->all(),
            totalSinCategoria: $sinCategoria->count(),
            categorias: $this->contar($sinCategoria, fn (object $p) => [$p->categoria_id]),
            estados: $this->contar(
                $pool->filter(fn (object $p) => $cumple($p, 'estado')),
                fn (object $p) => [(int) $p->estado]
            ),
            marcas: $this->contar(
                $pool->filter(fn (object $p) => $cumple($p, 'marca')),
                fn (object $p) => [self::normalizar($p->marca)]
            ),
            vehiculos: $this->contar(
                $pool->filter(fn (object $p) => $cumple($p, 'vehiculo')),
                fn (object $p) => array_keys($aplicaciones[$p->id] ?? [])
            ),
            modelos: $filtros->vehiculo === null ? [] : $this->contar(
                $pool->filter(fn (object $p) => $cumple($p, 'modelo')),
                fn (object $p) => $aplicaciones[$p->id][$filtros->vehiculo] ?? []
            ),
            atributos: $this->opcionesDeAtributos($pool, $columnasAtributo, $cumple, $filtros->atributos),
        );
    }

    /**
     * Los productos de una página, con todo lo que muestra la card.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Producto>
     */
    public function productos(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $orden = array_flip($ids);

        return Producto::with(self::RELACIONES_CARD)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Producto $p) => $orden[$p->id])
            ->values();
    }

    /**
     * Medidas y atributos por los que se puede filtrar una categoría: sus
     * columnas con nombre, salvo las de equivalencias (tienen su propio
     * filtro) y las que repiten otro filtro.
     *
     * @return array<string, string> columna_N => nombre
     */
    public function atributosFiltrables(int $categoriaId): array
    {
        $categoria = Categoria::find($categoriaId);

        if ($categoria === null) {
            return [];
        }

        $columnas = [];
        foreach ($categoria->getAttributes() as $columna => $nombre) {
            $nombre = trim((string) $nombre);
            $clave = strtoupper(Str::ascii($nombre));

            if (preg_match('/^columna_\d+$/', $columna)
                && $nombre !== ''
                && ! in_array($clave, self::ATRIBUTOS_EXCLUIDOS, true)
                && ! BusquedaPorEquivalencia::esCampoDeEquivalencia($nombre)) {
                $columnas[$columna] = $nombre;
            }
        }

        return $columnas;
    }

    /** Mayúsculas, sin espacios de más: así «Bosch » y «BOSCH» son lo mismo. */
    public static function normalizar(?string $valor): string
    {
        $valor = trim((string) preg_replace('/\s+/u', ' ', (string) $valor));

        return in_array(mb_strtoupper($valor), self::SIN_DATO, true) ? '' : mb_strtoupper($valor);
    }

    /** «MERCEDES BENZ (MB)» y «Mercedes Benz» son el mismo vehículo. */
    public static function normalizarVehiculo(?string $valor): string
    {
        return self::normalizar(preg_replace('/\s*\([^)]*\)\s*$/u', '', (string) $valor));
    }

    // ------------------------------------------------------------------ pool

    /**
     * @param  list<string>  $columnasAtributo
     * @return Collection<int, Producto>
     */
    private function pool(FiltrosCatalogo $filtros, array $columnasAtributo): Collection
    {
        if ($filtros->hayBusqueda()) {
            return $this->buscador->buscar($filtros->q, $columnasAtributo);
        }

        $consulta = Producto::query()
            ->select([...self::COLUMNAS, ...$columnasAtributo])
            // Los ocultos (estado = 0) no se le ofrecen al visitante.
            ->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 0));

        // Con una categoría elegida el catálogo respeta su orden (manual, por
        // código o por nombre); sin categoría, alfabético por nombre.
        return ($filtros->categoria === null
            ? $consulta->orderBy('nombre')
            : $consulta->ordenado($filtros->categoria))
            ->get();
    }

    /**
     * Vehículos (marca → modelos) de cada producto del pool.
     *
     * @param  Collection<int, Producto>  $pool
     * @return array<int, array<string, list<string>>> producto_id => [vehículo => [modelos]]
     */
    private function aplicacionesDe(Collection $pool): array
    {
        if ($pool->isEmpty()) {
            return [];
        }

        $ids = $pool->pluck('id')->flip();
        $consulta = DB::table('aplicaciones')->select(['producto_id', 'nombre', 'valor']);

        // Con medio catálogo, traer la tabla entera es más barato que un IN enorme.
        $filas = $ids->count() > 1500
            ? $consulta->get()
            : $consulta->whereIn('producto_id', $ids->keys())->get();

        $mapa = [];
        foreach ($filas as $fila) {
            if (! isset($ids[$fila->producto_id])) {
                continue;
            }
            $vehiculo = self::normalizarVehiculo($fila->nombre);
            if ($vehiculo === '') {
                continue;
            }
            $modelos = $mapa[$fila->producto_id][$vehiculo] ?? [];
            $modelo = self::normalizar($fila->valor);
            if ($modelo !== '' && ! in_array($modelo, $modelos, true)) {
                $modelos[] = $modelo;
            }
            $mapa[$fila->producto_id][$vehiculo] = $modelos;
        }

        return $mapa;
    }

    // ------------------------------------------------------------- filtros

    /**
     * Un predicado por filtro activo, con el nombre de la faceta que lo omite
     * al contar sus propias opciones.
     *
     * @param  array<int, array<string, list<string>>>  $aplicaciones
     * @param  array<int, int>  $gradosEquivalencia
     * @param  array<string, string>  $columnasAtributo
     * @return array<string, \Closure(object): bool>
     */
    private function predicados(FiltrosCatalogo $f, array $aplicaciones, array $gradosEquivalencia, array $columnasAtributo): array
    {
        $predicados = [];

        if ($f->categoria !== null) {
            $predicados['categoria'] = fn (object $p) => (int) $p->categoria_id === $f->categoria;
        }
        if ($f->estados !== []) {
            $predicados['estado'] = fn (object $p) => in_array((int) $p->estado, $f->estados, true);
        }
        if ($f->marca !== null) {
            $predicados['marca'] = fn (object $p) => self::normalizar($p->marca) === $f->marca;
        }
        if ($f->vehiculo !== null) {
            $predicados['vehiculo'] = fn (object $p) => isset($aplicaciones[$p->id][$f->vehiculo]);
        }
        if ($f->vehiculo !== null && $f->modelo !== null) {
            $predicados['modelo'] = fn (object $p) => in_array($f->modelo, $aplicaciones[$p->id][$f->vehiculo] ?? [], true);
        }
        if ($f->equivalencia !== null) {
            $predicados['equivalencia'] = fn (object $p) => isset($gradosEquivalencia[$p->id]);
        }
        foreach ($f->atributos as $columna => $valor) {
            if (isset($columnasAtributo[$columna])) {
                $predicados['atributo:'.$columna] = fn (object $p) => self::normalizar($p->{$columna}) === $valor;
            }
        }

        return $predicados;
    }

    /** @param  array<string, \Closure(object): bool>  $predicados */
    private function cumple(object $producto, array $predicados, ?string $salvo): bool
    {
        foreach ($predicados as $nombre => $predicado) {
            if ($nombre !== $salvo && ! $predicado($producto)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, object>  $productos
     * @param  callable(object): array<int|string>  $claves
     * @return array<int|string, int>
     */
    private function contar(Collection $productos, callable $claves): array
    {
        $conteo = [];
        foreach ($productos as $producto) {
            foreach ($claves($producto) as $clave) {
                if ($clave === '' || $clave === null) {
                    continue;
                }
                $conteo[$clave] = ($conteo[$clave] ?? 0) + 1;
            }
        }

        return $conteo;
    }

    /**
     * Las opciones de cada medida, ordenadas como números cuando lo son
     * (12, 24, 125 y no 12, 125, 24). Una medida con un solo valor no filtra
     * nada y no se ofrece, salvo que ya esté elegida (hay que poder sacarla).
     *
     * @param  Collection<int, Producto>  $pool
     * @param  array<string, string>  $columnas
     * @param  array<string, string>  $elegidos
     * @return array<string, array{etiqueta: string, opciones: array<string, int>}>
     */
    private function opcionesDeAtributos(Collection $pool, array $columnas, callable $cumple, array $elegidos): array
    {
        $atributos = [];

        foreach ($columnas as $columna => $etiqueta) {
            $opciones = $this->contar(
                $pool->filter(fn (object $p) => $cumple($p, 'atributo:'.$columna)),
                fn (object $p) => [self::normalizar($p->{$columna})]
            );
            uksort($opciones, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));

            if (count($opciones) > 1 || isset($elegidos[$columna])) {
                $atributos[$columna] = ['etiqueta' => $etiqueta, 'opciones' => $opciones];
            }
        }

        return $atributos;
    }
}
