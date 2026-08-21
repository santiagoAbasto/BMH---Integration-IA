<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Imagen;
use Illuminate\Support\Facades\File;

class ImagenController extends Controller
{
    public function store(Request $request){

        if($request->hasFile('imagenes')){
            $files = $request->file('imagenes');

            // Recorrer cada archivo
            foreach ($files as $file) {
                // Verificar si el archivo se cargó correctamente
                if ($file->isValid()) {

                    $extension = $file->getClientOriginalExtension();
                    $nuevaImagen = new Imagen();

                    if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'])){
                        $nuevaImagen->tipo = 'video';
                    } elseif (in_array($extension, ['jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'jpg'])){
                        $nuevaImagen->tipo = 'imagen';
                    } else {
                        return redirect()->back()->with('warning', 'El tipo de archivo seleccionado no es válido');
                    }

                    // Manejar la lógica para guardar o procesar la imagen
                    // $nombreImagen = $file->getClientOriginalName();
                    $nombreImagen = 'media_' . uniqid() . '.' . $extension;
                    $file->move('imagenes', $nombreImagen);

                    $nuevaImagen->path = $nombreImagen;
                    $nuevaImagen->sector = $request->sector;
                    $nuevaImagen->orden = $request->orden;
                    $nuevaImagen->baner_texto = $request->baner_texto;
                    $nuevaImagen->baner_texto_2 = $request->baner_texto_2;

                    $nuevaImagen->save();
                }
            }
        }

        return redirect()->back()->with('success', 'Imágenes agregadas');
        
    }

    public function delete(Request $request){
        $imagen = Imagen::find($request->id);
        File::delete(public_path('imagenes/'.$imagen->path));
        $imagen->delete();

        if($imagen->tipo == 'portada'){
            $resultado = Imagen::where('producto_id', $imagen->producto_id)->where('sector', $imagen->sector)->first();
            $resultado->tipo = 'portada';
            $resultado->save();

        }

        return redirect()->back()->with('success', 'Imagen eliminada');
        
    }

    public function update(Request $request){
        $imagen = Imagen::find($request->id);

        $anterior = Imagen::where('producto_id', $imagen->producto_id)
                       ->where('tipo', 'portada')
                       ->get()->first();
        $anterior->tipo = null;
        $anterior->orden = 'ab';
        $anterior->save();

        $imagen->tipo='portada';
        $imagen->orden='aa';
        $imagen->save();

        return redirect()->back();
    }
}
