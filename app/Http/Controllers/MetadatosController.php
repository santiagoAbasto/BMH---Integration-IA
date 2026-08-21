<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Metadatos;

class MetadatosController extends Controller
{
    public function update(Request $request){
        $metadatos = Metadatos::all();
        foreach($metadatos as $dato){
            $dato->keyword = $request->keyword;
            $dato->descripcion = $request->descripcion;
            $dato->save();
        }
        
        return redirect()->back();
    }
}
