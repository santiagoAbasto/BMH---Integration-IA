<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\File;
use App\Models\Subcategoria;
use App\Models\Medida;

class MedidaController extends Controller
{
 

   

    public function store(Request $request){
        $medida = new Medida();
        $medida->codigo = $request->codigo;
        $medida->descripcion = $request->descripcion;
        $medida->cantidad = $request->cantidad;
        $medida->save();

        return redirect()->back()->with('success', 'Medida creada exitosamente');
    }

    public function update(Request $request){
        $medida = Medida::find($request->id);

        $medida->codigo = $request->codigo;
        $medida->descripcion = $request->descripcion;
        $medida->cantidad = $request->cantidad;
        $medida->save();
        return redirect()->back()->with('success', 'Medida actualizada');
    }

  

    public function delete(Request $request){
        $medida = Medida::find($request->id);
        $nombre = $medida->nombre;
        $medida->delete();
        return redirect()->back()->with('success', 'La medida '.ucfirst($nombre).' ha sido eliminada');
    }

    public function actualizarDestacado(Request $request){ 

        $categoria = Categoria::find($request->producto_id);
        
        $categoria->destacada = !$categoria->destacada;
        
        $categoria->save();
    }

    public function subcategorias($id) {
        $categoria = Categoria::find($id);
        $subcategorias = $categoria->subcategorias()->get();
        return response()->json($subcategorias);
    }

}
