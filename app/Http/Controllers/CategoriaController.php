<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Caracteristica;
use App\Models\Categoria;
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
    $categorias = Categoria::orderBy('orden')->get();
    $caracteristicas = Caracteristica::get();

    // Filtro de productos
    $query = Producto::orderBy('orden');

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
            File::delete(public_path('imagenes/' . $categoria->portada));
            $file = $request->file('portada');
            $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $nombreImagen);
            $categoria->portada = $nombreImagen;
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
            foreach ($request->caracteristicas as $caracteristica_id) {
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
            File::delete(public_path('imagenes/' . $categoria->portada));
            $file = $request->file('portada');
            $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $nombreImagen);
            $categoria->portada = $nombreImagen;
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
            foreach ($request->caracteristicas as $caracteristica_id) {
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

    public function delete(Request $request)
    {
        $categoria = Categoria::find($request->id);
        File::delete(public_path('imagenes/' . $categoria->portada));
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
