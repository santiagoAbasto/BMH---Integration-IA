<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Bonificacion;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Caracteristica;
use App\Models\Imagen;
use Illuminate\Support\Facades\File;
use App\Models\Metadatos;
use CodersFree\Shoppingcart\Facades\Cart;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Impuesto;
use App\Models\Uso;
use App\Models\Dimension;
use App\Models\Descarga;
use App\Models\Medida;
use App\Models\Repuesto;
use App\Models\User;
use App\Services\BuscadorCatalogo;
use App\Services\Catalogo\CatalogoFiltrado;
use App\Services\Catalogo\FiltrosCatalogo;
use App\Services\Catalogo\PartesReciprocas;
use App\Actions\Caracteristicas\SincronizarCaracteristicasProducto;
use Illuminate\Validation\ValidationException;
use App\Services\BusquedaPorEquivalencia;
use App\Services\Imagenes\ImagenesDeCategoria;
use App\Services\Imagenes\OptimizadorImagenes;
use App\Services\CatalogFilterOptions;
use App\Services\LogosSitio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductoController extends Controller
{
    public function __construct(
        private readonly CatalogFilterOptions $catalogFilterOptions,
        private readonly PartesReciprocas $partesReciprocas,
    ) {
    }

    /** Guarda las imágenes que se suben desde el admin, optimizadas a WebP. */
    private function optimizador(): OptimizadorImagenes
    {
        return app(OptimizadorImagenes::class);
    }

    /**
     * Nombre con que existe hoy una imagen. Las que se optimizaron cambiaron
     * de extensión (media_x.png → media_x.webp): un Excel viejo con el nombre
     * anterior tiene que seguir apuntando a la imagen correcta.
     */
    private function nombreImagenVigente(string $nombre): string
    {
        $directorio = config('imagenes.directorio');
        if ($nombre === '' || is_file($directorio . '/' . $nombre)) {
            return $nombre;
        }

        $webp = pathinfo($nombre, PATHINFO_FILENAME) . '.webp';

        return is_file($directorio . '/' . $webp) ? $webp : $nombre;
    }

    public function load(Request $request)
    {
        $categoria_id = $request->categoria_id;
        $with = [
            'categoria',
            'portadaImagen',
            'imagenesGaleria',
            'caracteristicasVisibles',
            'partesRelacionadas.portadaImagen',
            'partesRelacionadas.imagenesGaleria',
            'equivalencias',
            'aplicaciones',
        ];
        if ($categoria_id == 0) {
            $busqueda = $request->search;
            $productos = Producto::with($with)
                ->where('nombre', 'LIKE', '%' . $busqueda . '%')
                ->orWhereHas('categoria', function ($query) use ($busqueda) {
                    $query->where('nombre', 'LIKE', '%' . $busqueda . '%');
                })
                ->ordenado()->get();
        } else {
            $busqueda = '';
            $productos = Producto::with($with)
                ->whereHas('categoria', function ($query) use ($categoria_id) {
                $query->where('id', $categoria_id);
            })->ordenado()->get();
        }

        $productos = $productos->skip($request->contador)->take($request->xpag);

        return view('frontend/productos-listado', compact('productos'));
    }

    public function ofertas()
    {
        $productos = Producto::with(['categoria', 'portadaImagen', 'imagenesGaleria', 'caracteristicasVisibles'])
            ->where('descuento', '!=', 0)
            ->ordenado()
            ->get();
        $ventana = 'ofertas-nav';
        return view('frontend.ofertas', compact('productos', 'ventana'));
    }

    public function producto(Request $request, CatalogoFiltrado $catalogo)
{
    // Traer producto con sus caracterÃ­sticas
    $producto = Producto::with([
        'categoria',
        'portadaImagen',
        'imagenesGaleria',
        'caracteristicasVisibles',
        'usos',
        'dimensiones',
        'equivalencias',
        'aplicaciones',
        'partesRelacionadas.portadaImagen',
        'partesRelacionadas.imagenesGaleria',
    ])->find($request->integer('id'));

    // Un producto borrado (o un link viejo de buscadores) es un 404, no un 500.
    abort_if($producto === null, 404);
    app(\App\Support\Seo\SeoContext::class)->set(\App\Support\Seo\DynamicSeo::producto($producto));

    // Productos relacionados (mismo formato horizontal que el listado principal)
    if ($producto->categoria != null) {
        $productos = Producto::with([
                'categoria',
                'portadaImagen',
                'imagenesGaleria',
                'caracteristicasVisibles',
                'partesRelacionadas.portadaImagen',
                'partesRelacionadas.imagenesGaleria',
                'equivalencias',
                'aplicaciones',
            ])
            ->where('categoria_id', $producto->categoria->id)
            ->where('id', '!=', $producto->id)
            ->ordenado()
            ->limit(6)
            ->get();
    } else {
        $productos = null;
    }

    // ImÃ¡genes
    $imagenes = Imagen::where('producto_id', $producto->id)->where('tipo', '!=', 'portada')->get();

    // IVA y categorÃ­as
    $iva = Impuesto::find(1);
    $categorias = Categoria::orderBy('nombre')->get();
    $categoria_id = $producto->categoria->id ?? null;
    $ruta = 'productos';
    $filtros = new FiltrosCatalogo(categoria: $categoria_id);
    $resultado = $catalogo->filtrar($filtros);
    $categoria = $producto->categoria;
    $usos = $producto->usos;
    $dimensiones = $producto->dimensiones;
    $ventana = 'categorias-nav';

    // Otras variables
    $zonaclientes = Auth::guard('web')->check();

    // ðŸ”¹ Datos extra como en edit()
    $imagenesProducto = Imagen::where('producto_id', $request->id)->where('sector', 'producto')->orderBy('orden')->get();
    $categoriaSelected = Categoria::with('caracteristicas')->find($producto->categoria_id);
    // Las que declara la categoría, en el orden de cada característica: [nombre, valor].
    $caracteristicas = $producto->fichaCaracteristicas();

    return view('frontend/producto', compact(
        'zonaclientes',
        'producto',
        'productos',
        'imagenes',
        'iva',
        'categorias',
        'categoria_id',
        'ruta',
        'filtros',
        'resultado',
        'usos',
        'dimensiones',
        'ventana',
        'categoria',
        // Agregados de edit()
        'imagenesProducto',
        'categoriaSelected',
        'caracteristicas'
    ));
}



    public function filtrar_productos(Request $request)
    {

        $with = [
            'categoria',
            'portadaImagen',
            'imagenesGaleria',
            'caracteristicasVisibles',
            'partesRelacionadas.portadaImagen',
            'partesRelacionadas.imagenesGaleria',
            'equivalencias',
            'aplicaciones',
        ];
        $categoria = $request->categoria;
        if ($categoria == '') {
            $productos = Producto::with($with)
                ->ordenado()
                ->get();
        } else {
            $productos = Producto::with($with)
                ->whereHas('categoria', function ($query) use ($categoria) {
                $query->where('id', $categoria);
            })->ordenado()->get();
        }

        return view('frontend/productos-listado', compact('productos'));
    }

public function filtroRodamiento(Request $request, BusquedaPorEquivalencia $equivalencias, BuscadorCatalogo $buscador)
{
    $busqueda = '';
    $filtrosAplicados = [];

    // =========================
    // Filtros aplicados (texto)
    // =========================
    if ($request->filled('buscadorPrincipal')) {
        $filtrosAplicados[] = 'Texto libre: "' . $request->buscadorPrincipal . '"';
    }
    if ($request->filled('marca')) {
        $filtrosAplicados[] = 'Marca: "' . $request->marca . '"';
    }
    if ($request->filled('modelo')) {
        $filtrosAplicados[] = 'Modelo: "' . $request->modelo . '"';
    }
    if ($request->filled('codigoBMH')) {
        $filtrosAplicados[] = 'Código BMH: "' . $request->codigoBMH . '"';
    }
    if ($request->filled('equivalenciaFiltro')) {
        $filtrosAplicados[] = 'Equivalencia: "' . $request->equivalenciaFiltro . '"';
    }
    if ($request->filled('categoriaFiltro')) {
        $categoriaNombre = Categoria::find($request->categoriaFiltro)?->nombre ?? $request->categoriaFiltro;
        $filtrosAplicados[] = 'Categoría: "' . $categoriaNombre . '"';
    }
    if ($request->filled('atributo') && $request->filled('valorAttr')) {
        $filtrosAplicados[] = 'Caracteristica 1: "' . $request->valorAttr . '"';
    }
    if ($request->filled('atributoTwo') && $request->filled('valorAttrTwo')) {
        $filtrosAplicados[] = 'Caracteristica 2: "' . $request->valorAttrTwo . '"';
    }
    if ($request->filled('nuevo')) {
        $filtrosAplicados[] = 'Estado: Nuevo';
    }
    if ($request->filled('reconstruido')) {
        $filtrosAplicados[] = 'Estado: Reconstruido';
    }

    $normalizar = function (?string $s) {
        return mb_strtolower(preg_replace('/\s+/', '', $s ?? ''));
    };

    // ========== INICIAR QUERY BASE ==========
    $query = Producto::with([
        'categoria',
        'portadaImagen',
        'imagenesGaleria',
        'caracteristicasVisibles',
        'partesRelacionadas.portadaImagen',
        'partesRelacionadas.imagenesGaleria',
        'equivalencias',
        'aplicaciones',
    ]);

    // =======================================
    // FILTRO POR CÓDIGO BMH (PRIORIDAD)
    // =======================================
    if ($request->filled('codigoBMH')) {
        $busqueda = trim($request->codigoBMH);
        $busquedaSinEspacios = $normalizar($busqueda);

        $query->where(function ($q) use ($busqueda, $busquedaSinEspacios) {
            $q->where('codigo', 'LIKE', '%' . $busqueda . '%')
              ->orWhereRaw("LOWER(REPLACE(codigo, ' ', '')) LIKE ?", ['%' . $busquedaSinEspacios . '%']);
        });
    }
    // =======================================
    // FILTRO POR MARCA (solo si no hay código ni buscador)
    // =======================================
    elseif ($request->filled('marca') && !$request->filled('buscadorPrincipal')) {
        $busqueda = trim($request->marca);

        $query->where(function ($q) use ($busqueda) {
            $q->where('marca', 'LIKE', '%' . $busqueda . '%')
                ->orWhere('nombre', 'LIKE', '%' . $busqueda . '%')
                ->orWhere('codigo', 'LIKE', '%' . $busqueda . '%')
                ->orWhere('modelo', 'LIKE', '%' . $busqueda . '%')
                ->orWhere(DB::raw("CONCAT_WS(' ', nombre, marca)"), 'LIKE', '%' . $busqueda . '%');
        });
    }

    // ======================================================
    // ===   BUSCADOR PRINCIPAL (texto libre)             ===
    // ======================================================
    if ($request->filled('buscadorPrincipal')) {
        $busqueda = trim($request->buscadorPrincipal);
        // Mismo texto libre que el buscador del header (ver BuscadorCatalogo).
        $buscador->aplicarTexto($query, $busqueda);
    }

    // ========== FILTRO POR MODELO ==========
    if ($request->filled('modelo')) {
        $query->where('modelo', $request->modelo);
        $busqueda = $request->modelo;
    }

    // ========== FILTRO POR CATEGORÍA ==========
    $categoriaActual = null;
    if ($request->filled('categoriaFiltro')) {
        $categoriaInput = trim((string) $request->categoriaFiltro);

        // El select manda el id. Resolverlo por id exacto y recién después por
        // nombre: el `orWhere nombre LIKE %id%` que había acá podía quedarse
        // con otra categoría cuyo nombre contuviera esos dígitos.
        $categoria = ctype_digit($categoriaInput)
            ? Categoria::find($categoriaInput)
            : null;
        $categoria ??= Categoria::where('nombre', 'LIKE', '%' . $categoriaInput . '%')->first();

        if ($categoria) {
            $query->where('categoria_id', $categoria->id);
            $categoriaActual = $categoria->toArray();
        }
        // Ojo: $busqueda alimenta el puntaje de relevancia contra
        // código/nombre/marca. Meter acá el id de la categoría desordenaba los
        // resultados por coincidencias numéricas casuales.
    }

    // ========== FILTRO POR EQUIVALENCIA ==========
    // Sólo campos que son equivalencias de verdad (ver BusquedaPorEquivalencia);
    // el grado de cada coincidencia ordena primero las exactas.
    $coincidenciasEquivalencia = [];
    if ($request->filled('equivalenciaFiltro')) {
        $coincidenciasEquivalencia = $equivalencias->coincidencias($request->equivalenciaFiltro);
        $equivalencias->filtrar($query, $coincidenciasEquivalencia);

        $busqueda = $request->equivalenciaFiltro;
    }

    // ========== ATRIBUTOS CON TOLERANCIA ==========
    $tolerancias = [
        'DIAMETRO'                            => 2,
        'LARGO'                               => 3,
        'LARGO TOTAL'                         => 2,
        'ANCHO'                               => 2,
        'DISTANCIA ENTRE ORIFICIO DE MONTAJE' => 2,
        'ORIFICIO DE MONTAJE'                 => 2,
        'ALTURA'                              => 3,
        'DISTANCIA'                           => 2,
        'DIAMETRO INTERNO'                    => 2,
        'DIAMETRO EXTERNO'                    => 2,
        'DIAMETRO EXTERNO PIÑON'              => 2,
    ];

    // `atributo` / `atributoTwo`: el id de la característica (categoria.atributos). Un link viejo puede
    // traer `columna_N`: se traduce a la característica de ese nombre (LegacyAttributeMap).
    foreach ([['atributo', 'valorAttr'], ['atributoTwo', 'valorAttrTwo']] as [$campoAtributo, $campoValor]) {
        if (! $request->filled($campoAtributo) || ! $request->filled($campoValor)) {
            continue;
        }
        $caracteristica = Caracteristica::desdeParametro((string) $request->input($campoAtributo));
        if ($caracteristica === null) {
            continue;
        }
        $rawVal = trim((string) $request->input($campoValor));
        $val = str_replace(',', '.', $rawVal);
        $tolerancia = $tolerancias[mb_strtoupper(trim((string) $caracteristica->nombre))] ?? 0;

        $query->conValorDe((int) $caracteristica->id, function ($w) use ($val, $rawVal, $tolerancia): void {
            if (is_numeric($val)) {
                $n = (float) $val;
                $tolerancia > 0
                    ? $w->whereRaw("REPLACE(cpf.valor, ',', '.') + 0 BETWEEN ? AND ?", [$n - $tolerancia, $n + $tolerancia])
                    : $w->whereRaw("REPLACE(cpf.valor, ',', '.') + 0 = ?", [$n]);
            } else {
                $w->where('cpf.valor', 'LIKE', '%'.$rawVal.'%');
            }
        });
    }

    // ========= Filtros por estado =========
    if ($request->filled('nuevo')) {
        $query->where('estado', 1);
    }
    if ($request->filled('reconstruido')) {
        $query->where('estado', 2);
    }

    // ==============================
    // Traer productos desde la DB
    // ==============================
    // Los productos alcanzados por equivalencia ya vienen en esta misma query
    // (ver el OR de códigos equivalentes más arriba), con todos los filtros
    // aplicados.
    $productos = $query->orderBy('orden', 'desc')->get();

    // ==============================
    // Relevancia en PHP
    // ==============================
    if (!empty($busqueda)) {
        // Filtro «Por equivalencia»: la exacta primero, después las que
        // empiezan con el código y al final las que lo contienen.
        $puntajeEquivalencia = array_map([BusquedaPorEquivalencia::class, 'puntaje'], $coincidenciasEquivalencia);
        $productos = $buscador->ordenarPorRelevancia($productos, $busqueda, $puntajeEquivalencia);
    }

    // ==============================
    // Paginación manual
    // ==============================
    $page    = request()->get('page', 1);
    $perPage = 20;
    $offset  = ($page - 1) * $perPage;

    $paginatedItems = $productos->slice($offset, $perPage)->values();

    $productos = new \Illuminate\Pagination\LengthAwarePaginator(
        $paginatedItems,
        $productos->count(),
        $perPage,
        $page,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    // ==============================
    // Datos adicionales para la vista
    // ==============================
    $ventana       = 'categorias-nav';
    $marcas        = $this->catalogFilterOptions->brandsWithModels();
    $categoriasAll = Categoria::orderBy('nombre', 'asc')->get();
    $zonaclientes  = Auth::guard('web')->check();

    return view('frontend/productos-search', compact(
        'productos',
        'ventana',
        'busqueda',
        'marcas',
        'categoriasAll',
        'zonaclientes',
        'filtrosAplicados'
    ));
}




    /**
     * Inicio de la Zona de Clientes: las categorías y el margen de reventa.
     * Buscar y filtrar productos se hace desde el buscador del header y el
     * catálogo (CatalogoController), que muestran los precios del cliente.
     */
    public function productos_clientes(Request $request)
    {
        $categorias = Categoria::orderBy('orden')->orderBy('nombre')->get();
        $bonificaciones = Bonificacion::orderBy('orden')->get();

        $zonaclientes = true;
        $ventana = 'productos-nav';



        if (!session()->has('anuncio_mostrado') || now()->diffInHours(session('anuncio_mostrado')) >= 24) {
            $anuncio = Anuncio::find(1);
            session(['anuncio_mostrado' => now()]); // Guardar la hora actual
        } else {
            $anuncio = null; // No se muestra el anuncio
        }
        // $request->session()->forget('anuncio_mostrado');

        return view('frontend/productos-zona-privada', compact('anuncio', 'categorias', 'zonaclientes', 'ventana', 'bonificaciones'));
    }


    public function productos_clientes_filter(Request $request, BusquedaPorEquivalencia $equivalencias)
    {

        $anuncio = Anuncio::find(1);



        $categoria = $request->categoria;
        $producto = $request->producto;
        $codigo = $request->codigo;

        $query = Producto::with([
            'categoria',
            'portadaImagen',
            'caracteristicasVisibles',
        ]);
        $busqueda = '';

        if ($request->has('buscadorPrincipal') && $request->buscadorPrincipal) {
            $busqueda = $request->buscadorPrincipal;
            $query->where(function ($q) use ($busqueda) {
                $q->where('marca', 'LIKE', '%' . $busqueda . '%')
                    ->orWhere('nombre', 'LIKE', '%' . $busqueda . '%')
                    ->orWhere('codigo', 'LIKE', '%' . $busqueda . '%')
                    ->orWhere('modelo', 'LIKE', '%' . $busqueda . '%')
                    ->orWhereHas('equivalencias', function ($q) use ($busqueda) {
                        $q->where('valor', 'LIKE', '%' . $busqueda . '%')
                            ->orWhere('nombre', 'LIKE', '%' . $busqueda . '%');
                    })
                    ->orWhereHas('aplicaciones', function ($q) use ($busqueda) {
                        $q->where('valor', 'LIKE', '%' . $busqueda . '%')
                            ->orWhere('nombre', 'LIKE', '%' . $busqueda . '%');
                    })
                    ->orWhereHas('partesRelacionadas', function ($q) use ($busqueda) {
                        $q->where('codigo', 'LIKE', '%' . $busqueda . '%')
                            ->orWhere('nombre', 'LIKE', '%' . $busqueda . '%');
                    });
            });
        }

        if ($request->has('codigoBMH') && $request->codigoBMH) {
            $query->where('codigo', $request->codigoBMH);
            $busqueda = $request->codigoBMH;
        }


        if ($request->has('marca') && $request->marca) {
            $query->where('marca', $request->marca);
            $busqueda = $request->marca;
        }


        if ($request->has('modelo') && $request->modelo) {
            $query->where('modelo', $request->modelo);
            $busqueda = $request->modelo;
        }


        // Mismo filtro que el sitio público: antes acá sólo se buscaba el valor
        // exacto en las columnas viejas y no en las características, que es
        // donde está cargada la mayoría de las equivalencias.
        if ($request->filled('equivalenciaFiltro')) {
            $coincidencias = $equivalencias->coincidencias($request->equivalenciaFiltro);
            $equivalencias->filtrar($query, $coincidencias);
            $equivalencias->ordenar($query, $coincidencias);
        }


        if ($request->has('categoriaFiltro') && $request->categoriaFiltro) {
            $query->where('categoria_id', $request->categoriaFiltro);
            $busqueda = $request->categoriaFiltro;
        }

        // Medidas con ±2 de tolerancia, en las características ALTURA, LARGO y ANCHO.
        foreach (['alto' => 'ALTURA', 'largo' => 'LARGO', 'ancho' => 'ANCHO'] as $campo => $nombre) {
            $medida = $request->input($campo);
            $caracteristica = is_numeric($medida) ? Caracteristica::nombreOcupado($nombre) : null;
            if ($caracteristica !== null) {
                $query->conValorDe((int) $caracteristica->id, fn ($w) => $w->whereRaw(
                    "REPLACE(cpf.valor, ',', '.') + 0 BETWEEN ? AND ?", [(float) $medida - 2, (float) $medida + 2]
                ));
                $busqueda = $medida;
            }
        }

        if ($request->has('nuevo') && $request->nuevo) {
            $query->where('estado', 1);
        }
        if ($request->has('reconstruido') && $request->reconstruido) {
            $query->where('estado', 2);
        }

        $productos = $query->ordenado()
            ->orderBy('nombre')
            ->paginate(15)
            ->appends([
                'categoria' => $categoria,
                'producto' => $producto,
                'codigo' => $codigo
            ]);

        $categorias = Categoria::orderBy('orden')->orderBy('nombre')->get();
        $bonificaciones = Bonificacion::orderBy('orden')->get();

        $zonaclientes = true;
        $ventana = 'productos-nav';


        $marcas = $this->catalogFilterOptions->brandsWithModels();
        $categoriasAll = Categoria::orderBy('nombre', 'asc')->get();

        return view('frontend/productos-zona-privada', compact('anuncio', 'categorias', 'zonaclientes', 'ventana', 'bonificaciones'));
    }



    public function productos_home(Request $request)
    {

        $zonaclientes = true;
        $ventana = 'home-nav';
        $anuncio = Anuncio::find(1);



        return view('frontend/productos-zona-home',  compact('zonaclientes', 'ventana', 'anuncio'));
    }

    public function productos_vista(Request $request)
    {

        $zonaclientes = true;
        $ventana = 'home-nav';


        return view('frontend/productos-zona-home',  compact('zonaclientes', 'ventana'));
    }

    public function imagen_predeterminada(Request $request, LogosSitio $logos)
    {
        $logoDataUri = e($logos->dataUri(LogosSitio::HEADER_BLANCO));
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600" role="img" aria-labelledby="title description">
  <title id="title">Producto sin imagen</title>
  <desc id="description">Logo de BMH para productos sin imagen.</desc>
  <rect width="600" height="600" fill="#f5f6f7"/>
  <image href="{$logoDataUri}" x="100" y="220" width="400" height="160" preserveAspectRatio="xMidYMid meet"/>
</svg>
SVG;

        // El SVG lleva el logo embebido, que puede pesar más de 1 MB, y se pide
        // en cada página con productos sin foto. La URL no cambia cuando cambia
        // el logo, así que se guarda pero se revalida siempre: si el logo es el
        // mismo, el navegador recibe un 304 sin cuerpo.
        $response = response($svg, 200, ['Content-Type' => 'image/svg+xml']);
        $response->setEtag(md5($svg));
        $response->setPublic();
        $response->headers->addCacheControlDirective('no-cache');
        $response->isNotModified($request);

        return $response;
    }




    public function productos_clientes_buscar(Request $request)
    {
        $zonaclientes = true;
        $busqueda = $request->valor;
        $productos = Producto::whereHas('categoria', function ($query) use ($busqueda) {
            $query->where('nombre', 'like', '%' . $busqueda . '%');
        })
            ->orWhere('nombre', 'like', '%' . $request->valor . '%')
            ->ordenado()->paginate(20);
        return view('components/productos-clientes-listado', compact('productos', 'zonaclientes'));
    }

    public function lista_de_precios()
    {
        $zonaclientes = true;
        $listas = Descarga::where('sector', 'lista de precios')->get();
        $ventana = 'lista-nav';
        return view('frontend/lista-de-precios', compact('zonaclientes', 'listas', 'ventana'));
    }

public function dash_productos(Request $request)
{
    $categoria_id = $request->input('categoria_id');
    // El texto del buscador también viaja en la URL (?q=): así el listado se
    // abre ya filtrado al volver desde otra pantalla o al recargar.
    $busqueda = mb_substr(trim((string) $request->query('q', '')), 0, 100);

    $soloOfertas = $request->boolean('oferta');
    ['productos' => $productos, 'sinResultadosCategoria' => $sinResultadosCategoria] = $this->listadoAdmin($busqueda, $categoria_id, $soloOfertas);
    $productos->withPath(route('dashboard.productos'))
        ->appends(array_filter(['categoria_id' => $categoria_id, 'q' => $busqueda, 'oferta' => $soloOfertas ? 1 : null], fn ($v) => $v !== null && $v !== ''));
    $hayAlternativas = $sinResultadosCategoria && $productos->isNotEmpty();

    $categorias = Categoria::withCount('productos')->orderBy('nombre')->get();

    // Con una categoría elegida se ofrece descargar sus imágenes en un ZIP.
    $imagenesCategoria = (!empty($categoria_id) && $categoria_id != 'todos')
        ? app(ImagenesDeCategoria::class)->contar((int) $categoria_id)
        : null;

    // Reporte de la última «Actualizar desde Excel → Precios», si se acaba de subir.
    $reportePrecios = app(ActualizacionPreciosController::class)->reporte(session('precios_reporte'));
    $tokenPrecios = session('precios_reporte');

    return view('backend/dash-productos', compact('productos', 'categorias', 'categoria_id', 'imagenesCategoria', 'busqueda', 'sinResultadosCategoria', 'hayAlternativas', 'reportePrecios', 'tokenPrecios', 'soloOfertas'));
}

    public function exportarExcel(Request $request)
    {
        $categoriaId = $request->input('categoria_id');

        $query = Producto::with('categoria')->ordenado();
        if (!empty($categoriaId) && $categoriaId != 'todos') {
            $query->where('categoria_id', $categoriaId);
        }
        $productos = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        $headers = ['Código', 'Nombre / Descripción', 'Categoría', 'Precio'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($productos as $p) {
            $sheet->setCellValue("A{$row}", $p->codigo);
            $sheet->setCellValue("B{$row}", $p->nombre);
            $sheet->setCellValue("C{$row}", optional($p->categoria)->nombre ?? '');
            $sheet->setCellValue("D{$row}", $p->precio());
            $row++;
        }

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'productos' . (!empty($categoriaId) && $categoriaId != 'todos' ? '_categoria_' . $categoriaId : '_todos') . '.xlsx';

        $temp = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($temp);

        return response()->download($temp, $fileName)->deleteFileAfterSend(true);
    }


    public function create(Request $request)
    {
        $categorias = Categoria::orderBy('orden')->get();
        $medidas = Medida::orderBy('codigo')->get();
        $repuestos = Repuesto::orderBy('codigo')->get();
        $partesRelacionadas = collect();

        return view('backend/dash-producto-create', compact('categorias', 'medidas', 'repuestos', 'partesRelacionadas'));
    }
    
  


    public function store(Request $request, SincronizarCaracteristicasProducto $caracteristicas)
    {
        // Las características se validan antes de guardar nada: un valor mal escrito vuelve a su campo.
        $this->validarCaracteristicas($request, $caracteristicas);

        $nuevo_producto = new Producto();
        $nuevo_producto->nombre = $request->nombre;
        if ($request->has('oferta')) {
            $nuevo_producto->oferta = $request->boolean('oferta');
        }
        $nuevo_producto->orden = $request->orden;
        $nuevo_producto->codigo = $request->codigo;





        if ($request->nuevo) {

            $nuevo_producto->estado = 1;
        }


        if ($request->reconstruido) {
            $nuevo_producto->estado = 2;
        }

        // $nuevo_producto->caracteristicas = $request->caracteristicas;
        // $nuevo_producto->precio = $request->precio;
        $nuevo_producto->descripcion = $request->descripcion;
        $nuevo_producto->precio = $request->precio;
        $nuevo_producto->precioN = $request->precioN;
        $nuevo_producto->descuento = $request->descuento;

        $nuevo_producto->diametroInterno = $request->diametroInterno;
        $nuevo_producto->diametroExterno = $request->diametroExterno;
        $nuevo_producto->anchoBanda = $request->anchoBanda;
        $nuevo_producto->tolerancia = $request->tolerancia;
        $nuevo_producto->blindaje = $request->blindaje;

        $nuevo_producto->marca = $request->marca;
        $nuevo_producto->modelo = $request->modelo;





        // if ($request->hasFile('ficha')) {
        //     $files = $request->file('ficha');
        //     $nombreImagen = 'media_' . uniqid() . '.' . $files->getClientOriginalExtension();
        //     $files->move('archivos', $nombreImagen);
        //     $nuevo_producto->ficha = $nombreImagen;
        // }

        if (isset($request->categoria)) {
            $nuevo_producto->categoria_id = $request->categoria;
        }
        // $nuevo_producto->iva = $request->iva == '1' ? true : false;
        
        



        $nuevo_producto->orden_equivalencias = Producto::normalizarModoOrden($request->input('orden_equivalencias_mode'));
        $nuevo_producto->orden_aplicaciones = Producto::normalizarModoOrden($request->input('orden_aplicaciones_mode'));
        $nuevo_producto->orden_partes = Producto::normalizarModoOrden($request->input('orden_partes_mode'));

        $nuevo_producto->save();

        $this->sincronizarPartesRelacionadas($request, $nuevo_producto);

        $caracteristicas->ejecutar($nuevo_producto, $this->valoresCaracteristicas($request));

        $this->sincronizarEquivalencias($request, $nuevo_producto);

        $this->sincronizarAplicaciones($request, $nuevo_producto);

        if ($request->hasFile('imagenes')) {
            $files = $request->file('imagenes');

            // Recorrer cada archivo
            for ($i = 0; $i < count($files); $i++) {
                if ($files[$i]->isValid()) {
                    // Se guarda como WebP optimizado si conviene (ver OptimizadorImagenes).
                    $nombreImagen = $this->optimizador()->guardarSubida($files[$i], config('imagenes.directorio'));
                    $nueva_imagen = new Imagen();
                    $nueva_imagen->path = $nombreImagen;
                    $nueva_imagen->producto_id = $nuevo_producto->id;
                    $nueva_imagen->sector = 'producto';
                    if ($i == 0) {
                        $nueva_imagen->tipo = 'portada';
                    }
                    $nueva_imagen->save();
                }
            }
        }

        //  
        // if (isset($request->medidas)) {
        //     foreach ($request->medidas as $medida) {
        //         $resultado = Medida::where('id', $medida)->first();
        //         if ($resultado != null) {
        //             $nuevo_producto->medidas()->attach($resultado->id);
        //         }
        //     }
        // }

        // if (isset($request->repuestos)) {
        //     foreach ($request->repuestos as $medida) {
        //         $resultado = Repuesto::where('id', $medida)->first();
        //         if ($resultado != null) {
        //             $nuevo_producto->repuestos()->attach($resultado->id);
        //         }
        //     }
        // }



        return redirect()->back()->with('success', 'Producto creado exitosamente');
    }

    public function edit(Request $request)
    {
        $producto = Producto::with(['caracteristicas'])->find($request->id);
        $equivalencias = $producto->equivalencias()->orderBy('orden')->get();
        $aplicaciones = $producto->aplicaciones()->orderBy('orden')->get();
        $imagenes = Imagen::where('producto_id', $request->id)->where('sector', 'producto')->orderBy('orden')->get();
        $categorias = Categoria::with('caracteristicas')->orderBy('orden')->get();
        $categoriaSelected = Categoria::with('caracteristicas')->find($producto->categoria_id);
        $partesRelacionadas = $producto->partesRelacionadas()->with('portadaImagen')->get();
        // Todas las del producto (también las que su categoría no declara: no se ven en el sitio, pero
        // tienen que estar en el formulario para no perderlas al guardar).
        $caracteristicas = $producto->caracteristicas;



        return view('backend/dash-producto-edit', compact('producto',  'imagenes',  'categorias', 'equivalencias', 'aplicaciones', 'categoriaSelected', 'caracteristicas', 'partesRelacionadas'));
    }

    public function update(Request $request)
    {

        $producto = Producto::find($request->id);
        $sincronizar = app(SincronizarCaracteristicasProducto::class);
        // Las características se validan antes de guardar nada: un valor mal escrito vuelve a su campo.
        $this->validarCaracteristicas($request, $sincronizar);

        if ($request->hasFile('imagenes')) {
            $files = $request->file('imagenes');

            foreach ($files as $file) {
                // Verificar si el archivo se cargï¿½ï¿½ correctamente
                if ($file->isValid()) {
                    $nombreImagen = $this->optimizador()->guardarSubida($file, config('imagenes.directorio'));

                    $nueva_imagen = new Imagen();
                    $nueva_imagen->path = $nombreImagen;
                    $nueva_imagen->sector = 'producto';
                    $nueva_imagen->producto_id = $producto->id;
                    $nueva_imagen->save();
                }
            }
        }


        // if ($request->hasFile('ficha')) {
        //     $files = $request->file('ficha');
        //     $nombreImagen = 'media_' . uniqid() . '.' . $files->getClientOriginalExtension();
        //     $files->move('archivos', $nombreImagen);
        //     $producto->ficha = $nombreImagen;
        // }


        if (isset($request->categoria)) {
            $producto->categoria_id = $request->categoria;
        }

        $producto->nombre = $request->nombre;
        $producto->orden = $request->orden;
        $producto->codigo = $request->codigo;



        // $producto->precio = $request->precio;
        // $producto->caracteristicas = $request->caracteristicas;
        $producto->descripcion = $request->descripcion;

        $producto->diametroInterno = $request->diametroInterno;
        $producto->diametroExterno = $request->diametroExterno;
        $producto->anchoBanda = $request->anchoBanda;
        $producto->tolerancia = $request->tolerancia;
        $producto->blindaje = $request->blindaje;


        $producto->precio = $request->precio;
        $producto->precioN = $request->precioN;
        $producto->descuento = $request->descuento;
        $producto->estado = 0;

        $producto->marca = $request->marca;
        $producto->modelo = $request->modelo;

        if ($request->nuevo) {

            $producto->estado = 1;
        }


        if ($request->reconstruido) {
            $producto->estado = 2;
        }

        $producto->orden_equivalencias = Producto::normalizarModoOrden($request->input('orden_equivalencias_mode'));
        $producto->orden_aplicaciones = Producto::normalizarModoOrden($request->input('orden_aplicaciones_mode'));
        $producto->orden_partes = Producto::normalizarModoOrden($request->input('orden_partes_mode'));

        // Oferta (zona pública): sólo si el formulario la manda, así no se pierde al guardar.
        if ($request->has('oferta')) {
            $producto->oferta = $request->boolean('oferta');
        }

        $producto->save();

        $this->sincronizarPartesRelacionadas($request, $producto);

        $this->sincronizarEquivalencias($request, $producto);

        $this->sincronizarAplicaciones($request, $producto);

        // Sólo si el formulario trae la sección: así un guardado parcial no borra las características.
        if ($request->boolean('caracteristicas_presente')) {
            $sincronizar->ejecutar($producto, $this->valoresCaracteristicas($request));
        }


        return redirect()->back()->with('success', 'Producto actualizado');
    }

    /** @return array<int|string, mixed> caracteristica_id => valor, como viene del formulario */
    private function valoresCaracteristicas(Request $request): array
    {
        $valores = $request->input('caracteristicas', []);

        return is_array($valores) ? $valores : [];
    }

    /** Valida los valores de las características con el formato de cada una (SincronizarCaracteristicasProducto). */
    private function validarCaracteristicas(Request $request, SincronizarCaracteristicasProducto $caracteristicas): void
    {
        $errores = $caracteristicas->preparar($this->valoresCaracteristicas($request))['errores'];
        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Marca o desmarca un producto como oferta (zona pública) desde el listado del admin.
     * Con `oferta` en el pedido la deja así; sin él, la invierte. Responde el estado final.
     */
    public function actualizarOferta(Request $request): \Illuminate\Http\JsonResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'oferta' => ['sometimes', 'boolean'],
        ]);

        $producto = Producto::findOrFail($datos['producto_id']);
        $producto->oferta = array_key_exists('oferta', $datos) ? (bool) $datos['oferta'] : ! $producto->oferta;
        $producto->save();

        return response()->json(['producto_id' => $producto->id, 'oferta' => $producto->oferta]);
    }

    public function actualizarDestacado(Request $request)
    {

        $producto = Producto::find($request->producto_id);

        $producto->destacada = !$producto->destacada;

        $producto->save();
    }

    public function delete(Request $request)
    {
        $producto = Producto::find($request->id);

        $imagenes = Imagen::where('producto_id', $request->id)->where('sector', 'producto')->get();
        foreach ($imagenes as $imagen) {
            File::delete(public_path('imagenes/' . $imagen->path));
            $imagen->delete();
        }

        $producto->delete();

        return redirect()->back()->with('success', 'Producto eliminado');
    }

    public function imagen_update(Request $request)
    {
        $imagen = Imagen::find($request->id);
        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');

            // Verificar si el archivo se cargï¿½ï¿½ correctamente
            if ($file->isValid()) {
                $nombreImagen = $this->optimizador()->guardarSubida($file, config('imagenes.directorio'));
                File::delete(public_path('imagenes/' . $imagen->path));
                $imagen->path = $nombreImagen;
            }
        }
        $imagen->orden = $request->orden;
        $imagen->save();

        return redirect()->back()->with('success', 'Imagen actualizada');
    }

    public function portada_update(Request $request)
    {

        $anterior = Imagen::where('producto_id', $request->id_producto)->where('sector', 'producto')
            ->where('tipo', 'portada')->get()->first();
        if ($anterior) {
            $anterior->tipo = 'imagen';
            $anterior->save();
        }

        $imagen = Imagen::where('id', $request->id_imagen)
            ->where('producto_id', $request->id_producto)
            ->where('sector', 'producto')
            ->firstOrFail();
        $imagen->tipo = 'portada';
        $imagen->orden = 'aa';
        $imagen->save();

        return redirect()->back();
    }

    public function portada_delete(Request $request)
    {
        $imagen = Imagen::query()
            ->where('id', $request->id_imagen)
            ->where('producto_id', $request->id_producto)
            ->where('sector', 'producto')
            ->where('tipo', 'portada')
            ->firstOrFail();

        File::delete(public_path('imagenes/' . $imagen->path));
        $imagen->delete();

        return redirect()->back()->with('success', 'Imagen principal eliminada. El producto mostrará la imagen predeterminada de BMH.');
    }

    public function dash_buscar_producto(Request $request)
    {
        $busqueda = (string) $request->input('valor', '');
        $categoriaId = $request->input('categoria_id');

        ['productos' => $productos, 'sinResultadosCategoria' => $sinResultadosCategoria] = $this->listadoAdmin($busqueda, $categoriaId, $request->boolean('oferta'));

        return response()->json([
            'html' => view('backend/dash-productos-listado', compact('productos'))->render(),
            'pagination' => $productos->links('pagination::bootstrap-5')->toHtml(),
            'sinResultadosCategoria' => $sinResultadosCategoria,
            'hayAlternativas' => $sinResultadosCategoria && $productos->isNotEmpty(),
        ]);
    }

    /**
     * El listado de productos del admin, igual al cargar la página (con ?q= y
     * ?categoria_id= en la URL) que al buscar por AJAX.
     *
     * El código tiene prioridad sobre nombre, descripción y categoría, incluso
     * antes de paginar (Producto::busquedaAdmin, la misma búsqueda del Excel).
     * Si no hay coincidencias dentro de la categoría, se muestran las de otras
     * categorías, avisando, en vez de una lista vacía.
     *
     * @return array{productos: \Illuminate\Pagination\LengthAwarePaginator, sinResultadosCategoria: bool}
     */
    private function listadoAdmin(string $busqueda, $categoriaId, bool $soloOfertas = false): array
    {
        $hayBusqueda = trim($busqueda) !== '';
        $hayCategoria = $categoriaId !== null && $categoriaId !== '' && $categoriaId !== 'todos';
        $consulta = fn () => Producto::with(['categoria', 'portadaImagen'])
            ->busquedaAdmin($busqueda)
            ->when($soloOfertas, fn ($q) => $q->where('oferta', true))
            ->when($hayBusqueda, fn ($q) => $q->orderBy('orden'), fn ($q) => $q->ordenado());

        $productos = $consulta()->when($hayCategoria, fn ($q) => $q->where('categoria_id', $categoriaId))->paginate(20);
        $sinResultadosCategoria = false;

        if ($productos->isEmpty() && $hayBusqueda && $hayCategoria) {
            $sinResultadosCategoria = true;
            $productos = $consulta()->where('categoria_id', '!=', $categoriaId)->paginate(20);
        }

        return ['productos' => $productos, 'sinResultadosCategoria' => $sinResultadosCategoria];
    }

    public function actualizar_clientes(Request $request)
    {
        if ($request->file('lista')) {
            $file = $request->file('lista');
            $actualizados = 0;
            $creados = 0;

            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($file);

            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            foreach ($rows as $key => $row) {
                if ($key < 2) {
                    continue; // Saltar la primera fila, ya que son los encabezados
                }

                $codigo = $row[0] ?? null;
                $name = $row[1] ?? null;
                $descuento = $row[3] ?? null;
                $nombreUsuario = $row[4] ?? null;
                $password = $row[5] ?? null;

                if (strtoupper($descuento) === 'NETO') {
                    $descuento = 0;
                }

                if (strtoupper($descuento) === '') {
                    $descuento = 0;
                }






                $cliente = User::where('codigo', $codigo)->first();

                if ($cliente) {
                    $cliente->username = $nombreUsuario;
                    $cliente->name = $name;
                    $cliente->codigo = $codigo;
                    $cliente->descuento = $descuento;

                    $cliente->password = bcrypt($password);

                    $cliente->save();
                    $actualizados++;
                } else {

                    // Crear nuevo cliente
                    $cliente = new User();
                    $cliente->username = $nombreUsuario;
                    $cliente->name = $name;
                    $cliente->codigo = $codigo;
                    $cliente->descuento = $descuento;
                    $cliente->rol = 'cliente';
                    $cliente->habilitado = 1;
                    $cliente->password = bcrypt($password);
                    $cliente->save();


                    $creados++;
                }
            }

            return redirect()->back()->with('success', 'ActualizaciÃ³n exitosa: ' . $actualizados . ' clientes han sido actualizados y ' . $creados . ' clientes han sido creados.');
        } else {
            return redirect()->back()->with('warning', 'No se ha proporcionado un archivo Excel.');
        }
    }

    /**
     * Admin → Productos → «Actualizar desde Excel: Categorías». Cada fila trae el nombre de la categoría y,
     * en las columnas que siguen, los nombres de sus características (el mismo Excel de siempre: antes cada
     * columna era una de las 78 «columna_N»). Las características se buscan por nombre, sin distinguir
     * tildes ni mayúsculas, y las que no existen se crean. La categoría queda con las de su fila.
     */
    public function actualizar_categoriasExcel(Request $request)
    {
        if (! $request->file('lista')) {
            return redirect()->back()->with('warning', 'No se ha proporcionado un archivo Excel.');
        }

        $reader = IOFactory::createReaderForFile($request->file('lista'));
        $reader->setReadEmptyCells(false);
        $filas = $reader->load($request->file('lista'))->getActiveSheet()->toArray();

        $existentes = [];
        foreach (Caracteristica::query()->get() as $caracteristica) {
            $existentes[\App\Support\Caracteristicas\MigracionCaracteristicas::clave($caracteristica->nombre)] ??= $caracteristica;
        }
        $actualizadas = 0;
        $creadas = 0;
        $noEncontradas = [];

        DB::transaction(function () use ($filas, &$existentes, &$actualizadas, &$creadas, &$noEncontradas): void {
            foreach ($filas as $indice => $fila) {
                $nombreCategoria = trim((string) ($fila[0] ?? ''));
                if ($indice < 1 || $nombreCategoria === '') {
                    continue; // La primera fila son los títulos.
                }
                $categoria = Categoria::where('nombre', $nombreCategoria)->first();
                if ($categoria === null) {
                    $noEncontradas[] = $nombreCategoria;

                    continue;
                }

                $ids = [];
                foreach (array_slice($fila, 1) as $celda) {
                    $nombre = Caracteristica::normalizarNombre(is_scalar($celda) ? (string) $celda : '');
                    if ($nombre === '') {
                        continue;
                    }
                    $clave = \App\Support\Caracteristicas\MigracionCaracteristicas::clave($nombre);
                    if (! isset($existentes[$clave])) {
                        $existentes[$clave] = Caracteristica::query()->create(['nombre' => $nombre, 'orden' => Caracteristica::siguienteOrden()]);
                        $creadas++;
                    }
                    $ids[] = $existentes[$clave]->id;
                }
                $categoria->caracteristicas()->sync(array_values(array_unique($ids)));
                $actualizadas++;
            }
        });

        $mensaje = "Listo: se actualizaron las características de {$actualizadas} ".($actualizadas === 1 ? 'categoría' : 'categorías')
            .($creadas > 0 ? " y se crearon {$creadas} ".($creadas === 1 ? 'característica nueva' : 'características nuevas') : '').'.';
        if ($noEncontradas !== []) {
            return redirect()->back()->with('warning', $mensaje.' No encontré estas categorías (revisá el nombre): '.implode(', ', $noEncontradas).'.');
        }

        return redirect()->back()->with('success', $mensaje);
    }

    private function handleImages($producto, $imagenesTexto)
    {
        if ($imagenesTexto) {
            $imagenesURLs = array_map('trim', explode(',', $imagenesTexto));
            $imagenesNombres = array_map(fn ($url) => $this->nombreImagenVigente(basename($url)), $imagenesURLs);

            if (count($imagenesNombres) > 0) {
                $primeraImagen = array_shift($imagenesNombres);

                $imagenPortada = Imagen::where('producto_id', $producto->id)->where('sector', 'portada')->first();

                if ($imagenPortada) {
                    File::delete(public_path('imagenes/' . $imagenPortada->path));
                    $imagenPortada->path = $primeraImagen;
                    $imagenPortada->save();
                } else {
                    $imagenPortada = new Imagen();
                    $imagenPortada->path = $primeraImagen;
                    $imagenPortada->sector = 'producto';
                    $imagenPortada->producto_id = $producto->id;
                    $imagenPortada->tipo = 'portada';
                    $imagenPortada->orden = 'aa';
                    $imagenPortada->save();
                }

                foreach ($imagenesNombres as $nombreImagen) {
                    $imagenProducto = new Imagen();
                    $imagenProducto->path = $nombreImagen;
                    $imagenProducto->sector = 'producto';
                    $imagenProducto->producto_id = $producto->id;
                    $imagenProducto->tipo = 'imagen';
                    $imagenProducto->save();
                }
            }
        }
    }

    private function handleEquivalencias($producto, $equivalencias)
    {
        if (! $equivalencias) {
            return;
        }

        // El Excel trae códigos sueltos separados por coma, sin etiqueta.
        // Van siempre en mayúsculas, como en el formulario.
        $valores = collect(explode(',', $equivalencias))
            ->map(fn ($valor): string => mb_strtoupper(trim((string) $valor)))
            ->filter(fn (string $valor): bool => $valor !== '')
            ->unique()
            ->values();

        DB::transaction(function () use ($valores, $producto): void {
            $producto->equivalencias()->delete();

            if ($valores->isEmpty()) {
                return;
            }

            $now = now();
            DB::table('equivalencias')->insert($valores->map(fn (string $valor, int $i): array => [
                'producto_id' => $producto->id,
                'nombre' => null,
                'valor' => mb_substr($valor, 0, 255),
                'orden' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    private function handleAplicaciones($producto, $aplicaciones)
    {
        if (! $aplicaciones) {
            return;
        }

        // El Excel trae modelos sueltos separados por coma, sin etiqueta.
        $valores = collect(explode(',', $aplicaciones))
            ->map(fn ($valor): string => trim((string) $valor))
            ->filter(fn (string $valor): bool => $valor !== '')
            ->unique()
            ->values();

        DB::transaction(function () use ($valores, $producto): void {
            $producto->aplicaciones()->delete();

            if ($valores->isEmpty()) {
                return;
            }

            $now = now();
            DB::table('aplicaciones')->insert($valores->map(fn (string $valor, int $i): array => [
                'producto_id' => $producto->id,
                'nombre' => null,
                'valor' => mb_substr($valor, 0, 255),
                'orden' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    private function handleCategoria($producto, $categoriaNombre)
    {
        if ($categoriaNombre) {
            $categoria = Categoria::where('nombre', $categoriaNombre)->first();

            if ($categoria) {
                $producto->categoria_id = $categoria->id;
            }
        }
    }

    private function mapEstado($estado)
    {
        if ($estado == 'NUEVO') {
            return 1;
        } elseif ($estado == 'RECONSTRUIDO') {
            return 2;
        } else {
            return 0;
        }
    }

    public function actualizarPorcentaje(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tipo'        => 'required|in:aumentar,descontar',
            'porcentaje'  => 'required|numeric'
        ]);

        $producto = Producto::find($request->producto_id);

        if ($request->tipo === 'aumentar') {
            $producto->aumento = $request->porcentaje;
            $producto->descuento = 0;
        } else {
            $producto->descuento = $request->porcentaje;
            $producto->aumento = 0;
        }

        $producto->save();

        return redirect()->back()->with('success', 'Porcentaje actualizado correctamente en el producto');
    }

    /**
     * Búsqueda JSON para el selector de partes relacionadas del admin.
     * Optimizado para 5k+ productos: sólo columnas necesarias, límite fijo y
     * portada eager-loaded (portadaUrl() verifica existencia en disco).
     */
    public function buscarPartes(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $excludeId = (int) $request->input('exclude', 0);

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';

        $productos = Producto::query()
            ->with('portadaImagen')
            ->select(['id', 'codigo', 'nombre', 'marca', 'modelo'])
            ->where(function ($query) use ($like): void {
                $query->where('codigo', 'LIKE', $like)
                    ->orWhere('nombre', 'LIKE', $like)
                    ->orWhere('marca', 'LIKE', $like)
                    ->orWhere('modelo', 'LIKE', $like);
            })
            ->when($excludeId > 0, fn ($query) => $query->where('id', '!=', $excludeId))
            // Los códigos que empiezan con lo buscado primero: es lo más común.
            ->orderByRaw('codigo LIKE ? DESC', [$q . '%'])
            ->orderBy('nombre')
            ->limit(8)
            ->get();

        return response()->json([
            'data' => $productos->map(fn (Producto $producto): array => [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'marca' => $producto->marca,
                'modelo' => $producto->modelo,
                'portada_url' => $producto->portadaUrl(),
            ]),
        ]);
    }

    /**
     * Normaliza el `orden` de una fila de equivalencias / aplicaciones / partes.
     * La columna es VARCHAR(2), así que el resultado son como mucho dos
     * caracteres alfanuméricos.
     *
     * El input `*_orden[]` no viaja cuando el criterio de orden no es "manual"
     * (el front lo deja de solo lectura) o cuando la fila es nueva y quedó sin
     * código. En ese caso se deriva de la posición con el mismo esquema que usa
     * el front (aa, ab, …, zz): usar la posición cruda desbordaba la columna a
     * partir de la fila 100 y reventaba el guardado con un 1406.
     */
    private static function normalizarOrden(mixed $raw, int $i): string
    {
        $limpio = substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $raw), 0, 2);

        return $limpio !== '' ? $limpio : self::codigoOrden($i);
    }

    /** Posición → código de dos letras (aa, ab, …, zz). */
    private static function codigoOrden(int $i): string
    {
        $i = max(0, min($i, 675)); // 26 * 26 - 1

        return chr(97 + intdiv($i, 26)) . chr(97 + $i % 26);
    }

    /**
     * Reemplaza la lista de partes relacionadas por la enviada en el form
     * (`partes[]`), asignando `orden` según el orden de llegada. Ignora ids
     * inexistentes y el propio producto. Si el form no incluyó la sección
     * (`partes_presente` ausente), no toca nada.
     */
    private function sincronizarPartesRelacionadas(Request $request, Producto $producto): void
    {
        if (! $request->boolean('partes_presente')) {
            return; // El form no incluyó la sección: no tocar nada.
        }

        // Preservar el ORDEN del formulario: el número viene del input
        // `parte_orden[]` (alineado por posición con `partes[]`). Si falta,
        // se usa la posición. Se permiten números repetidos (mismo lugar).
        $ids = (array) $request->input('partes', []);
        $ordenes = (array) $request->input('parte_orden', []);

        $filasParte = collect($ids)
            ->map(function ($value, $i) use ($ordenes): array {
                $rawOrden = $ordenes[$i] ?? null;
                return [
                    'id' => (int) $value,
                    'orden' => self::normalizarOrden($rawOrden, $i),
                ];
            })
            ->filter(fn (array $f): bool => $f['id'] > 0 && $f['id'] !== (int) $producto->id);

        // Quitar ids duplicados conservando el primer orden encontrado.
        $vistos = [];
        $filasParte = $filasParte
            ->filter(function (array $f) use (&$vistos): bool {
                if (in_array($f['id'], $vistos, true)) {
                    return false;
                }
                $vistos[] = $f['id'];
                return true;
            })
            ->values();

        $existentes = Producto::whereIn('id', $filasParte->pluck('id')->all())->pluck('id')->all();
        $filasParte = $filasParte->filter(fn (array $f): bool => in_array($f['id'], $existentes, true))->values();

        DB::transaction(function () use ($filasParte, $producto): void {
            $cambios = $producto->partesRelacionadas()->sync(
                $filasParte->mapWithKeys(fn (array $f): array => [$f['id'] => ['orden' => $f['orden']]])->all(),
            );

            // Del otro lado también: la parte tiene al producto como parte relacionada.
            $this->partesReciprocas->sincronizar(
                (int) $producto->id,
                $filasParte->pluck('id')->all(),
                $cambios['detached'],
            );
        });
    }

    /**
     * Reemplaza las equivalencias del producto por las que viajan en el form
     * (equiv_nombre[] + equiv_valor[], fila por fila). El orden es la posición.
     */
    private function sincronizarEquivalencias(Request $request, Producto $producto): void
    {
        if (! $request->boolean('equivalencias_presente')) {
            return; // El form no incluyó la sección: no tocar nada.
        }

        $nombres = (array) $request->input('equiv_nombre', []);
        $valores = (array) $request->input('equiv_valor', []);
        $ordenes = (array) $request->input('equiv_orden', []);

        $filas = collect($valores)
            ->map(function ($valor, $i) use ($nombres, $ordenes): array {
                $rawOrden = $ordenes[$i] ?? null;
                return [
                    // Origen y código van siempre en mayúsculas.
                    'nombre' => mb_strtoupper(trim((string) ($nombres[$i] ?? ''))),
                    'valor' => mb_strtoupper(trim((string) $valor)),
                    'orden' => self::normalizarOrden($rawOrden, $i),
                ];
            })
            // Conservar filas con solo el origen o solo el código. Una fila
            // completamente vacía sigue siendo un borrador y se descarta.
            ->filter(fn (array $fila): bool => $fila['nombre'] !== '' || $fila['valor'] !== '')
            ->values();

        DB::transaction(function () use ($filas, $producto): void {
            $producto->equivalencias()->delete();

            if ($filas->isEmpty()) {
                return;
            }

            $now = now();
            DB::table('equivalencias')->insert($filas->map(fn (array $fila, int $i): array => [
                'producto_id' => $producto->id,
                'nombre' => $fila['nombre'] !== '' ? $fila['nombre'] : null,
                'valor' => mb_substr($fila['valor'], 0, 255),
                'orden' => $fila['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    /**
     * Reemplaza las aplicaciones del producto por las que viajan en el form
     * (aplic_nombre[] + aplic_valor[], fila por fila). El orden es la posición.
     * Idéntico a sincronizarEquivalencias pero sobre la tabla `aplicaciones`.
     */
    private function sincronizarAplicaciones(Request $request, Producto $producto): void
    {
        if (! $request->boolean('aplicaciones_presente')) {
            return; // El form no incluyó la sección: no tocar nada.
        }

        $nombres = (array) $request->input('aplic_nombre', []);
        $valores = (array) $request->input('aplic_valor', []);
        $ordenes = (array) $request->input('aplic_orden', []);

        $filas = collect($valores)
            ->map(function ($valor, $i) use ($nombres, $ordenes): array {
                $rawOrden = $ordenes[$i] ?? null;
                return [
                    // Origen y modelo van siempre en mayúsculas.
                    'nombre' => mb_strtoupper(trim((string) ($nombres[$i] ?? ''))),
                    'valor' => mb_strtoupper(trim((string) $valor)),
                    'orden' => self::normalizarOrden($rawOrden, $i),
                ];
            })
            // Conservar filas con solo el origen o solo el valor. Una fila
            // completamente vacía sigue siendo un borrador y se descarta.
            ->filter(fn (array $fila): bool => $fila['nombre'] !== '' || $fila['valor'] !== '')
            ->values();

        DB::transaction(function () use ($filas, $producto): void {
            $producto->aplicaciones()->delete();

            if ($filas->isEmpty()) {
                return;
            }

            $now = now();
            DB::table('aplicaciones')->insert($filas->map(fn (array $fila, int $i): array => [
                'producto_id' => $producto->id,
                'nombre' => $fila['nombre'] !== '' ? $fila['nombre'] : null,
                'valor' => mb_substr($fila['valor'], 0, 255),
                'orden' => $fila['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }
}
