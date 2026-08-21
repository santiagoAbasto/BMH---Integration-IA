<?php

namespace App\Http\Controllers;

use App\Models\Descarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DescargaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $descargas = Descarga::whereNull('sector')->orderBy('orden')->get();
        $ventana = 'descargas-nav';
        return view('frontend/descargas', compact('descargas', 'ventana'));
    }

    public function datos_kc(){
        $modelo = Descarga::where('sector', 'datos-modelo')->first();
        $datos = Descarga::where('sector', 'datos-actual')->first();
        return view('frontend/datos-kc', compact('modelo', 'datos'));
    }

    public function datos_kc_update(Request $request){
        if($request->hasFile('file')){
            $file = $request->file;
            if ($file->isValid()) {
                $anterior = Descarga::where('sector', 'datos-actual')->first();
                File::delete(public_path('archivos/'.$anterior->path));

                $nombreArchivo = 'datoskc_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move('archivos', $nombreArchivo);
    
                $anterior->archivo = $file->getClientOriginalName();
                $anterior->path = $nombreArchivo;
                $anterior->save();
            }
        }

        return redirect()->back();
    }

    public function dash_descargas(){
        $descargas = Descarga::whereNull('sector')->orderBy('orden')->get();
        return view('backend/dash-descargas', compact('descargas'));
    }

    public function dash_lista(){
        $listas = Descarga::where('sector', 'lista de precios')->get();
        return view('backend/dash-lista', compact('listas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $nueva_descarga = new Descarga();
        
        $file = $request->file('file');
        if ($file->isValid()) {

            $nombreArchivo = 'media_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move('archivos', $nombreArchivo);

            $nueva_descarga->archivo = $file->getClientOriginalName();
            $nueva_descarga->path = $nombreArchivo;
            $nueva_descarga->nombre = $request->nombre;
            $nueva_descarga->orden = $request->orden;

            $nueva_descarga->save();
        }
            

        return redirect()->back()->with('success', 'Descarga creada');
    }

    /**
     * Display the specified resource.
     */
    public function show(Descarga $descarga)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Descarga $descarga)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
  public function update(Request $request)
{
    $descarga = Descarga::find($request->id);

    if ($request->hasFile('file')) {
        // Define la ruta absoluta de la carpeta 'archivos' dentro de public_html
        $rutaPublicHtml = $_SERVER['DOCUMENT_ROOT'] . '/archivos';

        // Asegúrate de que la carpeta exista
        if (!is_dir($rutaPublicHtml)) {
            mkdir($rutaPublicHtml, 0755, true); // Crea la carpeta con permisos adecuados
        }

        // Elimina el archivo anterior si existe
        if ($descarga->path) {
            $rutaAnterior = $rutaPublicHtml . '/' . $descarga->path;
            if (file_exists($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        // Maneja el nuevo archivo
        $file = $request->file('file');
        $nombreArchivo = 'archivo_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // Mueve el archivo a la carpeta en public_html
        $file->move($rutaPublicHtml, $nombreArchivo);

        $descarga->path = $nombreArchivo;
        $descarga->archivo = $file->getClientOriginalName();

        // Obtén el peso del archivo
        $pesoArchivoBytes = filesize($rutaPublicHtml . '/' . $nombreArchivo);

        // Convierte el peso del archivo a una unidad legible
        $pesoArchivo = $this->formatBytes($pesoArchivoBytes);
        $descarga->peso = $pesoArchivo;
        $descarga->formato = $file->getClientOriginalExtension();
    }

    $descarga->nombre = $request->nombre;
    $descarga->orden = $request->has('orden') ? $request->orden : 'aa';
    $descarga->save();

    return redirect()->back()->with('success', 'Descarga actualizada');
}


    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request)
    {
        $descarga = Descarga::find($request->id);
        File::delete(public_path('archivos/'.$descarga->path));
        $descarga->delete();

        return redirect()->back()->with('success', 'Descarga eliminada');
    }
}
