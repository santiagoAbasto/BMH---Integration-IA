<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nosotros;
use Illuminate\Support\Facades\File;
use App\Models\Metadatos;
use App\Models\Imagen;

class NosotrosController extends Controller
{
    public function index(){
        $nosotros_contenido = Nosotros::find(1);
        $ventana = 'nosotros-nav';
        $nosotros_portada = Imagen::where('sector', 'nosotros-portada')->first();
        $nosotros_baner = Imagen::where('sector', 'nosotros-baner')->first();
        $nosotros_procesos = Imagen::where('sector', '=', 'nosotros-procesos')->orderBy('orden')->get();

        return view('frontend/nosotros', compact('nosotros_contenido', 'ventana', 'nosotros_portada', 'nosotros_baner', 'nosotros_procesos'));
    }

    public function update(Request $request){
        $nosotros_contenido = Nosotros::find(1);

        $imagen = Imagen::where('sector', 'nosotros-portada')->first();
        if ($request->hasFile('portada')){

            $file = $request->file('portada');

            
            $extension = $file->getClientOriginalExtension();

            if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'])){
                $imagen->tipo = 'video';
            } elseif (in_array($extension, ['jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'jpg'])){
                $imagen->tipo = 'imagen';
            } else {
                return redirect()->back()->with('warning', 'El tipo de archivo para el baner seleccionado no es válido');
            }

            File::delete(public_path('imagenes/'.$imagen->path));
            $nombreImagen = 'media_' . uniqid() . '.' . $extension;
            $file->move('imagenes', $nombreImagen);
            $imagen->path = $nombreImagen;
            $imagen->save();
        }

        $imagen = Imagen::where('sector', 'nosotros-baner')->first();
        if ($request->hasFile('baner')){
            File::delete(public_path('imagenes/'.$imagen->path));

            $file = $request->file('baner');
            $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $nombreImagen);
            $imagen->path = $nombreImagen;
            $imagen->save();
        }

        $nosotros_contenido->info_home = $request->info;
        $nosotros_contenido->mision = $request->mision;
        $nosotros_contenido->vision = $request->vision;
        $nosotros_contenido->valores = $request->valores;
        // $nosotros_contenido->titulo_baner = $request->titulo_baner;
        // $nosotros_contenido->texto_baner = $request->texto_baner;
        $nosotros_contenido->save();
        return redirect()->back()->with('success', 'Información actualizada');

    }
}
