<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Novedad;
use App\Models\Metadatos;
use Illuminate\Support\Facades\File;

class NovedadController extends Controller
{
    public function index(){
        $novedades = Novedad::orderBy('orden')->get();
        $metadatos = Metadatos::where('seccion', 'novedades')->get();
        $ventana = 'novedades-nav';
        return view('frontend/novedades', compact('metadatos', 'novedades', 'ventana'));
    }

    public function novedad(Request $request){
        $novedad = Novedad::find($request->id);
        $ventana = 'novedades-nav';
        $metadatos = Metadatos::where('seccion', 'novedades')->get();
        return view('frontend/novedad', compact('metadatos', 'novedad', 'ventana'));
    }

    public function dash_novedades(){
        $novedades = Novedad::orderBy('orden')->get();
        return view('backend/dash-novedades', compact('novedades'));
    }

    public function create(){
        return view('backend/dash-novedad-crear');
    }

    public function store(Request $request){
        $novedad = new Novedad();

        if ($request->hasFile('imagen')){

            $file = $request->file('imagen');
            $file_name = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $file_name);
            $novedad->portada = $file_name;
        }

        if($request->destacada == null){
            $novedad->destacada = false;
        } else {
            $novedad->destacada = true;
        }

        $novedad->titulo = $request->titulo;
        $novedad->etiqueta = $request->etiqueta;
        $novedad->orden = $request->orden;
        $novedad->epigrafe = $request->epigrafe;
        $novedad->texto = $request->texto;

        $novedad->save();

        return redirect()->back()->with('success', 'Novedad creada');
    }

    public function edit(Request $request){
        $novedad = Novedad::find($request->id);
        return view('backend/dash-novedad-editar', compact('novedad'));
    }

    public function update(Request $request){
        $novedad = Novedad::find($request->id);

        if ($request->hasFile('imagen')){

            $file = $request->file('imagen');
            $file_name = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $file_name);

            File::delete(public_path('imagenes/'.$novedad->portada));
            $novedad->portada = $file_name;
        }

        $novedad->titulo = $request->titulo;
        $novedad->etiqueta = $request->etiqueta;
        $novedad->orden = $request->orden;
        $novedad->epigrafe = $request->epigrafe;
        $novedad->texto = $request->texto;

        $novedad->save();

        return redirect()->back()->with('success', 'Novedad actualizada');
    }

    public function actualizarDestacada(Request $request){ 

        $novedad = Novedad::find($request->novedad_id);
        
        $novedad->destacada = !$novedad->destacada;
        
        $novedad->save();
    }

    public function delete(Request $request){
        $novedad = Novedad::find($request->id);
        File::delete(public_path('imagenes/'.$novedad->portada));

        $nombre = $novedad->titulo;
        $novedad->delete();
        return redirect()->back()->with('success', 'La novedad  "'.$nombre.'" ha sido eliminada');
    }
}
