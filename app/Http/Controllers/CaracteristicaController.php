<?php

namespace App\Http\Controllers;

use App\Actions\Caracteristicas\AplicarFormatoCaracteristica;
use App\Actions\Caracteristicas\FusionarCaracteristicas;
use App\Http\Requests\CaracteristicaRequest;
use App\Models\Caracteristica;
use App\Models\Categoria;
use App\Models\Producto;
use App\Support\Caracteristicas\FormatoCaracteristica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → Características: el listado (con cuántas categorías y productos usan cada una), el alta, la
 * edición con el formato, la baja con su impacto y la fusión de duplicadas. También los pedidos JSON
 * chicos que usan los selectores de Categorías y Productos (buscar, crear al vuelo, traer de otro lado).
 */
class CaracteristicaController extends Controller
{
    /** Filas por página del listado. */
    private const POR_PAGINA = 50;

    /** Resultados de los buscadores de los selectores. */
    private const POR_BUSQUEDA = 20;

    public function dash_caracteristicas(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        // Lista blanca: cualquier otro valor cae en el orden por defecto.
        $ordenar = (string) $request->query('ordenar', Caracteristica::ORDEN_CAMPO);
        if (! in_array($ordenar, Caracteristica::CRITERIOS_DE_ORDEN, true)) {
            $ordenar = Caracteristica::ORDEN_CAMPO;
        }
        $soloSinUso = $request->query('mostrar') === 'sin-uso';

        $caracteristicas = Caracteristica::query()
            ->withCount(['categorias', 'productos'])
            ->buscar($buscar)
            ->when($soloSinUso, fn ($q) => $q->sinUso())
            ->ordenadoPor($ordenar)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $totales = [
            'todas' => Caracteristica::query()->count(),
            'sin_uso' => Caracteristica::query()->sinUso()->count(),
        ];
        $siguienteOrden = Caracteristica::siguienteOrden();

        return view('backend.dash-caracteristicas', compact('caracteristicas', 'buscar', 'ordenar', 'soloSinUso', 'totales', 'siguienteOrden'));
    }

    public function store(CaracteristicaRequest $request): RedirectResponse|JsonResponse
    {
        $nombre = $request->nombre();

        if (($existente = Caracteristica::nombreOcupado($nombre)) !== null) {
            $mensaje = "Ya existe «{$existente->nombre}». Usá esa o elegí otro nombre.";

            return $request->expectsJson()
                ? response()->json(['message' => $mensaje, 'errors' => ['nombre' => [$mensaje]], 'existente' => $this->json($existente)], 422)
                : $this->volver()->withInput()->with('warning', $mensaje);
        }

        $caracteristica = Caracteristica::query()->create([
            'nombre' => $nombre,
            'orden' => $request->orden() ?? Caracteristica::siguienteOrden(),
        ] + $request->atributosDeFormato());

        if ($request->expectsJson()) {
            return response()->json(['caracteristica' => $this->json($caracteristica)], 201);
        }

        return $this->volver()->with('success', "Se creó «{$caracteristica->nombre}». Ahora podés sumarla a una categoría o a un producto.");
    }

    public function update(CaracteristicaRequest $request, AplicarFormatoCaracteristica $aplicar): RedirectResponse
    {
        $caracteristica = Caracteristica::query()->find($request->caracteristicaId());

        if ($caracteristica === null) {
            return $this->volver()->with('warning', 'La característica ya no existe: puede que alguien la haya borrado recién.');
        }

        $nombre = $request->nombre();

        if (($existente = Caracteristica::nombreOcupado($nombre, $caracteristica->id)) !== null) {
            return $this->volver()->withInput()->with('warning', "Ya existe otra característica llamada «{$existente->nombre}». Si son la misma, usá «Fusionar».");
        }

        $caracteristica->update([
            'nombre' => $nombre,
            'orden' => $request->orden(),
        ] + $request->atributosDeFormato());

        $mensaje = "Se guardó «{$caracteristica->nombre}».";
        if ($request->boolean('aplicar_formato')) {
            $cambiados = $aplicar->ejecutar($caracteristica, $caracteristica->formato());
            $mensaje .= $cambiados === 0
                ? ' Los valores cargados ya tenían ese formato.'
                : " Se actualizaron {$cambiados} ".($cambiados === 1 ? 'valor cargado' : 'valores cargados').' al formato nuevo.';
        }

        return $this->volver()->with('success', $mensaje);
    }

    /**
     * Baja. El id puede no existir (doble clic, volver atrás y reenviar, dos pestañas): se responde con un
     * aviso en vez de un error. Los vínculos con categorías y productos se van con la característica
     * (claves foráneas en cascada); la confirmación ya le mostró al usuario cuántos son.
     */
    public function delete(Request $request): RedirectResponse
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            return $this->volver()->with('warning', 'No se indicó qué característica borrar.');
        }

        $caracteristica = Caracteristica::query()->withCount(['categorias', 'productos'])->find($id);

        if ($caracteristica === null) {
            return $this->volver()->with('warning', 'La característica ya no existe: puede que ya la hayas borrado.');
        }

        $impacto = self::impacto($caracteristica->categorias_count, $caracteristica->productos_count);
        $caracteristica->delete();

        return $this->volver()->with('success', "Se eliminó «{$caracteristica->nombre}»".($impacto !== '' ? ": se quitó de {$impacto}." : '.'));
    }

    // ------------------------------------------------------------------ JSON

    /** Buscador de los selectores: por nombre, con cuántas categorías y productos la usan. */
    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $resultados = Caracteristica::query()
            ->withCount(['categorias', 'productos'])
            ->buscar($q)
            // Primero las que empiezan con lo escrito.
            ->orderByRaw('caracteristicas.nombre LIKE ? DESC', [str_replace(['%', '_'], ['\%', '\_'], $q).'%'])
            ->ordenadas()
            ->limit(self::POR_BUSQUEDA)
            ->get();

        return response()->json(['data' => $resultados->map(fn (Caracteristica $c) => $this->json($c))->values()]);
    }

    /** Las que no usa ninguna categoría ni ningún producto. */
    public function sinUso(): JsonResponse
    {
        $sinUso = Caracteristica::query()->withCount(['categorias', 'productos'])->sinUso()->ordenadas()->get();

        return response()->json(['data' => $sinUso->map(fn (Caracteristica $c) => $this->json($c))->values()]);
    }

    /** Categorías o productos de donde traer características («Traer de otra categoría o producto»). */
    public function origenes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $tipo = $request->query('tipo') === 'producto' ? 'producto' : 'categoria';
        $excluir = (int) $request->query('excluir', 0);

        if ($tipo === 'categoria') {
            $categorias = Categoria::query()
                ->withCount('caracteristicas')
                ->when($q !== '', fn ($c) => $c->where('nombre', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%'))
                ->when($excluir > 0, fn ($c) => $c->whereKeyNot($excluir))
                ->orderBy('nombre')
                ->limit(self::POR_BUSQUEDA)
                ->get(['id', 'nombre']);

            return response()->json(['data' => $categorias->map(fn (Categoria $c) => [
                'id' => $c->id,
                'titulo' => $c->nombre,
                'detalle' => self::cantidad($c->caracteristicas_count, 'característica', 'características'),
                'cantidad' => $c->caracteristicas_count,
                'url' => route('categoria.show', $c->id),
            ])->values()]);
        }

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q).'%';
        $productos = Producto::query()
            ->withCount('caracteristicas')
            ->where(fn ($p) => $p->where('codigo', 'like', $like)->orWhere('nombre', 'like', $like))
            ->when($excluir > 0, fn ($p) => $p->whereKeyNot($excluir))
            ->orderByRaw('codigo LIKE ? DESC', [$q.'%'])
            ->orderBy('codigo')
            ->limit(self::POR_BUSQUEDA)
            ->get(['id', 'codigo', 'nombre']);

        return response()->json(['data' => $productos->map(fn (Producto $p) => [
            'id' => $p->id,
            'titulo' => trim($p->codigo.' · '.$p->nombre, ' ·'),
            'detalle' => self::cantidad($p->caracteristicas_count, 'característica cargada', 'características cargadas'),
            'cantidad' => $p->caracteristicas_count,
            'url' => route('caracteristicas.de-producto', $p->id),
        ])->values()]);
    }

    /** Las características de un producto, con sus valores («Copiar de otro producto»). */
    public function deProducto(Producto $producto): JsonResponse
    {
        $producto->load(['caracteristicas', 'categoria:id,nombre']);

        return response()->json([
            'id' => $producto->id,
            'nombre' => trim($producto->codigo.' · '.$producto->nombre, ' ·'),
            'categoria' => $producto->categoria?->nombre,
            'caracteristicas' => $producto->caracteristicas->map(fn (Caracteristica $c) => $c->paraFormulario() + ['valor' => (string) $c->pivot->valor])->values(),
        ]);
    }

    /** Qué pasaría con los valores cargados si se cambia el formato (antes de guardar). */
    public function vistaFormato(Caracteristica $caracteristica, Request $request, AplicarFormatoCaracteristica $aplicar): JsonResponse
    {
        $numerica = $request->query('tipo') === 'numero';
        $unidad = trim((string) $request->query('unidad', ''));
        $formato = new FormatoCaracteristica(
            mayusculas: $request->boolean('mayusculas', true),
            numerica: $numerica,
            unidad: $numerica && $unidad !== '' ? mb_substr($unidad, 0, 10) : null,
        );

        return response()->json($aplicar->vistaPrevia($caracteristica, $formato));
    }

    /** Qué pasaría al fusionar esta característica con otra (antes de confirmar). */
    public function vistaFusion(Caracteristica $caracteristica, Request $request, FusionarCaracteristicas $fusionar): JsonResponse
    {
        $destino = Caracteristica::query()->find((int) $request->query('con'));
        if ($destino === null || $destino->is($caracteristica)) {
            return response()->json(['message' => 'Elegí otra característica para fusionar.'], 422);
        }

        return response()->json($fusionar->vistaPrevia($caracteristica, $destino) + ['destino' => $this->json($destino)]);
    }

    public function fusionar(Caracteristica $caracteristica, Request $request, FusionarCaracteristicas $fusionar): RedirectResponse
    {
        $datos = $request->validate([
            'con' => ['required', 'integer'],
            'en_conflicto' => ['nullable', 'in:'.FusionarCaracteristicas::CONSERVAR_DESTINO.','.FusionarCaracteristicas::CONSERVAR_ORIGEN],
        ], ['con.required' => 'Elegí con qué característica fusionar.']);

        $destino = Caracteristica::query()->find((int) $datos['con']);
        if ($destino === null || $destino->is($caracteristica)) {
            return $this->volver()->with('warning', 'Elegí otra característica para fusionar.');
        }

        $nombre = $caracteristica->nombre;
        $fusionar->ejecutar($caracteristica, $destino, $datos['en_conflicto'] ?? FusionarCaracteristicas::CONSERVAR_DESTINO);

        return $this->volver()->with('success', "Listo: «{$nombre}» se fusionó con «{$destino->nombre}». Sus valores y categorías ahora están en «{$destino->nombre}».");
    }

    // -------------------------------------------------------------- ayudas

    /** Lo que necesita el JS de los selectores. */
    private function json(Caracteristica $caracteristica): array
    {
        if (! array_key_exists('productos_count', $caracteristica->getAttributes())) {
            $caracteristica->loadCount(['categorias', 'productos']);
        }

        return $caracteristica->paraFormulario() + [
            'categorias' => (int) $caracteristica->categorias_count,
            'productos' => (int) $caracteristica->productos_count,
            'uso' => self::impacto((int) $caracteristica->categorias_count, (int) $caracteristica->productos_count) ?: 'sin uso',
        ];
    }

    /** «3 categorías y 120 productos», o '' si no la usa nadie. */
    public static function impacto(int $categorias, int $productos): string
    {
        $partes = array_filter([
            $categorias > 0 ? self::cantidad($categorias, 'categoría', 'categorías') : null,
            $productos > 0 ? self::cantidad($productos, 'producto', 'productos') : null,
        ]);

        return implode(' y ', $partes);
    }

    private static function cantidad(int $n, string $singular, string $plural): string
    {
        return number_format($n, 0, ',', '.').' '.($n === 1 ? $singular : $plural);
    }

    /**
     * Vuelve al listado conservando página y búsqueda; si no hay referer —un POST directo, o un navegador
     * que no lo manda— cae en el listado.
     */
    private function volver(): RedirectResponse
    {
        return back(fallback: route('dashboard.caracteristicas'));
    }
}
