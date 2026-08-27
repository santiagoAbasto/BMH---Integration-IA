<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Imagen;
use App\Models\Categoria;
use App\Models\Nosotros;
use Illuminate\Support\Facades\File;
use App\Models\Metadatos;
use App\Models\Producto;
use App\Models\Novedad;
use App\Models\Anuncio;
use App\Services\CatalogFilterOptions;
use Illuminate\Support\Facades\Http;

class HomeController extends Controller
{
    public function __construct(
        private readonly CatalogFilterOptions $catalogFilterOptions,
    ) {
    }

    public function home(Request $request){

        // $url = 'https://apitest.correoargentino.com.ar/paqar/v1/auth';

        // $response = Http::get($url);

        // if ($response->successful()) {
        //     $datos = $response->json();

        //     return view('tuvista', ['datos' => $datos]);
        // } else {
        //     return response()->json(['error' => 'La solicitud GET no fue exitosa'], 500);
        // }

        $home_slider = Imagen::where('sector','=','home-slider')->orderBy('orden')->get();
        $categorias = Categoria::where('destacada', true)->orderBy('orden')->get();
        $nosotros = Nosotros::find(1);
        $anuncio = Anuncio::find(1);
        $nosotros_slider = Imagen::where('sector', 'nosotros-slider')->orderBy('orden')->first();
        $seccion = 'home';
        $productos = Producto::with([
                'portadaImagen',
                'imagenesGaleria',
                'productCaracteristicas.caracteristica',
                'partesRelacionadas',
                'equivalencias',
                'aplicaciones',
            ])->where('destacada', true)->orderBy('orden')->get();
        $novedades = Novedad::where('destacada', true)->orderBy('orden')->get();
        $categoriasAll = Categoria::orderBy('nombre', 'asc')->get();

        $marcas = $this->catalogFilterOptions->brandsWithModels();

        //$request->session()->forget('modal_abierto');
        $anuncio_abierto = $request->session()->get('modal_abierto', 0); // Obtiene el valor de la sesión o 0 si no existe
        if($anuncio->mostrar == true){
            $anuncio_abierto++; // Incrementa el valor
            $request->session()->put('modal_abierto', $anuncio_abierto);
        }
        
        $registro = isset($request->registro);
        return view('frontend/home', compact('home_slider','categorias', 'nosotros', 'anuncio', 'anuncio_abierto', 'nosotros_slider', 'seccion', 'novedades', 'registro', 'productos', 'marcas', 'categoriasAll'));
    }

    public function search(Request $request){
        $busqueda = $request->search;

        $productos = Producto::with([
                'categoria',
                'portadaImagen',
                'imagenesGaleria',
                'productCaracteristicas.caracteristica',
                'partesRelacionadas.portadaImagen',
                'equivalencias',
                'aplicaciones',
            ])->whereHas('categoria', function ($query) use ($busqueda) {
            $query->where('nombre', 'like', '%'.$busqueda.'%');})
            ->orWhereHas('subcategoria', function ($query) use ($busqueda) {
            $query->where('nombre', 'like', '%'.$busqueda.'%');})
            ->orWhere('nombre', 'like', '%'.$busqueda.'%')
            ->orWhereHas('equivalencias', function ($q) use ($busqueda) {
                $q->where('valor', 'like', '%'.$busqueda.'%')->orWhere('nombre', 'like', '%'.$busqueda.'%');
            })
            ->orWhereHas('aplicaciones', function ($q) use ($busqueda) {
                $q->where('valor', 'like', '%'.$busqueda.'%')->orWhere('nombre', 'like', '%'.$busqueda.'%');
            })
            ->orWhereHas('partesRelacionadas', function ($q) use ($busqueda) {
                $q->where('codigo', 'like', '%'.$busqueda.'%')->orWhere('nombre', 'like', '%'.$busqueda.'%');
            })
            ->orderBy('orden')
            ->orderBy('cantidad', 'DESC')
            ->get();
        $productos->each(function (Producto $prod): void {
            $prod->setRelation(
                'productCaracteristicas',
                $prod->productCaracteristicas
                    ->sortBy(fn ($pc) => $pc->caracteristica->orden ?? PHP_INT_MAX)
                    ->values(),
            );
        });
        
        return view('frontend/search', compact('productos', 'busqueda'));
    }

    public function updateLogo(Request $request){
        $logo = Imagen::where('sector', 'logo')->get()->first();
        if ($request->hasFile('imagen')){
            File::delete(public_path('imagenes/'.$logo->path));
            $file = $request->file('imagen');
            $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $nombreImagen);
            $logo->path = $nombreImagen;
            $logo->save();
        }

        $logo2 = Imagen::where('sector', 'logo2')->get()->first();
        if ($request->hasFile('imagen2')){
            File::delete(public_path('imagenes/'.$logo2->path));
            $file = $request->file('imagen2');
            $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('imagenes', $nombreImagen);
            $logo2->path = $nombreImagen;
            $logo2->save();
        }

        // $imagen = Imagen::where('sector', 'home-slider')->first();
        // $imagen->baner_texto = $request->baner_texto;
        // if ($request->hasFile('baner')){

        //     $file = $request->file('baner');

            
        //     $extension = $file->getClientOriginalExtension();

        //     if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'])){
        //         $imagen->tipo = 'video';
        //     } elseif (in_array($extension, ['jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'jpg'])){
        //         $imagen->tipo = 'imagen';
        //     } else {
        //         return redirect()->back()->with('warning', 'El tipo de archivo para el baner seleccionado no es válido');
        //     }

        //     File::delete(public_path('imagenes/'.$imagen->path));
        //     $nombreImagen = 'media_' . uniqid() . '.' . $extension;
        //     $file->move('imagenes', $nombreImagen);
        //     $imagen->path = $nombreImagen;
        // }
        // $imagen->save();
        
        // $nosotros_contenido = Nosotros::find(1);
        // $nosotros_contenido->info_home = $request->info;
        // $nosotros_contenido->titulo_home = $request->titulo;



        // if ($request->hasFile('imagen3')){
        //     File::delete(public_path('imagenes/'.$nosotros_contenido->image_file_home));

        //     $file = $request->file('imagen2');
        //     $nombreImagen = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
        //     $file->move('imagenes', $nombreImagen);
        //     $nosotros_contenido->image_file_home = $nombreImagen;
        // }
        // $nosotros_contenido->save();

        $nosotros_slider = Imagen::where('sector', 'nosotros-slider')->orderBy('orden')->first();
        $nosotros_slider->baner_texto = $request->titulo;
        $nosotros_slider->baner_texto_2 = $request->info;


        
        $nosotros_slider->save();



        return redirect()->back()->with('success', 'Contenido actualizado');
    }

    public function updateSlider(Request $request){

        
        $imagen = Imagen::find($request->id);
        
        

        if ($request->hasFile('imagen')){

            $file = $request->file('imagen');
            $extension = $file->getClientOriginalExtension();
            

            if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'])){
                $imagen->tipo = 'video';
            } elseif (in_array($extension, ['jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'jpg'])){
                $imagen->tipo = 'imagen';
            } else {
                return redirect()->back()->with('warning', 'El tipo de archivo seleccionado no es válido');
            }

            File::delete(public_path('imagenes/'.$imagen->path));
            $nombreImagen = 'media_' . uniqid() . '.' . $extension;
            $file->move('imagenes', $nombreImagen);
            $imagen->path = $nombreImagen;
        }

        
        $imagen->orden = $request->orden;
        $imagen->baner_texto = $request->baner_texto;
        $imagen->baner_texto_2 = $request->baner_texto_2;

        $imagen->save();
        return redirect()->back();
    }

    public function slider_texto(Request $request){
        $contenido = Nosotros::find(1);
        $contenido->info = $request->slider_texto;
        $contenido->save();
        return redirect()->back()->with('success', 'Texto actualizado');
    }
    
}
