<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Impuesto;

class ImpuestoController extends Controller
{
    public function impuestos(){
        $impuestos = Impuesto::all();
        return view('backend/dash-impuestos', compact('impuestos'));
    }

    public function update(Request $request){
        $impuesto = Impuesto::find($request->id);
        $impuesto->nombre = $request->nombre;
        $impuesto->texto = $request->texto;
        $impuesto->porcentaje = $request->porcentaje;
        $impuesto->save();

        return redirect()->back()->with('success', 'Impuesto actualizado');

    }
}
