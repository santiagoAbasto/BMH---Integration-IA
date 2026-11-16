<?php

namespace App\Http\Controllers;

use App\Models\Bonificacion;
use App\Models\Home;
use App\Models\Imagen;
use App\Models\Nosotros;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Contacto;
use App\Models\Medida;
use App\Models\User;
use App\Models\Metadatos;
use App\Models\Repuesto;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Inicio del panel: actividad, pendientes y estado del sitio (App\Support\Panel\MetricasPanel). */
    public function index(\App\Support\Panel\MetricasPanel $metricas){
        return view('backend/dashboard', $metricas->todo());
    }
    
    public function logo(){
        // Home › Contenido pasó a Home › Sección Nosotros (los logos están en Extras).
        return redirect()->route('dashboard.home.seccion-nosotros');
    }

    public function home_slider(){
        $home_slider = Imagen::where('sector', '=', 'home-slider')->orderBy('orden')->get();
        $nosotros_contenido = Nosotros::find(1);
        return view('backend/dash-home-slider', compact('home_slider', 'nosotros_contenido'));
    }

    public function home_contenido(){
        $home_settings = Home::find(1);
        return view('backend/dash-home-contenido', compact('home_settings'));
    }

    public function nosotros(){
        $nosotros_contenido = Nosotros::find(1);
        $nosotros_portada = Imagen::where('sector', 'nosotros-portada')->first();

        return view('backend/dash-nosotros', compact('nosotros_contenido', 'nosotros_portada'));
    }

    public function categorias(){
        $categorias = Categoria::orderBy('orden')->get();
        $productos = Producto::orderBy('orden')->get();

        return view('backend/dash-categorias', compact('categorias', 'productos'));
    }

    public function productos(){
        $productos = Producto::orderBy('orden')->get();
        $categorias = Categoria::orderBy('orden')->get();
        $productos_imagenes = Imagen::whereNotNull('producto_id')->get();
        return view('backend/dash-productos', compact('productos', 'categorias', 'productos_imagenes'));
    }

    
    public function medidas(){
       
        $medidas = Medida::orderBy('codigo')->get();
        return view('backend/dash-medidas', compact('medidas'));
    }

    public function repuestos(){
       
        $repuestos = Repuesto::orderBy('codigo')->get();
        return view('backend/dash-repuesto', compact('repuestos'));
    }

    public function bonificaciones(){
        $bonificaciones = Bonificacion::orderBy('orden')->get();
        return view('backend/dash-bonificaciones', compact('bonificaciones')); 
    }
    public function ofertas(){
        $ofertas = Producto::whereNotNull('descuento')->distinct()->orderBy('descuento')->pluck('descuento');
        $productos = Producto::orderBy('nombre')->get();
        return view('backend/dash-ofertas', compact('ofertas', 'productos'));
    }

    /** Contacto › Contenido (la bandeja de mensajes está en ContactoMensajeController). */
    public function contacto(){
        $contacto = Contacto::actual();
        $sinLeer = \App\Models\ContactoMensaje::sinLeer();
        return view('backend/dash-contacto', compact('contacto', 'sinLeer'));
    }
}
