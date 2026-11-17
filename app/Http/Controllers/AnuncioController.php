<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Anuncio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Panel › Carrito › Anuncio: la imagen que ven los clientes, una vez, al ingresar a la Zona de Clientes.
 */
class AnuncioController extends Controller
{
    public const PESO_MAXIMO_KB = 5120;

    public const ANCHO_MINIMO = 500;

    /** Tamaño en que se muestra en una pantalla de escritorio (y proporción recomendada, 7:5). */
    public const ANCHO_VENTANA = 700;

    public const ALTO_VENTANA = 500;

    public function index(): View
    {
        return view('backend/dash-anuncio', [
            'anuncio' => Anuncio::actual(),
            'pesoMaximoKb' => self::PESO_MAXIMO_KB,
            'anchoMinimo' => self::ANCHO_MINIMO,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $anuncio = Anuncio::actual();

        $request->validate([
            'imagen' => [
                filled($anuncio->imagen) ? 'nullable' : 'required',
                'file', 'image', 'mimes:jpg,jpeg,png,webp,gif',
                'max:'.self::PESO_MAXIMO_KB,
                'dimensions:min_width='.self::ANCHO_MINIMO,
            ],
            'texto_alternativo' => ['nullable', 'string', 'max:200'],
        ], [
            'imagen.required' => 'Elegí la imagen del anuncio.',
            'imagen.image' => 'El archivo tiene que ser una imagen JPG, PNG, WEBP o GIF.',
            'imagen.mimes' => 'El archivo tiene que ser una imagen JPG, PNG, WEBP o GIF.',
            'imagen.max' => 'La imagen no puede pesar más de 5 MB.',
            'imagen.dimensions' => 'La imagen tiene que medir al menos '.self::ANCHO_MINIMO.' px de ancho para no verse pixelada.',
            'texto_alternativo.max' => 'La descripción puede tener hasta 200 caracteres.',
        ]);

        if ($archivo = $request->file('imagen')) {
            [$ancho, $alto] = getimagesize($archivo->getRealPath()) ?: [null, null];
            $nombre = 'anuncio-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$archivo->guessExtension();
            $archivo->move(public_path('imagenes'), $nombre);

            $anterior = $anuncio->imagen;
            $anuncio->imagen = $nombre;
            $anuncio->ancho = $ancho;
            $anuncio->alto = $alto;
            $this->borrarAnterior($anterior);
        }

        $anuncio->texto_alternativo = filled($request->texto_alternativo) ? trim($request->texto_alternativo) : null;
        $anuncio->save();

        return redirect()->route('dashboard.anuncio')->with('success', $archivo ? 'Imagen del anuncio actualizada' : 'Anuncio actualizado');
    }

    /** Mostrar u ocultar el anuncio (el interruptor de la pantalla; se guarda al tocarlo). */
    public function mostrar(Request $request): JsonResponse
    {
        $request->validate(['mostrar' => ['required', 'boolean']]);

        $anuncio = Anuncio::actual();
        $mostrar = $request->boolean('mostrar');

        if ($mostrar && blank($anuncio->imagen)) {
            return response()->json(['mostrar' => false, 'mensaje' => 'Cargá una imagen antes de mostrar el anuncio.'], 422);
        }

        $anuncio->mostrar = $mostrar;
        $anuncio->save();

        return response()->json([
            'mostrar' => $anuncio->mostrar,
            'mensaje' => $anuncio->mostrar ? 'Los clientes van a ver el anuncio al ingresar.' : 'El anuncio está oculto.',
        ]);
    }

    /** Sólo las imágenes propias del anuncio: nunca otra de public/imagenes. */
    private function borrarAnterior(?string $archivo): void
    {
        if ($archivo === null || ! preg_match('/^anuncio-[A-Za-z0-9-]+\.(png|jpe?g|webp|gif)$/', $archivo)) {
            return;
        }

        $ruta = public_path('imagenes/'.$archivo);

        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
