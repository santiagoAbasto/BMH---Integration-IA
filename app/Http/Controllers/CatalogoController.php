<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CatalogoRequest;
use App\Models\Categoria;
use App\Services\Catalogo\CatalogoFiltrado;
use App\Services\Catalogo\FiltrosCatalogo;
use App\Services\Catalogo\ParecidosCatalogo;
use App\Services\Catalogo\ResultadoCatalogo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Catálogo: /productos (por categoría) y /buscar (desde el buscador del
 * header) son la misma pantalla, con el sidebar de filtros y la carga de más
 * productos al hacer scroll.
 *
 * Un mismo pedido puede responder tres cosas, según `parcial`:
 *  - nada       → la página completa;
 *  - contenido  → JSON con sidebar y resultados, cuando cambia un filtro;
 *  - pagina     → JSON con las cards de la página siguiente (scroll infinito).
 *
 * Los parciales van con un parámetro y no con un header para que el
 * navegador nunca muestre el JSON al volver con «atrás».
 *
 * Si nada cumple lo pedido, debajo del aviso van los productos más
 * parecidos (CatalogoFiltrado::parecidos) en vez de una página vacía.
 */
class CatalogoController extends Controller
{
    public const POR_PAGINA = 12;

    public function __construct(
        private readonly CatalogoFiltrado $catalogo,
    ) {
    }

    public function index(CatalogoRequest $request): View|JsonResponse
    {
        $ruta = $request->route()->getName();
        $filtros = $request->filtros();

        if ($request->query('parcial') === 'modelos') {
            return $this->modelos($filtros);
        }

        $resultado = $this->catalogo->filtrar($filtros);

        if ($request->query('parcial') === 'pagina') {
            return $this->pagina($ruta, $filtros, $resultado, $request->pagina());
        }

        $paginas = $request->paginas();
        $datos = $this->datosDeLaVista($ruta, $filtros, $resultado, $paginas);

        if ($request->query('parcial') === 'contenido') {
            return response()->json([
                'html' => view('frontend.catalogo._contenido', $datos + ['sinRecursosCard' => true])->render(),
                'titulo' => $datos['titulo'],
            ])->header('Cache-Control', 'no-store');
        }

        return view('frontend.catalogo', $datos + ['sinRecursosCard' => true]);
    }

    private function pagina(string $ruta, FiltrosCatalogo $filtros, ResultadoCatalogo $resultado, int $pagina): JsonResponse
    {
        $ids = array_slice($resultado->ids, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA);

        return response()->json([
            'html' => view('frontend.catalogo._cards', [
                'productos' => $this->catalogo->productos($ids),
                'sinRecursosCard' => true,
            ])->render(),
            'pagina' => $pagina,
            'siguiente' => $this->siguiente($ruta, $filtros, $resultado, $pagina),
        ])->header('Cache-Control', 'no-store');
    }

    /** @return array<string, mixed> */
    private function datosDeLaVista(string $ruta, FiltrosCatalogo $filtros, ResultadoCatalogo $resultado, int $paginas): array
    {
        $categorias = Categoria::orderBy('nombre')->get(['id', 'nombre']);
        $categoria = $filtros->categoria !== null ? $categorias->firstWhere('id', $filtros->categoria) : null;
        $ids = array_slice($resultado->ids, 0, self::POR_PAGINA * $paginas);
        $chips = $this->chips($ruta, $filtros, $categoria);

        return [
            'ruta' => $ruta,
            'filtros' => $filtros,
            'resultado' => $resultado,
            'productos' => $this->catalogo->productos($ids),
            'paginas' => $paginas,
            'siguiente' => $this->siguiente($ruta, $filtros, $resultado, $paginas),
            'categorias' => $categorias,
            'categoria' => $categoria,
            'titulo' => $this->titulo($filtros, $categoria),
            'chips' => $chips,
            'parecidos' => $resultado->total() === 0 ? $this->parecidos($ruta, $filtros, $categoria, $chips) : null,
            // El campo del header muestra lo buscado (layouts.partials.buscador-header).
            'termino' => $filtros->q,
            // El header muestra la navegación de la Zona de Clientes a quien entró.
            'zonaclientes' => Auth::guard('web')->check(),
        ];
    }

    private function siguiente(string $ruta, FiltrosCatalogo $filtros, ResultadoCatalogo $resultado, int $pagina): ?string
    {
        if ($pagina * self::POR_PAGINA >= $resultado->total()) {
            return null;
        }

        return route($ruta, $filtros->query() + ['page' => $pagina + 1, 'parcial' => 'pagina']);
    }

    private function titulo(FiltrosCatalogo $filtros, ?Categoria $categoria): string
    {
        $nombre = $categoria ? mb_convert_case($categoria->nombre, MB_CASE_TITLE, 'UTF-8') : null;

        return match (true) {
            $filtros->hayBusqueda() => 'Resultados para «'.$filtros->q.'»',
            $nombre !== null => $nombre,
            default => 'Todos los productos',
        };
    }

    /**
     * Filtros en uso, cada uno con el link que lo saca. La clave es el nombre
     * del filtro en CatalogoFiltrado (los estados, uno por estado: estado:1).
     *
     * @return Collection<string, array{etiqueta: string, url: string}>
     */
    private function chips(string $ruta, FiltrosCatalogo $filtros, ?Categoria $categoria): Collection
    {
        $chips = collect();
        $agregar = fn (string $clave, string $etiqueta, array $query) => $chips->put($clave, ['etiqueta' => $etiqueta, 'url' => route($ruta, $query)]);

        if ($filtros->hayBusqueda()) {
            $agregar('q', '«'.$filtros->q.'»', $filtros->query(['q' => null]));
        }
        if ($categoria !== null && $filtros->hayBusqueda()) {
            $agregar('categoria', mb_convert_case($categoria->nombre, MB_CASE_TITLE, 'UTF-8'), $filtros->query(['categoria' => null]));
        }
        foreach ($filtros->estados as $estado) {
            $agregar('estado:'.$estado, $estado === FiltrosCatalogo::NUEVO ? 'Nuevo' : 'Reconstruido', $filtros->sinEstado($estado));
        }
        if ($filtros->marca !== null) {
            $agregar('marca', 'Marca: '.self::enTitulo($filtros->marca), $filtros->query(['marca' => null]));
        }
        if ($filtros->vehiculo !== null) {
            $agregar('vehiculo', 'Vehículo: '.self::enTitulo($filtros->vehiculo), $filtros->query(['vehiculo' => null]));
        }
        if ($filtros->modelo !== null) {
            $agregar('modelo', 'Modelo: '.self::enTitulo($filtros->modelo), $filtros->query(['modelo' => null]));
        }
        if ($filtros->equivalencia !== null) {
            $agregar('equivalencia', 'Equivalencia: '.$filtros->equivalencia, $filtros->query(['equivalencia' => null]));
        }
        if ($filtros->atributos !== [] && $filtros->categoria !== null) {
            $nombres = $this->catalogo->atributosFiltrables($filtros->categoria);
            foreach ($filtros->atributos as $columna => $valor) {
                $agregar('atributo:'.$columna, mb_convert_case($nombres[$columna] ?? 'Medida', MB_CASE_TITLE, 'UTF-8').': '.$valor, $filtros->sinAtributo($columna));
            }
        }

        return $chips;
    }

    /**
     * Los productos más parecidos, para cuando nada cumple todo lo pedido.
     *
     * @param  Collection<string, array{etiqueta: string, url: string}>  $chips
     * @return array{productos: Collection, sin: list<string>, url: string|null, total: int, termino: string|null, parcial: bool}|null
     *         sin: los filtros que no cumplen, como se ven en los chips;
     *         url: la página con todos ellos (null si sólo coinciden algunas palabras)
     */
    private function parecidos(string $ruta, FiltrosCatalogo $filtros, ?Categoria $categoria, Collection $chips): ?array
    {
        $parecidos = $this->catalogo->parecidos($filtros, self::POR_PAGINA);

        if ($parecidos === null) {
            return null;
        }

        $sin = [];
        foreach ($parecidos->sinFiltros as $filtro) {
            if ($filtro === 'categoria') {
                $sin[] = $categoria !== null ? self::enTitulo($categoria->nombre) : 'la categoría';

                continue;
            }
            foreach ($chips as $clave => $chip) {
                if ($clave === $filtro || str_starts_with($clave, $filtro.':')) {
                    $sin[] = $chip['etiqueta'];
                }
            }
        }

        return [
            'productos' => $this->catalogo->productos($parecidos->ids),
            'sin' => $sin,
            'url' => $parecidos->tienePagina()
                ? route($ruta, $filtros->sin($parecidos->sinFiltros, $parecidos->termino !== null ? ['q' => $parecidos->termino] : []))
                : null,
            'total' => $parecidos->total,
            'termino' => $parecidos->termino,
            'parcial' => $parecidos->parcial,
        ];
    }

    /**
     * Opciones del filtro de modelo con los filtros actuales. Sin marca son
     * miles, así que no van en la página: el desplegable las pide al abrirse.
     * Con marca no hacen falta (van en la página), pero responde igual.
     */
    private function modelos(FiltrosCatalogo $filtros): JsonResponse
    {
        $resultado = $this->catalogo->filtrar($filtros, modelosDeTodasLasMarcas: true);
        $modelos = $resultado->modelos;
        uksort($modelos, fn ($a, $b) => strnatcasecmp((string) $a, (string) $b));

        $opciones = [];
        foreach ($modelos as $modelo => $cantidad) {
            $opciones[] = [
                'valor' => (string) $modelo,
                'texto' => self::enTitulo((string) $modelo),
                'cantidad' => $cantidad,
                'marcas' => array_map(self::enTitulo(...), $resultado->marcasDeModelos[$modelo] ?? []),
            ];
        }

        // Cambia sólo cuando cambia el catálogo: unos minutos de caché en el
        // navegador ahorran recalcular al abrir y cerrar el desplegable.
        return response()->json(['opciones' => $opciones])->header('Cache-Control', 'private, max-age=300');
    }

    private static function enTitulo(string $valor): string
    {
        return mb_convert_case($valor, MB_CASE_TITLE, 'UTF-8');
    }
}
