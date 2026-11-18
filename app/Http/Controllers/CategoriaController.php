<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Caracteristica;
use App\Models\Categoria;
use App\Services\Imagenes\EmparejarBordes;
use App\Services\Imagenes\FotosParejas;
use App\Services\Imagenes\NoSePuedeEmparejar;
use App\Models\Producto;
use App\Models\Imagen;
use Illuminate\Support\Facades\File;
use App\Models\Metadatos;
use App\Models\Subcategoria;
use App\Models\CaracteristicaCategoria;
use Illuminate\Support\Facades\DB;

class CategoriaController extends Controller
{
   public function show($id)
{
    $categoria = Categoria::with('caracteristicas')->findOrFail($id);

    // Generar dinámicamente todas las columnas que empiecen con "columna_"
    $columnas = collect($categoria->getAttributes())
        ->filter(function($value, $key) {
            return str_starts_with($key, 'columna_') && !empty($value);
        });

    return response()->json([
        'id'            => $categoria->id,
        'nombre'        => $categoria->nombre,
        'columnas'      => $columnas, // todas las columnas dinámicas en un array asociativo
        'caracteristicas' => $categoria->caracteristicas->map(function($c) {
            return [
                'id'     => $c->id,
                'nombre' => $c->nombre,
            ];
        })->values(),
    ]);
}


    public function index()
    {
        $categorias = Categoria::with('caracteristicas')->orderBy('orden')->get();
        $ventana = 'categorias-nav';
        return view('frontend/categorias', compact('categorias', 'ventana'));
    }

  public function dash_categorias(Request $request)
{
    $categorias = Categoria::query()
        ->with('caracteristicas:id,nombre')
        ->orderBy('orden')
        ->get();
    $caracteristicas = Caracteristica::query()
        ->select(['id', 'nombre'])
        ->get();

    // Filtro de productos
    // La vista solo necesita estos tres campos para el selector. Evita cargar
    // las columnas de texto largo de miles de productos en cada request.
    $query = Producto::query()
        ->select(['id', 'codigo', 'nombre'])
        ->orderBy('orden');

    if ($request->filled('categoria_id') && $request->categoria_id != 'todos') {
        $query->where('categoria_id', $request->categoria_id);
    }

    $productos = $query->get();

    return view('backend/dash-categorias', compact('categorias', 'productos', 'caracteristicas'));
}


    public function create(Request $request)
    {
        $caracteristicas = Caracteristica::orderBy('orden')->get();
        return view('backend/dash-categorias-create', compact('caracteristicas'));
    }

    public function store(Request $request)
    {
        $categoria = new Categoria();

        if ($request->hasFile('portada')) {
            $categoria->portada = $this->guardarPortada($request);
        }


        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'columna_') && !empty($value)) {
                // Asigna el valor dinámico directamente al modelo, si tienes columnas para ello
                $categoria->$key = $value; // Asegúrate de que el nombre de la columna exista en tu tabla
            }
        }
        $categoria->orden = $request->orden;
        $categoria->nombre = $request->nombre;
        // $categoria->descuento = $request->descuento;
        $categoria->save();


        if ($request->has('caracteristicas')) {
            foreach (array_unique($request->input('caracteristicas', [])) as $caracteristica_id) {
                DB::table('categoria_caracteristica')->insert([
                    'categoria_id' => $categoria->id,
                    'caracteristica_id' => $caracteristica_id,
                    'created_at' => now()
                ]);
            }
        }

        return redirect()->back()->with('success', 'Categoría creada exitosamente');
    }

    public function update(Request $request)
    {
        
        $categoria = Categoria::find($request->id);

        if ($request->hasFile('portada')) {
            $anterior = $categoria->portada;
            $categoria->portada = $this->guardarPortada($request);
            $this->borrarPortada($anterior, $categoria->id);
        }
        


        foreach (range(1, 78) as $i) {
            $key = "columna_$i";
            $categoria->$key = null; // Limpia el valor actual

        }

        // Luego, actualiza las columnas con los valores del request
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'columna_') && !empty($value)) {
                // Asigna el valor dinámico directamente al modelo, si tienes columnas para ello
                $categoria->$key = $value; // Asegúrate de que el nombre de la columna exista en tu tabla
            }
        }

        $categoria->orden = $request->orden;
        $categoria->nombre = $request->nombre;
        // $categoria->descuento = $request->descuento;
        $categoria->save();

        DB::table('categoria_caracteristica')->where('categoria_id', $categoria->id)->delete();

        if ($request->has('caracteristicas')) {
            foreach (array_unique($request->input('caracteristicas', [])) as $caracteristica_id) {
                DB::table('categoria_caracteristica')->insert([
                    'categoria_id' => $categoria->id,
                    'caracteristica_id' => $caracteristica_id,
                    'created_at' => now()
                ]);
            }
        }

        return redirect()->back()->with('success', 'Categoría actualizada');
    }

    public function subcategoria_delete(Request $request)
    {
        $subcat = Subcategoria::find($request->id);
        $subcat->delete();
        return redirect()->back()->with('success', 'Subcategoría eliminada');
    }

    // ---------------------------------------------------------------- Emparejar bordes

    /**
     * Guarda la foto subida y, si se marcó «Emparejar bordes», la emparejada (el producto centrado y del mismo tamaño
     * que en las demás). La original queda guardada para poder volver.
     */
    private function guardarPortada(Request $request): string
    {
        $file = $request->file('portada');
        $nombre = 'media_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move(config('imagenes.directorio'), $nombre);

        if ($request->boolean('emparejar_bordes')) {
            try {
                return app(EmparejarBordes::class)->emparejar($nombre, EmparejarBordes::CATEGORIA);
            } catch (NoSePuedeEmparejar $e) {
                session()->flash('warning', 'La foto se guardó tal cual: ' . $e->getMessage());
            }
        }

        return $nombre;
    }

    /** Borra la foto anterior (con su original o su emparejada) si ninguna otra categoría la usa. */
    private function borrarPortada(?string $portada, int $menosId): void
    {
        if (blank($portada)) {
            return;
        }
        foreach (app(EmparejarBordes::class)->familia($portada) as $archivo) {
            if (! Categoria::query()->where('portada', $archivo)->whereKeyNot($menosId)->exists()) {
                File::delete(config('imagenes.directorio') . DIRECTORY_SEPARATOR . $archivo);
            }
        }
    }

    /** Empareja los bordes de la foto de una categoría (o vuelve a la original). */
    public function bordes(Request $request, Categoria $categoria, FotosParejas $fotos)
    {
        $volver = $request->input('accion') === 'original';
        abort_if(blank($categoria->portada), 404);
        $nombre = ucfirst(mb_strtolower($categoria->nombre));
        try {
            $volver ? $fotos->deshacer($categoria->portada) : $fotos->emparejar($categoria->portada, EmparejarBordes::CATEGORIA);
        } catch (NoSePuedeEmparejar $e) {
            return redirect()->back()->with('warning', $nombre . ': ' . $e->getMessage());
        }

        return redirect()->back()->with('success', $volver
            ? $nombre . ': volvió a la foto original.'
            : $nombre . ': bordes emparejados. Si no te gusta, «Volver a la original» la deja como estaba.');
    }

    /** Todas las categorías con foto: emparejar o volver a las originales. Las que no se pueden, se dicen. */
    public function bordesDeTodas(Request $request, FotosParejas $fotos)
    {
        $volver = $request->input('accion') === 'original';
        $hechas = 0;
        $avisos = [];
        foreach (Categoria::query()->whereNotNull('portada')->where('portada', '!=', '')->orderBy('orden')->get() as $categoria) {
            if ($volver !== EmparejarBordes::esPareja($categoria->portada)) {
                continue; // ya está como se pidió
            }
            try {
                $volver ? $fotos->deshacer($categoria->portada) : $fotos->emparejar($categoria->portada, EmparejarBordes::CATEGORIA);
                $hechas++;
            } catch (NoSePuedeEmparejar $e) {
                $avisos[] = ucfirst(mb_strtolower($categoria->nombre)) . ': ' . $e->getMessage();
            }
        }

        $mensaje = $volver
            ? ($hechas === 1 ? 'Volvió a la original 1 foto.' : "Volvieron a la original {$hechas} fotos.")
            : ($hechas === 1 ? 'Se emparejaron los bordes de 1 foto.' : "Se emparejaron los bordes de {$hechas} fotos.");
        $respuesta = redirect()->back()->with('success', $mensaje);

        return $avisos === [] ? $respuesta : $respuesta->with('warning', 'No se pudieron emparejar: ' . implode(' ', $avisos));
    }

    public function delete(Request $request)
    {
        $categoria = Categoria::find($request->id);
        $this->borrarPortada($categoria->portada, $categoria->id);
        $nombre = $categoria->nombre;
        $categoria->delete();
        return redirect()->back()->with('success', 'La categoría ' . ucfirst($nombre) . ' ha sido eliminada');
    }

    public function actualizarDestacado(Request $request)
    {

        $categoria = Categoria::find($request->producto_id);

        $categoria->destacada = !$categoria->destacada;

        $categoria->save();
    }

    public function subcategorias($id)
    {
        $categoria = Categoria::find($id);
        $subcategorias = $categoria->subcategorias()->get();
        return response()->json($subcategorias);
    }

    public function getAtributos($id)
    {
        // Obtén la categoría por su ID
        $categoria = Categoria::find($id);
    
        // Si la categoría no existe, devolver error
        if (!$categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }
    
        // Inicializamos un array para almacenar los atributos
        $atributos = [];
    
        // Recorrer las columnas columna_24 hasta columna_68
        for ($i = 1; $i <= 78; $i++) {
            $columna = 'columna_' . $i; // Forma el nombre de la columna, e.g. 'columna_24'
    
            // Verifica si la columna existe, tiene un valor y no es nula ni vacía
            if (!is_null($categoria->$columna) && $categoria->$columna !== '') {
                $atributos[] = [
                    'nombre' => $categoria->$columna, // El valor de la columna
                    'value' => $columna               // El nombre de la columna como valor
                ];
            }
        }
    
        // Retorna los atributos en formato JSON
        return response()->json($atributos);
    }
    
    public function actualizarPorcentaje(Request $request)
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'tipo'         => 'required|in:aumentar,descontar',
            'porcentaje'   => 'required|numeric'
        ]);


        if($request->estado == 0){
            $categoria = Categoria::find($request->categoria_id);
        
            if ($request->tipo === 'aumentar') {
                $categoria->aumento = $request->porcentaje;
                $categoria->descuento = 0;
            } 
            
            if ($request->tipo === 'limpiar') {

                $categoria->descuento = 0;
                $categoria->aumento = 0;
            }
            
            
            if ($request->tipo === 'descontar') {

                $categoria->descuento = $request->porcentaje;
                $categoria->aumento = 0;
            }

            $categoria->save();

        }
        
        if ($request->estado == 1) {
            $productos = Producto::where('categoria_id', $request->categoria_id)->where('estado', 1)->get();
    
            foreach ($productos as $producto) {
    
                if ($request->tipo == 'aumentar') {
                    $producto->aumento = $request->porcentaje;
                    $producto->descuento = 0;
                } else {
                    $producto->descuento = $request->porcentaje;
                    $producto->aumento = 0;                    }
    
                $producto->save();
            }}

            if ($request->estado == 2) {
                $productos = Producto::where('categoria_id', $request->categoria_id)->where('estado', 2)->get();
        
                foreach ($productos as $producto) {
        
                    if ($request->tipo == 'aumentar') {
                        $producto->aumento = $request->porcentaje;
                        $producto->descuento = 0;
                    } else {
                        $producto->descuento = $request->porcentaje;
                        $producto->aumento = 0;                    }
        
                    $producto->save();
                }}
    
    
        return redirect()->back()->with('success', 'Porcentaje actualizado correctamente.');
    }
    
    
}
