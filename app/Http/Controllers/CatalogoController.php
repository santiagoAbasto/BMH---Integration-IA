<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CatalogoRequest;
use App\Models\Categoria;
use App\Services\Catalogo\CatalogoFiltrado;
use App\Services\Catalogo\FiltrosCatalogo;
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
            'chips' => $this->chips($ruta, $filtros, $categoria),
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
     * Filtros en uso, cada uno con el link que lo saca.
     *
     * @return Collection<int, array{etiqueta: string, url: string}>
     */
    private function chips(string $ruta, FiltrosCatalogo $filtros, ?Categoria $categoria): Collection
    {
        $chips = collect();
        $agregar = fn (string $etiqueta, array $query) => $chips->push(['etiqueta' => $etiqueta, 'url' => route($ruta, $query)]);

        if ($filtros->hayBusqueda()) {
            $agregar('«'.$filtros->q.'»', $filtros->query(['q' => null]));
        }
        if ($categoria !== null && $filtros->hayBusqueda()) {
            $agregar(mb_convert_case($categoria->nombre, MB_CASE_TITLE, 'UTF-8'), $filtros->query(['categoria' => null]));
        }
        foreach ($filtros->estados as $estado) {
            $agregar($estado === FiltrosCatalogo::NUEVO ? 'Nuevo' : 'Reconstruido', $filtros->sinEstado($estado));
        }
        if ($filtros->marca !== null) {
            $agregar('Marca: '.self::enTitulo($filtros->marca), $filtros->query(['marca' => null]));
        }
        if ($filtros->vehiculo !== null) {
            $agregar('Vehículo: '.self::enTitulo($filtros->vehiculo), $filtros->query(['vehiculo' => null]));
        }
        if ($filtros->modelo !== null) {
            $agregar('Modelo: '.self::enTitulo($filtros->modelo), $filtros->query(['modelo' => null]));
        }
        if ($filtros->equivalencia !== null) {
            $agregar('Equivalencia: '.$filtros->equivalencia, $filtros->query(['equivalencia' => null]));
        }
        if ($filtros->atributos !== [] && $filtros->categoria !== null) {
            $nombres = $this->catalogo->atributosFiltrables($filtros->categoria);
            foreach ($filtros->atributos as $columna => $valor) {
                $agregar(mb_convert_case($nombres[$columna] ?? 'Medida', MB_CASE_TITLE, 'UTF-8').': '.$valor, $filtros->sinAtributo($columna));
            }
        }

        return $chips;
    }

    private static function enTitulo(string $valor): string
    {
        return mb_convert_case($valor, MB_CASE_TITLE, 'UTF-8');
    }
}
