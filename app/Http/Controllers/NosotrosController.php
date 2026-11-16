<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Nosotros;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class NosotrosController extends Controller
{
    public function index()
    {
        $nosotros_contenido = Nosotros::find(1);
        $ventana = 'nosotros-nav';
        $nosotros_portada = Imagen::where('sector', 'nosotros-portada')->first();

        return view('frontend/nosotros', compact('nosotros_contenido', 'ventana', 'nosotros_portada'));
    }

    /**
     * Admin → Nosotros: la historia (título, texto y foto) y «Lo que nos mueve» (título y las tres tarjetas).
     * Los textos se guardan en las columnas que lee la página (antes el texto de la historia iba a `info_home`, que la
     * página no usa, y los cambios no se veían).
     */
    public function update(Request $request)
    {
        $datos = $request->validate([
            'historia_titulo' => ['nullable', 'string', 'max:160'],
            'info' => ['nullable', 'string', 'max:30000'],
            'valores_titulo' => ['nullable', 'string', 'max:120'],
            'mision' => ['nullable', 'string', 'max:10000'],
            'vision' => ['nullable', 'string', 'max:10000'],
            'valores' => ['nullable', 'string', 'max:10000'],
            'portada' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'portada.mimes' => 'La foto tiene que ser JPG, PNG o WebP.',
            'portada.max' => 'La foto pesa más de 4 MB: achicala antes de subirla.',
        ]);

        $contenido = Nosotros::query()->firstOrNew(['id' => 1]);
        foreach (['historia_titulo', 'valores_titulo'] as $campo) {
            $contenido->{$campo} = filled($datos[$campo] ?? null) ? trim($datos[$campo]) : null;
        }
        foreach (['info', 'mision', 'vision', 'valores'] as $campo) {
            $contenido->{$campo} = $this->textoOVacio($datos[$campo] ?? null);
        }
        $contenido->save();

        if ($request->hasFile('portada')) {
            $foto = Imagen::query()->where('sector', 'nosotros-portada')->first();
            if ($foto === null) {
                $foto = new Imagen();
                $foto->sector = 'nosotros-portada';
                $foto->orden = 'aa';
            }
            $anterior = $foto->path;
            $archivo = $request->file('portada');
            $nombre = 'nosotros-portada_'.bin2hex(random_bytes(6)).'.'.strtolower($archivo->extension() ?: $archivo->getClientOriginalExtension());
            $archivo->move(public_path('imagenes'), $nombre);
            $foto->path = $nombre;
            $foto->tipo = 'imagen';
            $foto->save();
            // Sólo se borra una foto subida desde esta pantalla (las anteriores pueden estar en uso en otro lado).
            if ($anterior && preg_match('/^nosotros-portada_[a-f0-9]{12}\.[a-z0-9]+$/', $anterior) === 1) {
                File::delete(public_path('imagenes/'.$anterior));
            }
        }

        return redirect()->route('dashboard.nosotros')->with('success', 'Se guardó la página Nosotros.');
    }

    /** El editor deja «<p><br></p>» cuando se borra todo: eso cuenta como vacío ('': las columnas no aceptan NULL). */
    private function textoOVacio(?string $html): string
    {
        return trim(strip_tags((string) $html, '<img>')) === '' ? '' : (string) $html;
    }
}
