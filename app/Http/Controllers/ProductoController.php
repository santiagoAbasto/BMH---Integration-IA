<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Bonificacion;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Caracteristica;
use App\Models\CaracteristicaProducto;
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
use App\Services\CatalogFilterOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductoController extends Controller
{
    public function __construct(
        private readonly CatalogFilterOptions $catalogFilterOptions,
    ) {
    }

    public function index(Request $request)
    {
        $categoriaId = $request->input('categoria');
        $busqueda = trim((string) $request->input('search', ''));

        $query = Producto::with([
            'categoria',
            'portadaImagen',
            'imagenesGaleria',
            'productCaracteristicas.caracteristica',
            'partesRelacionadas.portadaImagen',
            'equivalencias',
            'aplicaciones',
        ]);

        if ((int) $categoriaId === 0) {
            $query->where(function ($query) use ($busqueda): void {
                $query->where('nombre', 'LIKE', '%' . $busqueda . '%')
                    ->orWhereHas('categoria', function ($query) use ($busqueda): void {
                        $query->where('nombre', 'LIKE', '%' . $busqueda . '%');
                    });
            });
        } else {
            $query->where('categoria_id', $categoriaId);
        }

        $productos = $query->orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();
        $categoria = $categorias->firstWhere('id', (int) $categoriaId);
        $marcas = $this->catalogFilterOptions->brandsWithModels();

        $productos->each(function (Producto $producto): void {
            $producto->setRelation(
                'productCaracteristicas',
                $producto->productCaracteristicas
                    ->sortBy(fn ($productCaracteristica) => $productCaracteristica->caracteristica->orden ?? PHP_INT_MAX)
                    ->values(),
            );
        });

        $ruta = 'categorias';
        $zonaclientes = Auth::guard('web')->check();
        $categoriasAll = $categorias;

        return view('frontend/productos', compact(
            'zonaclientes',
            'productos',
            'marcas',
            'categoriasAll',
            'categorias',
            'ruta',
            'categoriaId',
            'busqueda',
            'categoria',
        ))->with('categoria_id', $categoriaId);
    }



    public function load(Request $request)
    {
        $categoria_id = $request->categoria_id;
        $with = [
            'categoria',
            'portadaImagen',
            'imagenesGaleria',
            'productCaracteristicas.caracteristica',
            'partesRelacionadas.portadaImagen',
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
                ->orderBy('orden')->get();
        } else {
            $busqueda = '';
            $productos = Producto::with($with)
                ->whereHas('categoria', function ($query) use ($categoria_id) {
                $query->where('id', $categoria_id);
            })->orderBy('orden')->get();
        }

        $productos = $productos->skip($request->contador)->take($request->xpag);
        $productos->each(function (Producto $prod): void {
            $prod->setRelation(
                'productCaracteristicas',
                $prod->productCaracteristicas
                    ->sortBy(fn ($pc) => $pc->caracteristica->orden ?? PHP_INT_MAX)
                    ->values(),
            );
        });

        return view('frontend/productos-listado', compact('productos'));
    }

    public function ofertas()
    {
        $productos = Producto::with(['categoria', 'portadaImagen', 'productCaracteristicas.caracteristica'])
            ->where('descuento', '!=', 0)
            ->orderBy('orden')
            ->get();
        $ventana = 'ofertas-nav';
        return view('frontend.ofertas', compact('productos', 'ventana'));
    }

    public function producto(Request $request)
{
    // Traer producto con sus caracterÃ­sticas
    $producto = Producto::with([
        'categoria',
        'portadaImagen',
        'productCaracteristicas.caracteristica',
        'usos',
        'dimensiones',
        'equivalencias',
        'aplicaciones',
        'partesRelacionadas.portadaImagen',
    ])->find($request->id);

    // Productos relacionados (mismo formato horizontal que el listado principal)
    if ($producto->categoria != null) {
        $productos = Producto::with([
                'categoria',
                'portadaImagen',
                'imagenesGaleria',
                'productCaracteristicas.caracteristica',
                'partesRelacionadas.portadaImagen',
                'equivalencias',
                'aplicaciones',
            ])
            ->where('categoria_id', $producto->categoria->id)
            ->where('id', '!=', $producto->id)
            ->orderBy('orden')
            ->limit(6)
            ->get();
        $productos->each(function (Producto $prod) use ($producto): void {
            $prod->setRelation(
                'productCaracteristicas',
                $prod->productCaracteristicas
                    ->sortBy(fn ($pc) => $pc->caracteristica->orden ?? PHP_INT_MAX)
                    ->values(),
            );
        });
    } else {
        $productos = null;
    }

    // ImÃ¡genes
    $imagenes = Imagen::where('producto_id', $producto->id)->where('tipo', '!=', 'portada')->get();

    // IVA y categorÃ­as
    $iva = Impuesto::find(1);
    $categorias = Categoria::orderBy('orden')->get();
    $categoria_id = $producto->categoria->id ?? null;
    $categoria = $producto->categoria;
    $usos = $producto->usos;
    $dimensiones = $producto->dimensiones;
    $ventana = 'categorias-nav';

    // Otras variables
    $categoriasAll = Categoria::orderBy('nombre', 'asc')->get();
    $marcas = $this->catalogFilterOptions->brandsWithModels();
    $zonaclientes = Auth::guard('web')->check();

    // ðŸ”¹ Datos extra como en edit()
    $imagenesProducto = Imagen::where('producto_id', $request->id)->where('sector', 'producto')->orderBy('orden')->get();
    $categoriaSelected = Categoria::with('caracteristicas')->find($producto->categoria_id);
     $caracteristicas = $producto->productCaracteristicas
         ->sortByDesc(fn ($pc) => $pc->caracteristica->orden ?? PHP_INT_MIN)
         ->map(function ($pc) {
             $caracteristica = $pc->caracteristica;
             $caracteristica->valor = $pc->valor;

             return $caracteristica;
         })
         ->values();

        

    return view('frontend/producto', compact(
        'zonaclientes',
        'producto',
        'productos',
        'imagenes',
        'iva',
        'categorias',
        'categoria_id',
        'usos',
        'dimensiones',
        'ventana',
        'categoriasAll',
        'marcas',
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
            'productCaracteristicas.caracteristica',
            'partesRelacionadas.portadaImagen',
            'equivalencias',
            'aplicaciones',
        ];
        $categoria = $request->categoria;
        if ($categoria == '') {
            $productos = Producto::with($with)
                ->orderBy('orden')
                ->get();
        } else {
            $productos = Producto::with($with)
                ->whereHas('categoria', function ($query) use ($categoria) {
                $query->where('id', $categoria);
            })->orderBy('orden')->get();
        }

        $productos->each(function (Producto $prod): void {
            $prod->setRelation(
                'productCaracteristicas',
                $prod->productCaracteristicas
                    ->sortBy(fn ($pc) => $pc->caracteristica->orden ?? PHP_INT_MAX)
                    ->values(),
            );
        });

        return view('frontend/productos-listado', compact('productos'));
    }

    // public function filtroRodamiento(Request $request)
    // {



    //     $query = Producto::query();
    //     $busqueda = '';

    //     if ($request->has('buscadorPrincipal') && $request->buscadorPrincipal) {
    //         $busqueda = $request->buscadorPrincipal;
    //         $query->where(function ($q) use ($busqueda) {
    //             $q->where('marca', 'LIKE', '%' . $busqueda . '%')
    //                 ->orWhere('nombre', 'LIKE', '%' . $busqueda . '%')
    //                 ->orWhere('codigo', 'LIKE', '%' . $busqueda . '%')
    //                 ->orWhere('modelo', 'LIKE', '%' . $busqueda . '%')
    //                 ->orWhereHas('equivalencias', function ($q) use ($busqueda) {
    //                     $q->where('descripcion', 'LIKE', '%' . $busqueda . '%');
    //                 });
    //         });
    //     }

    //     if ($request->has('codigoBMH') && $request->codigoBMH) {
    //         $query->where('codigo', 'LIKE', '%' . $request->codigoBMH . '%');
    //         $busqueda = $request->codigoBMH;
    //     }


    //     if ($request->has('marca') && $request->marca) {
    //         $query->where('marca', $request->marca);
    //         $busqueda = $request->marca;
    //     }


    //     if ($request->has('modelo') && $request->modelo) {
    //         $query->where('modelo', $request->modelo);
    //         $busqueda = $request->modelo;
    //     }

    //     if ($request->has('equivalenciaFiltro') && $request->equivalenciaFiltro) {
    //         $equivalenciaFiltro = $request->equivalenciaFiltro;

    //         // Eliminar espacios del filtro de bÃºsqueda
    //         $equivalenciaFiltroSinEspacios = str_replace(' ', '', $equivalenciaFiltro);

    //         // Obtener todas las columnas de la tabla productos
    //         $columns = Schema::getColumnListing('productos');

    //         // Filtrar solo las columnas desde columna_1 hasta columna_74
    //         $filteredColumns = array_filter($columns, function ($column) {
    //             return preg_match('/^columna_(\d+)$/', $column, $matches) && $matches[1] >= 1 && $matches[1] <= 78;
    //         });

    //         $query->where(function ($q) use ($equivalenciaFiltro, $equivalenciaFiltroSinEspacios, $filteredColumns) {
    //             foreach ($filteredColumns as $column) {
    //                 $q->orWhere(function ($subQuery) use ($column, $equivalenciaFiltro, $equivalenciaFiltroSinEspacios) {
    //                     // Eliminar los espacios en la columna de la base de datos para la comparaciÃ³n
    //                     $subQuery->where(DB::raw("REPLACE($column, ' ', '')"), '=', $equivalenciaFiltroSinEspacios)
    //                         // Coincidencia con el valor completo sin espacios o que empiece con el valor
    //                         ->orWhere(DB::raw("REPLACE($column, ' ', '')"), 'LIKE', $equivalenciaFiltroSinEspacios . '%')
    //                         // Coincidencia con el valor que contiene un guion
    //                         ->orWhere(DB::raw("REPLACE($column, ' ', '')"), 'LIKE', $equivalenciaFiltroSinEspacios . '-%');
    //                 });
    //             }
    //         });
    //     }

    //     if ($request->has('categoriaFiltro') && $request->categoriaFiltro) {
    //         $query->where('categoria_id', $request->categoriaFiltro);
    //         $busqueda = $request->categoriaFiltro;
    //     }

    //     if ($request->has('atributo') && $request->atributo) {
    //         $atributo = $request->atributo;
    //         $valorAttr = $request->valorAttr;

    //         // Eliminar cualquier letra o carÃ¡cter no numÃ©rico
    //         $valorAttr = filter_var($valorAttr, FILTER_SANITIZE_NUMBER_INT);

    //         // Buscar productos en el rango alrededor del valor (Â±3)
    //         $query->whereBetween($atributo, [$valorAttr - 3, $valorAttr + 3]);
    //         $busqueda = $valorAttr;
    //     }

    //     if ($request->has('atributoTwo') && $request->atributoTwo) {
    //         $atributoTwo = $request->atributoTwo;
    //         $valorAttrTwo = $request->valorAttrTwo;

    //         // Eliminar cualquier letra o carÃ¡cter no numÃ©rico
    //         $valorAttrTwo = filter_var($valorAttrTwo, FILTER_SANITIZE_NUMBER_INT);

    //         // Buscar productos en el rango alrededor del valor (Â±3)
    //         $query->whereBetween($atributoTwo, [$valorAttrTwo - 3, $valorAttrTwo + 3]);
    //         $busqueda = $valorAttrTwo;
    //     }

    //     if ($request->has('nuevo') && $request->nuevo) {
    //         $query->where('estado', 1);
    //     }
    //     if ($request->has('reconstruido') && $request->reconstruido) {
    //         $query->where('estado', 2);
    //     }

    //     $productos = $query->orderBy('orden', 'desc')->get();

    //     $ventana = 'categorias-nav';

    //     $productosAll = Producto::all();
    //     $marcas = $productosAll->groupBy('marca')
    //         ->sortKeys() // Ordena las marcas alfabÃ©ticamente
    //         ->map(function ($productosPorMarca) {
    //             return $productosPorMarca->groupBy('modelo')->sortKeys(); // Ordena los modelos alfabÃ©ticamente
    //         });
    //     $categoriasAll = Categoria::orderBy('nombre', 'asc')->get();



    //     $zonaclientes = false;

    //     if (Auth::guard('web')->check()) {
    //         $zonaclientes = true;
    //     }



    //     return view('frontend/productos-search', compact('productos',  'ventana',  'busqueda', 'productosAll', 'marcas', 'categoriasAll', 'zonaclientes'));
    // }

public function filtroRodamiento(Request $request)
{
    $busqueda = '';
    $filtrosAplicados = [];
    $equivalenciasCodigos = [];

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
        'productCaracteristicas.caracteristica',
        'partesRelacionadas.portadaImagen',
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
        $busquedaLower = mb_strtolower($busqueda);
        $busquedaSinEspacios = preg_replace('/\s+/', '', $busquedaLower);

        // ¿Es un código? → alfanumérico sin espacios y con al menos un dígito
        $esCodigo = preg_match('/^[0-9A-Za-z\-]+$/', $busquedaSinEspacios)
                 && preg_match('/[0-9]/', $busquedaSinEspacios);

        // ====== 1) si es código: obtener equivalencias del producto base (viejo + nuevo) ======
        if ($esCodigo) {

            // columnas de equivalencias (columna_1, columna_2, etc.) → sistema viejo
            $columnas  = Schema::getColumnListing('productos');
            $colsEquiv = array_filter($columnas, fn($c) => preg_match('/^columna_\d+$/', $c));

            // ids de características de tipo equivalencia → sistema nuevo
            $caracteristicasEquivIds = Caracteristica::where(function ($q) {
                    $q->where('nombre', 'LIKE', '%EQUIVALENCIA%')
                      ->orWhere('nombre', 'LIKE', '%BMH%')
                      ->orWhere('nombre', 'LIKE', '%Nº ORIGINAL%')
                      ->orWhere('nombre', 'LIKE', '%NUMERO ORIGINAL%');
                })
                ->pluck('id');

            // 1) Producto base por codigo (ej: IMPO1747 o 1061)
            $productoBase = Producto::whereRaw(
                "LOWER(REPLACE(codigo,' ','')) = ?",
                [$busquedaSinEspacios]
            )->first();

            if ($productoBase) {
                // 1.a) equivalencias viejas en columnas_X
                foreach ($colsEquiv as $col) {
                    $valor = $productoBase->{$col};
                    if (!$valor) continue;

                    foreach (preg_split('/[,\s]+/', (string)$valor) as $c) {
                        $c = trim($c);
                        if ($c === '') continue;
                        $equivalenciasCodigos[] = $c;
                    }
                }

                // 1.b) equivalencias nuevas en producto_caracteristica (todas las características)
                $valoresCarac = DB::table('producto_caracteristica')
                    ->where('producto_id', $productoBase->id)
                    ->pluck('valor')
                    ->toArray();

                foreach ($valoresCarac as $valor) {
                    foreach (preg_split('/[,\s]+/', (string)$valor) as $c) {
                        $c = trim($c);
                        if ($c === '') continue;
                        $equivalenciasCodigos[] = $c;
                    }
                }
                // 1.c) tablas nuevas: equivalencias / aplicaciones / partes (conviven con legacy)
                $valoresEquivNuevas = DB::table('equivalencias')->where('producto_id', $productoBase->id)->pluck('valor')->toArray();
                foreach ($valoresEquivNuevas as $valor) {
                    foreach (preg_split('/[,\s]+/', (string)$valor) as $c) {
                        $c = trim($c);
                        if ($c === '') continue;
                        $equivalenciasCodigos[] = $c;
                    }
                }
                $valoresAplic = DB::table('aplicaciones')->where('producto_id', $productoBase->id)->pluck('valor')->toArray();
                foreach ($valoresAplic as $valor) {
                    foreach (preg_split('/[,\s]+/', (string)$valor) as $c) {
                        $c = trim($c);
                        if ($c === '') continue;
                        $equivalenciasCodigos[] = $c;
                    }
                }
                $codigosPartes = DB::table('partes_relacionadas as pr')->join('productos as p2', 'p2.id', '=', 'pr.parte_id')->where('pr.producto_id', $productoBase->id)->pluck('p2.codigo')->toArray();
                foreach ($codigosPartes as $c) {
                    $c = trim((string)$c);
                    if ($c === '') continue;
                    $equivalenciasCodigos[] = $c;
                }
            }

            // 2) Productos donde ALGUNA equivalencia contiene el código buscado (1061, 6201, etc.)

            // 2.a) sistema viejo: buscar en columnas_X
            if (!empty($colsEquiv)) {
                $productosRelacionados = Producto::where(function ($q) use ($colsEquiv, $busquedaSinEspacios) {
                    foreach ($colsEquiv as $col) {
                        $q->orWhereRaw("LOWER(REPLACE($col,' ','')) LIKE ?", ['%' . $busquedaSinEspacios . '%']);
                    }
                })->get();

                foreach ($productosRelacionados as $p) {
                    if (!empty($p->codigo)) {
                        $equivalenciasCodigos[] = $p->codigo;
                    }
                }
            }

            // 2.b) sistema nuevo: buscar en TODAS las producto_caracteristica.valor
            if (!empty($busquedaSinEspacios)) {
                $productosPorCarac = Producto::whereIn('id', function ($sub) use ($busquedaSinEspacios) {
                        $sub->select('producto_id')
                            ->from('producto_caracteristica')
                            ->whereRaw("LOWER(REPLACE(valor,' ','')) LIKE ?", ["%{$busquedaSinEspacios}%"]);
                    })
                    ->get();

                foreach ($productosPorCarac as $p) {
                    if (!empty($p->codigo)) {
                        $equivalenciasCodigos[] = $p->codigo;
                    }
                }
            }
            // 2.c) tablas nuevas: equivalencias / aplicaciones / partes
            if (!empty($busquedaSinEspacios)) {
                $productosPorEquivNueva = Producto::whereIn('id', function ($sub) use ($busquedaSinEspacios) {
                        $sub->select('producto_id')->from('equivalencias')
                            ->whereRaw("LOWER(REPLACE(valor,' ','')) LIKE ?", ["%{$busquedaSinEspacios}%"])
                            ->orWhereRaw("LOWER(REPLACE(nombre,' ','')) LIKE ?", ["%{$busquedaSinEspacios}%"]);
                    })->get();
                foreach ($productosPorEquivNueva as $p) {
                    if (!empty($p->codigo)) { $equivalenciasCodigos[] = $p->codigo; }
                }
                $productosPorAplic = Producto::whereIn('id', function ($sub) use ($busquedaSinEspacios) {
                        $sub->select('producto_id')->from('aplicaciones')
                            ->whereRaw("LOWER(REPLACE(valor,' ','')) LIKE ?", ["%{$busquedaSinEspacios}%"])
                            ->orWhereRaw("LOWER(REPLACE(nombre,' ','')) LIKE ?", ["%{$busquedaSinEspacios}%"]);
                    })->get();
                foreach ($productosPorAplic as $p) {
                    if (!empty($p->codigo)) { $equivalenciasCodigos[] = $p->codigo; }
                }
                $productosPorPartes = Producto::whereIn('id', function ($sub) use ($busquedaSinEspacios) {
                        $sub->select('pr.producto_id')->from('partes_relacionadas as pr')
                            ->join('productos as p2', 'p2.id', '=', 'pr.parte_id')
                            ->whereRaw("LOWER(REPLACE(p2.codigo,' ','')) LIKE ?", ["%{$busquedaSinEspacios}%"]);
                    })->get();
                foreach ($productosPorPartes as $p) {
                    if (!empty($p->codigo)) { $equivalenciasCodigos[] = $p->codigo; }
                }
            }

            // limpiar duplicados
            $equivalenciasCodigos = array_values(array_unique($equivalenciasCodigos));
        }

        // ====== 2) búsqueda súper fina por palabras ======
        // Rompemos la frase en palabras y exigimos que TODAS aparezcan
        // en nombre / marca / modelo / código (orden indiferente).
        $stopWords = ['de', 'del', 'la', 'las', 'los', 'y', 'en', 'el', 'para', 'por', 'con', 'un', 'una', 'unos', 'unas', 'a'];
        $tokensRaw = preg_split('/[\s,;.\-\/]+/', $busquedaLower);

        $tokens = array_values(array_unique(array_filter($tokensRaw, function ($t) use ($stopWords) {
            $t = trim($t);
            return $t !== '' && mb_strlen($t) >= 2 && !in_array($t, $stopWords, true);
        })));

        if (!empty($tokens)) {
            $query->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $root = $token;

                    // Singular simple (escobillas → escobilla)
                    if (mb_strlen($root) > 4 && mb_substr($root, -1) === 's') {
                        $root = mb_substr($root, 0, -1);
                    }

                    // Patrón para palabra completa (fusible / fusibles)
                    $pattern = '\\b' . preg_quote($root, '/') . 's?\\b';
                    $rootSinEspacios = str_replace(' ', '', $root);

                    // Cada palabra genera un grupo OR, pero entre palabras se hace AND
                    $q->where(function ($sub) use ($pattern, $root, $rootSinEspacios) {
                        // NOMBRE: palabra completa + LIKE por si es abreviado (arr → arranque)
                        $sub->whereRaw("LOWER(nombre) REGEXP ?", [$pattern])
                            ->orWhereRaw("LOWER(nombre) LIKE ?", ["%{$root}%"])

                            // MARCA / MODELO
                            ->orWhereRaw("LOWER(marca) LIKE ?", ["%{$root}%"])
                            ->orWhereRaw("LOWER(modelo) LIKE ?", ["%{$root}%"])

                            // CÓDIGO (normalizado sin espacios)
                            ->orWhereRaw("LOWER(REPLACE(codigo,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
                            // TABLAS NUEVAS: equivalencias, aplicaciones, partes_relacionadas (conviven con legacy)
                            ->orWhereExists(function ($qq) use ($root, $rootSinEspacios) {
                                $qq->select(DB::raw(1))->from('equivalencias')
                                    ->whereColumn('equivalencias.producto_id', 'productos.id')
                                    ->where(function ($w) use ($root, $rootSinEspacios) {
                                        $w->whereRaw("LOWER(equivalencias.valor) LIKE ?", ["%{$root}%"])
                                          ->orWhereRaw("LOWER(REPLACE(equivalencias.valor,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
                                          ->orWhereRaw("LOWER(equivalencias.nombre) LIKE ?", ["%{$root}%"]);
                                    });
                            })
                            ->orWhereExists(function ($qq) use ($root, $rootSinEspacios) {
                                $qq->select(DB::raw(1))->from('aplicaciones')
                                    ->whereColumn('aplicaciones.producto_id', 'productos.id')
                                    ->where(function ($w) use ($root, $rootSinEspacios) {
                                        $w->whereRaw("LOWER(aplicaciones.valor) LIKE ?", ["%{$root}%"])
                                          ->orWhereRaw("LOWER(REPLACE(aplicaciones.valor,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
                                          ->orWhereRaw("LOWER(aplicaciones.nombre) LIKE ?", ["%{$root}%"]);
                                    });
                            })
                            ->orWhereExists(function ($qq) use ($root, $rootSinEspacios) {
                                $qq->select(DB::raw(1))->from('partes_relacionadas')
                                    ->join('productos as p2', 'p2.id', '=', 'partes_relacionadas.parte_id')
                                    ->whereColumn('partes_relacionadas.producto_id', 'productos.id')
                                    ->where(function ($w) use ($root, $rootSinEspacios) {
                                        $w->whereRaw("LOWER(p2.codigo) LIKE ?", ["%{$rootSinEspacios}%"])
                                          ->orWhereRaw("LOWER(REPLACE(p2.codigo,' ','')) LIKE ?", ["%{$rootSinEspacios}%"])
                                          ->orWhereRaw("LOWER(p2.nombre) LIKE ?", ["%{$root}%"]);
                                    });
                            });
                    });
                }
            });
        }
    }

    // ========== FILTRO POR MODELO ==========
    if ($request->filled('modelo')) {
        $query->where('modelo', $request->modelo);
        $busqueda = $request->modelo;
    }

    // ========== FILTRO POR CATEGORÍA ==========
    $categoriaActual = null;
    if ($request->filled('categoriaFiltro')) {
        $categoriaInput = $request->categoriaFiltro;
        $categoria = Categoria::where('id', $categoriaInput)
            ->orWhere('nombre', 'LIKE', '%' . $categoriaInput . '%')
            ->first();

        if ($categoria) {
            $query->where('categoria_id', $categoria->id);
            $categoriaActual = $categoria->toArray();
        }
        $busqueda = $categoriaInput;
    }

    // ========== FILTRO POR EQUIVALENCIA MANUAL (VIEJO + NUEVO) ==========
    if ($request->filled('equivalenciaFiltro')) {
        $valorSinEspacios = str_replace(' ', '', trim($request->equivalenciaFiltro));

        // columnas columna_1..columna_78 (sistema viejo)
        $columns = Schema::getColumnListing('productos');
        $filteredColumns = array_filter($columns, fn($col) => preg_match('/^columna_\d+$/', $col));

        // ids de características que representan equivalencias (sistema nuevo)
        $caracteristicasEquivIds = Caracteristica::where(function ($q) {
                $q->where('nombre', 'LIKE', '%EQUIVALENCIA%')
                  ->orWhere('nombre', 'LIKE', '%BMH%')
                  ->orWhere('nombre', 'LIKE', '%Nº ORIGINAL%')
                  ->orWhere('nombre', 'LIKE', '%NUMERO ORIGINAL%');
            })
            ->pluck('id');

        $query->where(function ($q) use ($filteredColumns, $valorSinEspacios, $caracteristicasEquivIds) {
            // --- sistema viejo: columnas_X ---
            foreach ($filteredColumns as $column) {
                $q->orWhereRaw("REPLACE($column, ' ', '') LIKE ?", ["%{$valorSinEspacios}%"]);
            }

            // --- sistema nuevo: producto_caracteristica ---
            if ($caracteristicasEquivIds->isNotEmpty()) {
                $q->orWhereIn('id', function ($sub) use ($caracteristicasEquivIds, $valorSinEspacios) {
                    $sub->select('producto_id')
                        ->from('producto_caracteristica')
                        ->whereIn('caracteristica_id', $caracteristicasEquivIds)
                        ->whereRaw("REPLACE(valor, ' ', '') LIKE ?", ["%{$valorSinEspacios}%"]);
                });
            }
            // --- tablas nuevas: equivalencias, aplicaciones, partes_relacionadas (conviven con legacy) ---
            $q->orWhereExists(function ($sub) use ($valorSinEspacios) {
                $sub->select(DB::raw(1))->from('equivalencias')
                    ->whereColumn('equivalencias.producto_id', 'productos.id')
                    ->where(function ($w) use ($valorSinEspacios) {
                        $w->whereRaw("REPLACE(equivalencias.valor,' ', '') LIKE ?", ["%{$valorSinEspacios}%"])
                          ->orWhereRaw("REPLACE(equivalencias.nombre,' ', '') LIKE ?", ["%{$valorSinEspacios}%"]);
                    });
            });
            $q->orWhereExists(function ($sub) use ($valorSinEspacios) {
                $sub->select(DB::raw(1))->from('aplicaciones')
                    ->whereColumn('aplicaciones.producto_id', 'productos.id')
                    ->where(function ($w) use ($valorSinEspacios) {
                        $w->whereRaw("REPLACE(aplicaciones.valor,' ', '') LIKE ?", ["%{$valorSinEspacios}%"])
                          ->orWhereRaw("REPLACE(aplicaciones.nombre,' ', '') LIKE ?", ["%{$valorSinEspacios}%"]);
                    });
            });
            $q->orWhereExists(function ($sub) use ($valorSinEspacios) {
                $sub->select(DB::raw(1))->from('partes_relacionadas')
                    ->join('productos as p2', 'p2.id', '=', 'partes_relacionadas.parte_id')
                    ->whereColumn('partes_relacionadas.producto_id', 'productos.id')
                    ->whereRaw("REPLACE(p2.codigo,' ', '') LIKE ?", ["%{$valorSinEspacios}%"]);
            });
        });

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

    $obtenerAtributoPorColumna = function ($columna, $categoria) {
        if (is_array($categoria) && isset($categoria[$columna]) && !is_null($categoria[$columna])) {
            return strtoupper(trim($categoria[$columna]));
        }
        return null;
    };

    $obtenerTolerancia = function ($nombreAtributo, $tolerancias) {
        return $tolerancias[$nombreAtributo] ?? 0;
    };

    // Primer atributo
    if ($request->filled('atributo') && $request->filled('valorAttr')) {
        $columna = $request->atributo;
        $rawVal  = trim($request->valorAttr);
        $val     = str_replace(',', '.', $rawVal);

        if (!$categoriaActual && $columna) {
            $producto = $query->first();
            if ($producto && $producto->categoria) {
                $categoriaActual = $producto->categoria->toArray();
            }
        }

        $nombreAtributo = $obtenerAtributoPorColumna($columna, $categoriaActual);
        $tolerancia     = $obtenerTolerancia($nombreAtributo, $tolerancias);

        if (is_numeric($val)) {
            $n    = (float)$val;
            $expr = DB::raw("REPLACE(`{$columna}`, ',', '.') + 0");

            if ($tolerancia > 0) {
                $query->whereBetween($expr, [$n - $tolerancia, $n + $tolerancia]);
            } else {
                $query->where($expr, '=', $n);
            }
        } else {
            $query->where($columna, 'LIKE', '%' . $rawVal . '%');
        }
    }

    // Segundo atributo
    if ($request->filled('atributoTwo') && $request->filled('valorAttrTwo')) {
        $columna2 = $request->atributoTwo;
        $rawVal2  = trim($request->valorAttrTwo);
        $val2     = str_replace(',', '.', $rawVal2);

        if (!$categoriaActual && $columna2) {
            $producto = $query->first();
            if ($producto && $producto->categoria) {
                $categoriaActual = $producto->categoria->toArray();
            }
        }

        $nombreAtributo2 = $obtenerAtributoPorColumna($columna2, $categoriaActual);
        $tolerancia2     = $obtenerTolerancia($nombreAtributo2, $tolerancias);

        if (is_numeric($val2)) {
            $n2    = (float)$val2;
            $expr2 = DB::raw("REPLACE(`{$columna2}`, ',', '.') + 0");

            if ($tolerancia2 > 0) {
                $query->whereBetween($expr2, [$n2 - $tolerancia2, $n2 + $tolerancia2]);
            } else {
                $query->where($expr2, '=', $n2);
            }
        } else {
            $query->where($columna2, 'LIKE', '%' . $rawVal2 . '%');
        }
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
    $productos = $query->orderBy('orden', 'desc')->get();

    // --- Agregar productos por equivalencia cuando se buscó un código ---
    if (!empty($equivalenciasCodigos)) {

        // Normalizar los códigos equivalentes (ej: 1420)
        $codigosNorm = array_map(function ($c) {
            return mb_strtolower(preg_replace('/\s+/', '', $c));
        }, $equivalenciasCodigos);

        $productosEquiv = Producto::with([
            'categoria',
            'portadaImagen',
            'imagenesGaleria',
            'productCaracteristicas.caracteristica',
            'partesRelacionadas.portadaImagen',
            'equivalencias',
            'aplicaciones',
        ])->where(function ($q) use ($codigosNorm) {
            foreach ($codigosNorm as $code) {
                $q->orWhereRaw("LOWER(REPLACE(codigo,' ','')) = ?", [$code]);
            }
        })->get();

        // Unir sin duplicar ids
        $productos = $productos->concat(
            $productosEquiv->reject(fn($p) => $productos->contains('id', $p->id))
        );
    }

    // ==============================
    // Relevancia en PHP
    // ==============================
    if (!empty($busqueda)) {
        $busquedaLower = mb_strtolower($busqueda);

        $productos = $productos->map(function ($producto) use ($busquedaLower) {
            $score = 0;

            $codigo = mb_strtolower($producto->codigo ?? '');
            $nombre = mb_strtolower($producto->nombre ?? '');
            $marca  = mb_strtolower($producto->marca ?? '');
            $modelo = mb_strtolower($producto->modelo ?? '');

            // CÓDIGO
            if ($codigo === $busquedaLower) {
                $score += 1000;
            } elseif (strpos($codigo, $busquedaLower) === 0) {
                $score += 900;
            } elseif (strpos($codigo, $busquedaLower) !== false) {
                $score += 800;
            }

            // NOMBRE
            if ($nombre === $busquedaLower) {
                $score += 700;
            } elseif (strpos($nombre, $busquedaLower) === 0) {
                $score += 650;
            } elseif (strpos($nombre, $busquedaLower) !== false) {
                $score += 600;
            }

            // MARCA
            if ($marca === $busquedaLower) {
                $score += 500;
            } elseif (strpos($marca, $busquedaLower) === 0) {
                $score += 450;
            } elseif (strpos($marca, $busquedaLower) !== false) {
                $score += 400;
            }

            // MODELO
            if (strpos($modelo, $busquedaLower) !== false) {
                $score += 300;
            }

            // CATEGORÍA
            if ($producto->categoria && strpos(mb_strtolower($producto->categoria->nombre ?? ''), $busquedaLower) !== false) {
                $score += 200;
            }

            $producto->relevancia_score = $score;
            return $producto;
        });

        $productos = $productos->sortByDesc('relevancia_score')->values();
    }

    $productos->each(function (Producto $prod): void {
        $prod->setRelation(
            'productCaracteristicas',
            $prod->productCaracteristicas
                ->sortBy(fn ($pc) => $pc->caracteristica->orden ?? PHP_INT_MAX)
                ->values(),
        );
    });

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




    public function productos_clientes(Request $request)
    {

        $categoria = $request->categoria;
        $producto = $request->producto;
        $codigo = $request->codigo;

        $query = Producto::with([
            'categoria',
            'portadaImagen',
            'productCaracteristicas.caracteristica',
        ]);
        if (!is_null($categoria) && $categoria != '') {
            $query->whereHas('categoria', function ($query) use ($categoria) {
                $query->where('id', $categoria);
            });
        }
        if (!is_null($producto) && $producto != '') {
            $query->where('nombre', 'like', '%' . $producto . '%');
        }
        if (!is_null($codigo) && $codigo != '') {
            $query->where('codigo', 'like', '%' . $codigo . '%');
        }
        $productos = $query->orderBy('orden')
            ->orderBy('nombre')
            ->paginate(15)
            ->appends([
                'categoria' => $categoria,
                'producto' => $producto,
                'codigo' => $codigo
            ]);

        $categorias = Categoria::orderBy('orden')->orderBy('nombre')->get();
        $bonificaciones = Bonificacion::orderBy('orden')->get();




        $marcas = $this->catalogFilterOptions->brandsWithModels();
        $categoriasAll = Categoria::orderBy('nombre', 'asc')->get();



        $zonaclientes = true;
        $ventana = 'productos-nav';



        if (!session()->has('anuncio_mostrado') || now()->diffInHours(session('anuncio_mostrado')) >= 24) {
            $anuncio = Anuncio::find(1);
            session(['anuncio_mostrado' => now()]); // Guardar la hora actual
        } else {
            $anuncio = null; // No se muestra el anuncio
        }
        // $request->session()->forget('anuncio_mostrado');

        return view('frontend/productos-zona-privada', compact('anuncio', 'productos', 'categorias', 'zonaclientes', 'ventana', 'bonificaciones', 'marcas', 'categoriasAll'));
    }


    public function productos_clientes_filter(Request $request)
    {

        $anuncio = Anuncio::find(1);



        $categoria = $request->categoria;
        $producto = $request->producto;
        $codigo = $request->codigo;

        $query = Producto::with([
            'categoria',
            'portadaImagen',
            'productCaracteristicas.caracteristica',
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


        if ($request->has('equivalenciaFiltro') && $request->equivalenciaFiltro) {
            $equivalenciaFiltro = $request->equivalenciaFiltro;

            $query->where(function ($q) use ($equivalenciaFiltro) {
                // Recorrer todas las columnas de columna_1 a columna_74
                for ($i = 1; $i <= 78; $i++) {
                    $q->orWhere("columna_$i", $equivalenciaFiltro);
                }
                // Tablas nuevas (conviven con legacy)
                $q->orWhereHas('equivalencias', function ($qq) use ($equivalenciaFiltro) {
                    $qq->where('valor', 'LIKE', '%' . $equivalenciaFiltro . '%')
                       ->orWhere('nombre', 'LIKE', '%' . $equivalenciaFiltro . '%');
                });
                $q->orWhereHas('aplicaciones', function ($qq) use ($equivalenciaFiltro) {
                    $qq->where('valor', 'LIKE', '%' . $equivalenciaFiltro . '%')
                       ->orWhere('nombre', 'LIKE', '%' . $equivalenciaFiltro . '%');
                });
                $q->orWhereHas('partesRelacionadas', function ($qq) use ($equivalenciaFiltro) {
                    $qq->where('codigo', 'LIKE', '%' . $equivalenciaFiltro . '%')
                       ->orWhere('nombre', 'LIKE', '%' . $equivalenciaFiltro . '%');
                });
            });
        }


        if ($request->has('categoriaFiltro') && $request->categoriaFiltro) {
            $query->where('categoria_id', $request->categoriaFiltro);
            $busqueda = $request->categoriaFiltro;
        }

        if ($request->has('alto') && $request->alto) {
            $query->whereBetween('columna_28', [$request->alto - 2, $request->alto + 2]);
            $busqueda = $request->alto;
        }

        if ($request->has('largo') && $request->largo) {
            $query->whereBetween('columna_8', [$request->largo - 2, $request->largo + 2]);
            $busqueda = $request->largo;
        }

        if ($request->has('ancho') && $request->ancho) {
            $query->whereBetween('columna_9', [$request->ancho - 2, $request->ancho + 2]);
            $busqueda = $request->ancho;
        }

        if ($request->has('nuevo') && $request->nuevo) {
            $query->where('estado', 1);
        }
        if ($request->has('reconstruido') && $request->reconstruido) {
            $query->where('estado', 2);
        }

        $productos = $query->orderBy('orden')
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

        return view('frontend/productos-zona-privada', compact('anuncio', 'productos', 'categorias', 'zonaclientes', 'ventana', 'bonificaciones', 'marcas', 'categoriasAll'));
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




    public function productos_clientes_buscar(Request $request)
    {
        $zonaclientes = true;
        $busqueda = $request->valor;
        $productos = Producto::whereHas('categoria', function ($query) use ($busqueda) {
            $query->where('nombre', 'like', '%' . $busqueda . '%');
        })
            ->orWhere('nombre', 'like', '%' . $request->valor . '%')
            ->orderBy('orden')->paginate(20);
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

    $query = Producto::with('categoria')->orderBy('orden');

    if (!empty($categoria_id) && $categoria_id != 'todos') {
        $query->where('categoria_id', $categoria_id);
    }

    $productos = $query->paginate(20)->appends($request->all());

    $categorias = Categoria::orderBy('nombre')->get();

    return view('backend/dash-productos', compact('productos', 'categorias', 'categoria_id'));
}

    public function exportarExcel(Request $request)
    {
        $categoriaId = $request->input('categoria_id');

        $query = Producto::with('categoria')->orderBy('orden');
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
    
  


    public function store(Request $request)
    {


        $nuevo_producto = new Producto();
        $nuevo_producto->nombre = $request->nombre;
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

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'columna_') && !empty($value)) {
                // Asigna el valor dinÃ¡mico directamente al modelo, si tienes columnas para ello
                $nuevo_producto->$key = $value; // AsegÃºrate de que el nombre de la columna exista en tu tabla
            }
        }




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

                if ($request->caracteristicas) {
            foreach ($request->caracteristicas as $caracteristica_id => $valor) {
                if ($valor !== null && $valor !== '') {
                    DB::table('producto_caracteristica')->insert([
                        'producto_id' => $nuevo_producto->id,
                        'caracteristica_id' => $caracteristica_id,
                        'valor' => $valor,
                        'created_at' => now(),
                    ]);
                }
            }
        }

        $this->sincronizarEquivalencias($request, $nuevo_producto);

        $this->sincronizarAplicaciones($request, $nuevo_producto);

        if ($request->hasFile('imagenes')) {
            $files = $request->file('imagenes');

            // Recorrer cada archivo
            for ($i = 0; $i < count($files); $i++) {
                if ($files[$i]->isValid()) {
                    $nombreImagen = 'media_' . uniqid() . '.' . $files[$i]->getClientOriginalExtension();
                    $files[$i]->move('imagenes', $nombreImagen);
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
        $producto = Producto::with(['productCaracteristicas.caracteristica'])->find($request->id);
        $equivalencias = $producto->equivalencias()->orderBy('orden')->get();
        $aplicaciones = $producto->aplicaciones()->orderBy('orden')->get();
        $imagenes = Imagen::where('producto_id', $request->id)->where('sector', 'producto')->orderBy('orden')->get();
        $categorias = Categoria::with('caracteristicas')->orderBy('orden')->get();
        $categoriaSelected = Categoria::with('caracteristicas')->find($producto->categoria_id);
        $partesRelacionadas = $producto->partesRelacionadas()->with('portadaImagen')->get();
        $caracteristicas = $producto->productCaracteristicas
            ->map(function ($pc) {
                $caracteristica = $pc->caracteristica;
                $caracteristica->valor = $pc->valor;

                return $caracteristica;
            })
            ->values();



        return view('backend/dash-producto-edit', compact('producto',  'imagenes',  'categorias', 'equivalencias', 'aplicaciones', 'categoriaSelected', 'caracteristicas', 'partesRelacionadas'));
    }

    public function update(Request $request)
    {

        $producto = Producto::find($request->id);

        if ($request->hasFile('imagenes')) {
            $files = $request->file('imagenes');

            foreach ($files as $file) {
                // Verificar si el archivo se cargï¿½ï¿½ correctamente
                if ($file->isValid()) {
                    $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move('imagenes', $nombreImagen);

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

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'columna_')) {
                $producto->$key = $value; // AsegÃºrate de que el nombre de la columna exista en tu tabla
            }
        }


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

        $producto->save();

        $this->sincronizarPartesRelacionadas($request, $producto);

        $this->sincronizarEquivalencias($request, $producto);

        $this->sincronizarAplicaciones($request, $producto);

        if ($request->caracteristicas) {
            foreach ($request->caracteristicas as $caracteristica_id => $valor) {
                if ($valor !== null && $valor !== '') {
                    // Primero eliminar el registro existente si existe
                    DB::table('producto_caracteristica')
                        ->where('producto_id', $producto->id)
                        ->where('caracteristica_id', $caracteristica_id)
                        ->delete();
                    
                    // Luego insertar el nuevo valor
                    DB::table('producto_caracteristica')->insert([
                        'producto_id' => $producto->id,
                        'caracteristica_id' => $caracteristica_id,
                        'valor' => $valor,
                        'created_at' => now(),
                    ]);
                } else {
                    // Si el valor estÃ¡ vacÃ­o, eliminar el registro
                    DB::table('producto_caracteristica')
                        ->where('producto_id', $producto->id)
                        ->where('caracteristica_id', $caracteristica_id)
                        ->delete();
                }
            }
        }


        return redirect()->back()->with('success', 'Producto actualizado');
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
                $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move('imagenes', $nombreImagen);
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
        } else {

            $imagen = Imagen::find($request->id_imagen);
            $imagen->tipo = 'portada';
            $imagen->orden = 'aa';
            $imagen->save();
        }

        return redirect()->back();
    }

    public function actualizarPreciosExcel(Request $request)
    {
        // 1) Validar que venga el Excel y sea correcto
        $request->validate([
            'lista' => 'required|file|mimes:xlsx,xls'
        ]);

        // 2) Leer el Excel
        $filePath    = $request->file('lista')->getRealPath();
        $reader      = IOFactory::createReaderForFile($filePath);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($filePath);
        $rows        = $spreadsheet->getActiveSheet()->toArray();

        // 3) Iterar y actualizar
        $actualizados = 0;
        foreach ($rows as $i => $row) {
            if ($i === 0) {
                // salto encabezados
                continue;
            }

            $codigo    = trim($row[0] ?? '');
            $precioRaw = trim($row[1] ?? '');
            $precio    = str_replace(',', '.', $precioRaw);

            if ($codigo === '' || ! is_numeric($precio)) {
                continue;
            }

            $updated = Producto::where('codigo', $codigo)
                ->update(['precio' => $precio]);

            if ($updated) {
                $actualizados++;
            }
        }

        // 4) Devolver con mensaje de Ã©xito
        return redirect()->back()
            ->with('success', "ActualizaciÃ³n exitosa: {$actualizados} productos actualizados.");
    }
    public function dash_buscar_producto(Request $request)
    {
        $busqueda = (string) $request->input('valor', '');
        $categoriaId = $request->input('categoria_id');
        $textoNormalizado = strtolower($busqueda);

        $crearConsulta = function () use ($textoNormalizado) {
            return Producto::with('categoria')
                ->where(function ($query) use ($textoNormalizado) {
                    $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . $textoNormalizado . '%'])
                        ->orWhereRaw('LOWER(codigo) LIKE ?', ['%' . $textoNormalizado . '%'])
                        ->orWhereRaw('LOWER(descripcion) LIKE ?', ['%' . $textoNormalizado . '%'])
                        ->orWhereHas('categoria', function ($query) use ($textoNormalizado) {
                            $query->whereRaw('LOWER(nombre) LIKE ?', ['%' . $textoNormalizado . '%']);
                        });
                });
        };

        $query = $crearConsulta();
        $sinResultadosCategoria = false;

        // La categoría seleccionada debe restringir también las búsquedas AJAX.
        if ($categoriaId !== null && $categoriaId !== '' && $categoriaId !== 'todos') {
            $query->where('categoria_id', $categoriaId);
        }

        $productos = $query
            ->orderBy('orden')
            ->paginate(20);

        // Si no hay coincidencias dentro de la categoría, mostrar alternativas
        // fuera de ella sin mezclar esos resultados con el filtro seleccionado.
        if (
            $productos->isEmpty()
            && trim($busqueda) !== ''
            && $categoriaId !== null
            && $categoriaId !== ''
            && $categoriaId !== 'todos'
        ) {
            $sinResultadosCategoria = true;
            $productos = $crearConsulta()
                ->where('categoria_id', '!=', $categoriaId)
                ->orderBy('orden')
                ->paginate(20);
        }

        return response()->json([
            'html' => view('backend/dash-productos-listado', compact('productos'))->render(),
            'pagination' => $productos->links()->toHtml(),
            'sinResultadosCategoria' => $sinResultadosCategoria,
            'hayAlternativas' => $sinResultadosCategoria && $productos->isNotEmpty(),
        ]);
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

    public function actualizar_categoriasExcel(Request $request)
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
                if ($key < 1) {
                    continue; // Saltar la primera fila, ya que son los encabezados
                }

                $nombreCategoria = $row[0] ?? null;
                $atrr1 = $row[1] ?? null;
                $atrr2 = $row[2] ?? null;
                $atrr3 = $row[3] ?? null;
                $atrr4 = $row[4] ?? null;
                $atrr5 = $row[5] ?? null;
                $atrr6 = $row[6] ?? null;
                $atrr7 = $row[7] ?? null;
                $atrr8 = $row[8] ?? null;
                $atrr9 = $row[9] ?? null;
                $atrr10 = $row[10] ?? null;
                $atrr11 = $row[11] ?? null;
                $atrr12 = $row[12] ?? null;
                $atrr13 = $row[13] ?? null;
                $atrr14 = $row[14] ?? null;
                $atrr15 = $row[15] ?? null;
                $atrr16 = $row[16] ?? null;
                $atrr17 = $row[17] ?? null;
                $atrr18 = $row[18] ?? null;
                $atrr19 = $row[19] ?? null;
                $atrr20 = $row[20] ?? null;
                $atrr21 = $row[21] ?? null;
                $atrr22 = $row[22] ?? null;
                $atrr23 = $row[23] ?? null;
                $atrr24 = $row[24] ?? null;
                $atrr25 = $row[25] ?? null;
                $atrr26 = $row[26] ?? null;
                $atrr27 = $row[27] ?? null;
                $atrr28 = $row[28] ?? null;
                $atrr29 = $row[29] ?? null;
                $atrr30 = $row[30] ?? null;
                $atrr31 = $row[31] ?? null;
                $atrr32 = $row[32] ?? null;
                $atrr33 = $row[33] ?? null;
                $atrr34 = $row[34] ?? null;
                $atrr35 = $row[35] ?? null;
                $atrr36 = $row[36] ?? null;
                $atrr37 = $row[37] ?? null;
                $atrr38 = $row[38] ?? null;
                $atrr39 = $row[39] ?? null;
                $atrr40 = $row[40] ?? null;
                $atrr41 = $row[41] ?? null;
                $atrr42 = $row[42] ?? null;
                $atrr43 = $row[43] ?? null;
                $atrr44 = $row[44] ?? null;
                $atrr45 = $row[45] ?? null;
                $atrr46 = $row[46] ?? null;
                $atrr47 = $row[47] ?? null;
                $atrr48 = $row[48] ?? null;
                $atrr49 = $row[49] ?? null;
                $atrr50 = $row[50] ?? null;
                $atrr51 = $row[51] ?? null;
                $atrr52 = $row[52] ?? null;
                $atrr53 = $row[53] ?? null;
                $atrr54 = $row[54] ?? null;
                $atrr55 = $row[55] ?? null;
                $atrr56 = $row[56] ?? null;
                $atrr57 = $row[57] ?? null;
                $atrr58 = $row[58] ?? null;
                $atrr59 = $row[59] ?? null;
                $atrr60 = $row[60] ?? null;
                $atrr61 = $row[61] ?? null;
                $atrr62 = $row[62] ?? null;
                $atrr63 = $row[63] ?? null;
                $atrr64 = $row[64] ?? null;
                $atrr65 = $row[65] ?? null;
                $atrr66 = $row[66] ?? null;
                $atrr67 = $row[67] ?? null;
                $atrr68 = $row[68] ?? null;
                $atrr69 = $row[69] ?? null;
                $atrr70 = $row[70] ?? null;
                $atrr71 = $row[71] ?? null;
                $atrr72 = $row[72] ?? null;
                $atrr73 = $row[73] ?? null;
                $atrr74 = $row[74] ?? null;
                $atrr75 = $row[75] ?? null;
                $atrr76 = $row[76] ?? null;
                $atrr77 = $row[77] ?? null;
                $atrr78 = $row[78] ?? null;



                $categoria = Categoria::where('nombre', $nombreCategoria)->first();

                if ($categoria) {
                    $categoria->columna_1 = $atrr1;
                    $categoria->columna_2 = $atrr2;
                    $categoria->columna_3 = $atrr3;
                    $categoria->columna_4 = $atrr4;
                    $categoria->columna_5 = $atrr5;
                    $categoria->columna_6 = $atrr6;
                    $categoria->columna_7 = $atrr7;
                    $categoria->columna_8 = $atrr8;
                    $categoria->columna_9 = $atrr9;
                    $categoria->columna_10 = $atrr10;
                    $categoria->columna_11 = $atrr11;
                    $categoria->columna_12 = $atrr12;
                    $categoria->columna_13 = $atrr13;
                    $categoria->columna_14 = $atrr14;
                    $categoria->columna_15 = $atrr15;
                    $categoria->columna_16 = $atrr16;
                    $categoria->columna_17 = $atrr17;
                    $categoria->columna_18 = $atrr18;
                    $categoria->columna_19 = $atrr19;
                    $categoria->columna_20 = $atrr20;
                    $categoria->columna_21 = $atrr21;
                    $categoria->columna_22 = $atrr22;
                    $categoria->columna_23 = $atrr23;
                    $categoria->columna_24 = $atrr24;
                    $categoria->columna_25 = $atrr25;
                    $categoria->columna_26 = $atrr26;
                    $categoria->columna_27 = $atrr27;
                    $categoria->columna_28 = $atrr28;
                    $categoria->columna_29 = $atrr29;
                    $categoria->columna_30 = $atrr30;
                    $categoria->columna_31 = $atrr31;
                    $categoria->columna_32 = $atrr32;
                    $categoria->columna_33 = $atrr33;
                    $categoria->columna_34 = $atrr34;
                    $categoria->columna_35 = $atrr35;
                    $categoria->columna_36 = $atrr36;
                    $categoria->columna_37 = $atrr37;
                    $categoria->columna_38 = $atrr38;
                    $categoria->columna_39 = $atrr39;
                    $categoria->columna_40 = $atrr40;
                    $categoria->columna_41 = $atrr41;
                    $categoria->columna_42 = $atrr42;
                    $categoria->columna_43 = $atrr43;
                    $categoria->columna_44 = $atrr44;
                    $categoria->columna_45 = $atrr45;
                    $categoria->columna_46 = $atrr46;
                    $categoria->columna_47 = $atrr47;
                    $categoria->columna_48 = $atrr48;
                    $categoria->columna_49 = $atrr49;
                    $categoria->columna_50 = $atrr50;
                    $categoria->columna_51 = $atrr51;
                    $categoria->columna_52 = $atrr52;
                    $categoria->columna_53 = $atrr53;
                    $categoria->columna_54 = $atrr54;
                    $categoria->columna_55 = $atrr55;
                    $categoria->columna_56 = $atrr56;
                    $categoria->columna_57 = $atrr57;
                    $categoria->columna_58 = $atrr58;
                    $categoria->columna_59 = $atrr59;
                    $categoria->columna_60 = $atrr60;
                    $categoria->columna_61 = $atrr61;
                    $categoria->columna_62 = $atrr62;
                    $categoria->columna_63 = $atrr63;
                    $categoria->columna_64 = $atrr64;
                    $categoria->columna_65 = $atrr65;
                    $categoria->columna_66 = $atrr66;
                    $categoria->columna_67 = $atrr67;
                    $categoria->columna_68 = $atrr68;
                    $categoria->columna_69 = $atrr69;
                    $categoria->columna_70 = $atrr70;
                    $categoria->columna_71 = $atrr71;
                    $categoria->columna_72 = $atrr72;
                    $categoria->columna_73 = $atrr73;
                    $categoria->columna_74 = $atrr74;
                    $categoria->columna_75 = $atrr75;
                    $categoria->columna_76 = $atrr76;
                    $categoria->columna_77 = $atrr77;
                    $categoria->columna_78 = $atrr78;



                    $categoria->save();
                }
            }

            return redirect()->back()->with('success', 'ActualizaciÃ³n exitosa: ' . $actualizados . ' clientes han sido actualizados y ' . $creados . ' clientes han sido creados.');
        } else {
            return redirect()->back()->with('warning', 'No se ha proporcionado un archivo Excel.');
        }
    }

    public function actualizar_precios(Request $request)
    {
        if ($request->file('lista')) {
            $file = $request->file('lista');
            $actualizados = 0;
            $creados = 0;
            $batchSize = 100; // NÃºmero de filas a procesar en cada lote

            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadEmptyCells(false);
            $spreadsheet = $reader->load($file);

            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $batchData = [];

            foreach ($rows as $key => $row) {
                if ($key < 1) {
                    continue; // Saltar la primera fila, ya que son los encabezados
                }

                // Obtener datos de la fila
                $data = $this->parseRow($row);

                // AÃ±adir datos al lote
                $batchData[] = $data;

                // Procesar el lote si alcanza el tamaÃ±o definido
                if (count($batchData) >= $batchSize) {
                    $this->processBatch($batchData, $actualizados, $creados);
                    $batchData = []; // Limpiar el lote
                }
            }

            // Procesar cualquier dato restante
            if (!empty($batchData)) {
                $this->processBatch($batchData, $actualizados, $creados);
            }

            return redirect()->back()->with('success', 'ActualizaciÃ³n exitosa: ' . $actualizados . ' productos han sido actualizados y ' . $creados . ' productos han sido creados.');
        } else {
            return redirect()->back()->with('warning', 'No se ha proporcionado un archivo Excel.');
        }
    }

    private function parseRow($row)
    {
        return [
            'codigo' => $row[1] ?? null,
            'nombre' => $row[2] ?? null,
            'descripcion' => $row[3] ?? null,
            'precio' => $row[4] ?? null,
            'precioNeto' => $row[5] ?? null,
            'estado' => $row[6] ?? null,
            'imagenesTexto' => $row[7] ?? null,
            'equivalencias' => $row[8] ?? null,
            'marca' => $row[9] ?? null,
            'modelo' => $row[10] ?? null,
            'attr1' => $row[11] ?? null,
            'attr2' => $row[12] ?? null,
            'attr3' => $row[13] ?? null,
            'attr4' => $row[14] ?? null,
            'attr5' => $row[15] ?? null,
            'attr6' => $row[16] ?? null,
            'attr7' => $row[17] ?? null,
            'attr8' => $row[18] ?? null,
            'attr9' => $row[19] ?? null,
            'attr10' => $row[20] ?? null,
            'attr11' => $row[21] ?? null,
            'attr12' => $row[22] ?? null,
            'attr13' => $row[23] ?? null,
            'attr14' => $row[24] ?? null,
            'attr15' => $row[25] ?? null,
            'attr16' => $row[26] ?? null,
            'attr17' => $row[27] ?? null,
            'attr18' => $row[28] ?? null,
            'attr19' => $row[29] ?? null,
            'attr20' => $row[30] ?? null,
            'attr21' => $row[31] ?? null,
            'attr22' => $row[32] ?? null,
            'attr23' => $row[33] ?? null,
            'attr24' => $row[34] ?? null,
            'attr25' => $row[35] ?? null,
            'attr26' => $row[36] ?? null,
            'attr27' => $row[37] ?? null,
            'attr28' => $row[38] ?? null,
            'attr29' => $row[39] ?? null,
            'attr30' => $row[40] ?? null,
            'attr31' => $row[41] ?? null,
            'attr32' => $row[42] ?? null,
            'attr33' => $row[43] ?? null,
            'attr34' => $row[44] ?? null,
            'attr35' => $row[45] ?? null,
            'attr36' => $row[46] ?? null,
            'attr37' => $row[47] ?? null,
            'attr38' => $row[48] ?? null,
            'attr39' => $row[49] ?? null,
            'attr40' => $row[50] ?? null,
            'attr41' => $row[51] ?? null,
            'attr42' => $row[52] ?? null,
            'attr43' => $row[53] ?? null,
            'attr44' => $row[54] ?? null,
            'attr45' => $row[55] ?? null,
            'attr46' => $row[56] ?? null,
            'attr47' => $row[57] ?? null,
            'attr48' => $row[58] ?? null,
            'attr49' => $row[59] ?? null,
            'attr50' => $row[60] ?? null,
            'attr51' => $row[61] ?? null,
            'attr52' => $row[62] ?? null,
            'attr53' => $row[63] ?? null,
            'attr54' => $row[64] ?? null,
            'attr55' => $row[65] ?? null,
            'attr56' => $row[66] ?? null,
            'attr57' => $row[67] ?? null,
            'attr58' => $row[68] ?? null,
            'attr59' => $row[69] ?? null,
            'attr60' => $row[70] ?? null,
            'attr61' => $row[71] ?? null,
            'attr62' => $row[72] ?? null,
            'attr63' => $row[73] ?? null,
            'attr64' => $row[74] ?? null,
            'attr65' => $row[75] ?? null,
            'attr66' => $row[76] ?? null,
            'attr67' => $row[77] ?? null,
            'attr68' => $row[78] ?? null,
            'attr69' => $row[79] ?? null,
            'attr70' => $row[80] ?? null,
            'attr71' => $row[81] ?? null,
            'attr72' => $row[82] ?? null,
            'attr73' => $row[83] ?? null,
            'attr74' => $row[84] ?? null,
            'attr75' => $row[85] ?? null,
            'attr76' => $row[86] ?? null,
            'attr77' => $row[87] ?? null,
            'attr78' => $row[88] ?? null,

            'aplicaciones' => $row[99] ?? null,

            'categoriaNombre' => $row[0] ?? null,
        ];
    }

    private function processBatch($batchData, &$actualizados, &$creados)
    {
        DB::transaction(function () use ($batchData, &$actualizados, &$creados) {
            foreach ($batchData as $data) {
                $producto = Producto::where('codigo', $data['codigo'])->first();
                if ($producto) {
                    $this->updateProduct($producto, $data);
                    $actualizados++;
                } else {
                    $this->createProduct($data);
                    $creados++;
                }
            }
        });
    }

    private function updateProduct($producto, $data)
    {
        $producto->nombre = $data['nombre'];
        $producto->descripcion = $data['descripcion'];
        $producto->precio = $data['precio'];
        $producto->precioN = $data['precioNeto'];
        $producto->estado = $this->mapEstado($data['estado']);
        $producto->marca = $data['marca'];
        $producto->modelo = $data['modelo'];

        // Actualizar columnas adicionales
        for ($i = 1; $i <= 78; $i++) {
            $column = 'columna_' . $i;
            $producto->$column = $data['attr' . $i] ?? null;
        }

        $producto->save();

        // Manejo de imÃ¡genes
        $this->handleImages($producto, $data['imagenesTexto']);

        // Manejo de equivalencias
        $this->handleEquivalencias($producto, $data['equivalencias']);

        // Manejo de aplicaciones
        $this->handleAplicaciones($producto, $data['aplicaciones']);

        // Manejo de categorÃ­as
        $this->handleCategoria($producto, $data['categoriaNombre']);
    }

    private function createProduct($data)
    {
        $producto = new Producto();
        $producto->orden = 'aa';
        $producto->codigo = $data['codigo'];
        $producto->nombre = $data['nombre'];
        $producto->descripcion = $data['descripcion'];
        $producto->precio = $data['precio'];
        $producto->precioN = $data['precioNeto'];
        $producto->estado = $this->mapEstado($data['estado']);
        $producto->marca = $data['marca'];
        $producto->modelo = $data['modelo'];

        // Asignar columnas adicionales
        for ($i = 1; $i <= 78; $i++) {
            $column = 'columna_' . $i;
            $producto->$column = $data['attr' . $i] ?? null;
        }

        $producto->save();

        // Manejo de imÃ¡genes
        $this->handleImages($producto, $data['imagenesTexto']);

        // Manejo de equivalencias
        $this->handleEquivalencias($producto, $data['equivalencias']);

        // Manejo de aplicaciones
        $this->handleAplicaciones($producto, $data['aplicaciones']);

        // Manejo de categorÃ­as
        $this->handleCategoria($producto, $data['categoriaNombre']);
    }

    private function handleImages($producto, $imagenesTexto)
    {
        if ($imagenesTexto) {
            $imagenesURLs = array_map('trim', explode(',', $imagenesTexto));
            $imagenesNombres = array_map('basename', $imagenesURLs);

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
        $valores = collect(explode(',', $equivalencias))
            ->map(fn ($valor): string => trim((string) $valor))
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

    public function getProductoValor($columnaId, $productoId, $categoriaId)
    {
        // Recuperar el producto por ID
        $producto = Producto::find($productoId);

        // Verificar que el producto existe
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Verificar si la categorÃ­a del producto coincide con la categorÃ­a seleccionada
        if ($producto->categoria_id != $categoriaId) {
            return response()->json(['error' => 'El producto no pertenece a esta categorÃ­a'], 400);
        }

        // Construir el nombre de la columna dinÃ¡mica (columna_1, columna_2, etc.)
        $columna = 'columna_' . $columnaId;

        // Verificar si la columna existe en el array $fillable
        if (!in_array($columna, $producto->getFillable())) {
            return response()->json(['error' => 'Columna no vÃ¡lida'], 400);
        }

        // Obtener el valor de la columna dinÃ¡mica
        $valor = $producto->$columna;

        // Si el valor es null, devolver una respuesta vacÃ­a o un valor predeterminado
        if ($valor === null) {
            return response()->json(''); // O puedes devolver un valor predeterminado, por ejemplo: return response()->json('No disponible');
        }

        // Retornar el valor de la columna
        return response()->json($valor);
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
                'orden' => ($rawOrden !== null && $rawOrden !== '')
                    ? substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $rawOrden), 0, 2)
                    : (string) $i,
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
            $producto->partesRelacionadas()->sync(
                $filasParte->mapWithKeys(fn (array $f): array => [$f['id'] => ['orden' => $f['orden']]])->all(),
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
                    'nombre' => trim((string) ($nombres[$i] ?? '')),
                    'valor' => trim((string) $valor),
                'orden' => ($rawOrden !== null && $rawOrden !== '')
                    ? substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $rawOrden), 0, 2)
                    : (string) $i,
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
                    'nombre' => trim((string) ($nombres[$i] ?? '')),
                    'valor' => trim((string) $valor),
                'orden' => ($rawOrden !== null && $rawOrden !== '')
                    ? substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $rawOrden), 0, 2)
                    : (string) $i,
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
