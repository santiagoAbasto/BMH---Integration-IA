<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ZonaPostal;
use App\Models\CodigoPostal;

class ZonaPostalController extends Controller
{
    public function zonas(){
        $zonas = ZonaPostal::orderBy('nombre')->get();
        return view('backend/dash-zonas-postales', compact('zonas'));
    }

    public function store(Request $request){
        if(ZonaPostal::where('nombre', $request->nombre)->first()){
            return redirect()->back()->with('warning', 'Ya existe una zona con ese nombre');
        } else {
            $zona = new ZonaPostal();
            $zona->nombre = $request->nombre;
            $zona->costo = $request->costo;
            $zona->save();
            return redirect()->back()->with('success', 'Zona postal creada');
        }        
    }

    public function update(Request $request){

        if(ZonaPostal::where('nombre', $request->nombre)->where('id', '!=', $request->id)->first()){
            return redirect()->back()->with('warning', 'Ya existe una zona con ese nombre');
        } else {
            $zona = ZonaPostal::find($request->id);
            $zona->nombre = $request->nombre;
            $zona->costo = $request->costo;
            $zona->save();
            return redirect()->back()->with('success', 'Zona postal actualizada');
        }        
    }

    public function delete(Request $request){
        $zona = ZonaPostal::find($request->id);
        
        $codigos = CodigoPostal::where('zona', $zona->nombre)->get();
        foreach($codigos as $cp){
            $cp->zona = null;
            $cp->save();
        }
        $zona->delete();

        return redirect()->back()->with('success', 'Zona eliminada');
    }
}
