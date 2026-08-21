<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categoria;
use Illuminate\Support\Facades\File;
use App\Models\Subcategoria;
use App\Models\Repuesto;

class RepuestosController extends Controller
{
 
    public function store(Request $request){
        $repuesto = new Repuesto();
        $repuesto->codigo = $request->codigo;
        $repuesto->descripcion = $request->descripcion;
        $repuesto->cantidad = $request->cantidad;
        $repuesto->save();

        return redirect()->back()->with('success', 'Repuesto creado exitosamente');
    }

    public function update(Request $request){
        $repuesto = Repuesto::find($request->id);

        $repuesto->codigo = $request->codigo;
        $repuesto->descripcion = $request->descripcion;
        $repuesto->cantidad = $request->cantidad;
        $repuesto->save();
        return redirect()->back()->with('success', 'Repuesto actualizado');
    }

  

    public function delete(Request $request){
        $repuesto = Repuesto::find($request->id);
        $nombre = $repuesto->nombre;
        $repuesto->delete();
        return redirect()->back()->with('success', 'El repuesto '.ucfirst($nombre).' ha sido eliminado');
    }



}
