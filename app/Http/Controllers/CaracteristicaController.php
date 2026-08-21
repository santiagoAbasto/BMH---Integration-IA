<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Caracteristica;

class CaracteristicaController extends Controller
{
    public function dash_caracteristicas(){
        $caracteristicas = Caracteristica::orderBy('orden', 'desc')->get();
        return view('backend/dash-caracteristicas', compact('caracteristicas'));
    }

    public function store(Request $request){

        if(!Caracteristica::where('nombre',$request->nombre)->exists()){
            $car = new Caracteristica();
            $car->orden = $request->orden;
            $car->nombre = $request->nombre;
            $car->save();
            return redirect()->back()->with('success', 'Característica creada');
        } else {
            return redirect()->back()->with('warning', 'Ya existe una característica con ese nombre');
        }
        
    }

    public function update(Request $request){
        $car = Caracteristica::find($request->id);
        $car->orden = $request->orden;
        $car->nombre = $request->nombre;
        $car->save();
        return redirect()->back()->with('success', 'Característica actualizada');
    }

    public function delete(Request $request){
        $car = Caracteristica::find($request->id);
        $car->delete();
        return redirect()->back()->with('success', 'Característica eliminada');
    }
}
