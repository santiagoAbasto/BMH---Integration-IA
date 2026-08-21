<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CodigoPostal;
use App\Models\ZonaPostal;

class CodigoPostalController extends Controller
{

    public function codigos_postales(){
        $codigos = CodigoPostal::all();
        $zonas = ZonaPostal::orderBy('nombre')->get();
        return view('backend/dash-codigos-postales', compact('codigos', 'zonas'));
    }

    public function buscar(Request $request){
        $codigos = CodigoPostal::where('cp', 'like', '%'.$request->valor.'%')
        ->orWhere('provincia', 'like', '%'.$request->valor.'%')
        ->orWhere('localidad', 'like', '%'.$request->valor.'%')
        ->orWhere('zona', 'like', '%'.$request->valor.'%')->get();
    
        return view('backend/dash-codigos-postales-listado', compact('codigos'));
    }

    public function update(Request $request){
        return $request->codigos;
        if(isset($request->codigos)){
            foreach($request->codigos as $id){
                $codigo = CodigoPostal::find($id);
                $codigo->zona = $request->zona;
                $codigo->save();
            }
            return redirect()->back()->with('success', 'Códigos postales actualizados');
        } else {
            return redirect()->back()->with('warning', 'Debe seleccionar uno o mas códigos postales');
        }
    }
}
