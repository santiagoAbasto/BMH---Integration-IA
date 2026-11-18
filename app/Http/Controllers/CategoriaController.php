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
        ->withCount('productos')
        ->orderBy('orden')
        ->get();
    $caracteristicas = Caracteristica::query()
        ->select(['id', 'nombre', 'orden'])
        ->ordenadas()
        ->get();

    // «Ajustar precios por porcentaje»: el producto se busca mientras se escribe (producto.buscar.partes), así que
    // ya no se mandan los ~6.000 productos a la página. Las categorías llevan cuántos productos tienen.
    // Vista previa de la imagen (admin): la tarjeta al lado de la categoría vecina en el orden, como en el sitio.
    $conImagen = $categorias->filter(fn ($c) => filled($c->portada))->values();
    $tarjeta = fn ($c) => $c ? ['nombre' => $c->nombre, 'imagen' => asset('imagenes/'.$c->portada)] : null;
    $vecinas = [];
    foreach ($conImagen as $i => $c) {
        $vecinas[$c->id] = $tarjeta($conImagen[$i + 1] ?? $conImagen[$i - 1] ?? null);
    }
    foreach ($categorias as $c) {
        $vecinas[$c->id] ??= $tarjeta($conImagen->first());
    }
    $vecinaParaCrear = $tarjeta($conImagen->first());

    return view('backend/dash-categorias', compact('categorias', 'caracteristicas', 'vecinas', 'vecinaParaCrear'));
}


    public function create(Request $request)
    {
        $caracteristicas = Caracteristica::orderBy('orden')->get();
        return view('backend/dash-categorias-create', compact('caracteristicas'));
    }

    public function store(Request $request)
    {
        $request->validate(self::reglasImagen(), self::MENSAJES_IMAGEN);
        $categoria = new Categoria();

        if ($request->hasFile('portada')) {
            $categoria->portada = $this->guardarImagen($request);
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
        
        $request->validate(self::reglasImagen(), self::MENSAJES_IMAGEN);
        $categoria = Categoria::findOrFail($request->id);

        if ($request->hasFile('portada')) {
            $anterior = $categoria->portada;
            $categoria->portada = $this->guardarImagen($request);
            // La anterior se borra recién con la nueva guardada, y sólo si ninguna otra categoría la usa.
            if (filled($anterior) && $anterior !== $categoria->portada && ! Categoria::query()->where('portada', $anterior)->whereKeyNot($categoria->id)->exists()) {
                File::delete(config('imagenes.directorio').DIRECTORY_SEPARATOR.basename($anterior));
            }
        }
        


        $categoria->orden = $request->orden;
        $categoria->nombre = $request->nombre;
        // $categoria->descuento = $request->descuento;
        $categoria->save();

        $categoria->caracteristicas()->sync($this->idsCaracteristicas($request));

        return redirect()->back()->with('success', 'Categoría actualizada');
    }

    /** La imagen de la tarjeta: JPG, PNG o WebP de hasta 10 MB (se optimiza al guardar). */
    private static function reglasImagen(): array
    {
        return ['portada' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240']];
    }

    private const MENSAJES_IMAGEN = [
        'portada.image' => 'La imagen tiene que ser un JPG, PNG o WebP.',
        'portada.mimes' => 'La imagen tiene que ser un JPG, PNG o WebP.',
        'portada.max' => 'La imagen pesa más de 10 MB. Guardala más liviana (1080 × 1080 px alcanza).',
    ];

    /** Guarda la imagen subida optimizada (WebP, como las fotos de productos) y devuelve el nombre del archivo. */
    private function guardarImagen(Request $request): string
    {
        $directorio = config('imagenes.directorio');
        File::ensureDirectoryExists($directorio);

        return app(\App\Services\Imagenes\OptimizadorImagenes::class)->guardarSubida($request->file('portada'), $directorio);
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
