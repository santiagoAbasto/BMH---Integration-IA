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
use Illuminate\Support\Facades\DB;

class CategoriaController extends Controller
{
    /** Las características de una categoría (admin de productos: al elegir la categoría). */
    public function show($id)
    {
        $categoria = Categoria::with('caracteristicas')->findOrFail($id);

        return response()->json([
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'caracteristicas' => $categoria->caracteristicas->map(fn (Caracteristica $c) => $c->paraFormulario())->values(),
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
        ->select(['id', 'nombre', 'orden'])
        ->ordenadas()
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
            File::delete(public_path('imagenes/' . $categoria->portada));
            $file = $request->file('portada');
            $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $nombreImagen);
            $categoria->portada = $nombreImagen;
        }


        $categoria->orden = $request->orden;
        $categoria->nombre = $request->nombre;
        // $categoria->descuento = $request->descuento;
        $categoria->save();


        $categoria->caracteristicas()->sync($this->idsCaracteristicas($request));

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
        


        $categoria->orden = $request->orden;
        $categoria->nombre = $request->nombre;
        // $categoria->descuento = $request->descuento;
        $categoria->save();

        $categoria->caracteristicas()->sync($this->idsCaracteristicas($request));

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

    /**
     * Las características de la categoría para el buscador viejo (/filtro-rodamiento): `value` es el id
     * de la característica, que es lo que ese filtro recibe en `atributo`.
     */
    public function getAtributos($id)
    {
        $categoria = Categoria::with('caracteristicas')->find($id);

        if (! $categoria) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }

        return response()->json($categoria->caracteristicas->map(fn (Caracteristica $c) => [
            'nombre' => $c->nombre,
            'value' => (string) $c->id,
        ])->values());
    }

    /** @return list<int> ids de características existentes que manda el formulario (`caracteristicas[]`) */
    private function idsCaracteristicas(Request $request): array
    {
        $ids = array_filter(
            array_map('intval', (array) $request->input('caracteristicas', [])),
            fn (int $id) => $id > 0,
        );

        return $ids === [] ? [] : Caracteristica::query()->whereKey(array_unique($ids))->pluck('id')->all();
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
