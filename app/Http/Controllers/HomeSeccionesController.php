<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HomeNosotros;
use App\Models\MarcaAliada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Admin → Home: «Trabajamos con» (los logos de clientes y proveedores, cada uno se crea y edita en un modal) y
 * «Sección Nosotros» (el bloque de la Home con imagen, título, descripción y botón).
 */
final class HomeSeccionesController extends Controller
{
    private const IMAGEN = ['file', 'mimes:jpg,jpeg,png,webp', 'max:4096'];

    private const MENSAJES = [
        'nombre.required' => 'Escribí el nombre de la empresa: es el texto que leen los buscadores y los lectores de pantalla.',
        'logo.required' => 'Elegí el logo.',
        'logo.mimes' => 'El logo tiene que ser JPG, PNG o WebP.',
        'logo.max' => 'El logo pesa más de 4 MB: achicalo antes de subirlo.',
        'imagen.mimes' => 'La imagen tiene que ser JPG, PNG o WebP.',
        'imagen.max' => 'La imagen pesa más de 4 MB: achicala antes de subirla.',
        'url.url' => 'El link tiene que ser una dirección completa, por ejemplo https://www.empresa.com.',
        'orden.regex' => 'El orden son dos letras o números, por ejemplo aa, ab, b1.',
        'titulo.required' => 'Escribí el título de la sección.',
        'boton_texto.required_with' => 'Si cargás el link del botón, escribí también el texto del botón.',
        'boton_url.required_with' => 'Si cargás el texto del botón, elegí también a dónde lleva.',
    ];

    // ---------------------------------------------------------------- Trabajamos con

    public function trabajamosCon(): View
    {
        return view('backend.home.trabajamos-con', [
            'marcas' => MarcaAliada::query()->ordenadas()->get(),
            'siguienteOrden' => MarcaAliada::siguienteOrden(),
        ]);
    }

    public function guardarMarca(Request $request): RedirectResponse
    {
        $datos = $this->validarMarca($request, nueva: true);
        $marca = new MarcaAliada($datos);
        $marca->logo = $this->subir($request->file('logo'), 'marca_');
        $marca->save();

        return redirect()->route('dashboard.home.trabajamos-con')->with('success', "«{$marca->nombre}» ya aparece en «Trabajamos con».");
    }

    public function actualizarMarca(Request $request, MarcaAliada $marca): RedirectResponse
    {
        $datos = $this->validarMarca($request, nueva: false);
        $marca->fill($datos);
        if ($request->hasFile('logo')) {
            $anterior = $marca->logoSubido() ? $marca->logo : null;
            $marca->logo = $this->subir($request->file('logo'), 'marca_');
            $this->borrar($anterior);
        }
        $marca->save();

        return redirect()->route('dashboard.home.trabajamos-con')->with('success', "Se guardaron los cambios de «{$marca->nombre}».");
    }

    public function visibilidadMarca(Request $request, MarcaAliada $marca): RedirectResponse
    {
        $marca->update(['visible' => $request->boolean('visible')]);

        return redirect()->route('dashboard.home.trabajamos-con')
            ->with('success', $marca->visible ? "«{$marca->nombre}» se muestra en la Home." : "«{$marca->nombre}» quedó oculto: no se ve en la Home.");
    }

    public function borrarMarca(MarcaAliada $marca): RedirectResponse
    {
        $this->borrar($marca->logoSubido() ? $marca->logo : null);
        $marca->delete();

        return redirect()->route('dashboard.home.trabajamos-con')->with('success', "Se eliminó «{$marca->nombre}».");
    }

    /** @return array{nombre: string, url: ?string, orden: string, visible: bool} */
    private function validarMarca(Request $request, bool $nueva): array
    {
        $request->merge(['orden' => strtolower(trim((string) $request->input('orden'))), 'url' => $this->conProtocolo($request->input('url'))]);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'logo' => [$nueva ? 'required' : 'nullable', ...self::IMAGEN],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'orden' => ['nullable', 'regex:/^[a-z0-9]{2}$/'],
        ], self::MENSAJES);

        return [
            'nombre' => trim($datos['nombre']),
            'url' => $datos['url'] ?? null,
            'orden' => $datos['orden'] ?: MarcaAliada::siguienteOrden(),
            'visible' => $request->boolean('visible', true),
        ];
    }

    // ---------------------------------------------------------------- Sección Nosotros

    public function seccionNosotros(): View
    {
        return view('backend.home.seccion-nosotros', ['seccion' => HomeNosotros::actual()]);
    }

    public function actualizarSeccionNosotros(Request $request): RedirectResponse
    {
        $request->merge(['boton_url' => $this->conProtocolo($request->input('boton_url'), permitirRuta: true)]);
        $datos = $request->validate([
            'imagen' => ['nullable', ...self::IMAGEN],
            'titulo' => ['required', 'string', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:20000'],
            'boton_texto' => ['nullable', 'string', 'max:40', 'required_with:boton_url'],
            'boton_url' => ['nullable', 'string', 'max:500', 'required_with:boton_texto', function (string $campo, mixed $valor, \Closure $falla): void {
                if ($valor !== null && ! str_starts_with($valor, '/') && filter_var($valor, FILTER_VALIDATE_URL) === false) {
                    $falla('El link del botón tiene que ser una página del sitio (por ejemplo /nosotros) o una dirección completa (https://…).');
                }
            }],
        ], self::MENSAJES);

        $seccion = HomeNosotros::query()->firstOrNew();
        $seccion->fill([
            'titulo' => trim($datos['titulo']),
            'descripcion' => $datos['descripcion'] ?? null,
            'boton_texto' => filled($datos['boton_texto'] ?? null) ? trim($datos['boton_texto']) : null,
            'boton_url' => $datos['boton_url'] ?? null,
        ]);
        if ($request->hasFile('imagen')) {
            $anterior = $seccion->imagenSubida() ? $seccion->imagen : null;
            $seccion->imagen = $this->subir($request->file('imagen'), 'home-nosotros_');
            $this->borrar($anterior);
        } elseif ($request->boolean('quitar_imagen')) {
            $this->borrar($seccion->imagenSubida() ? $seccion->imagen : null);
            $seccion->imagen = null;
        }
        $seccion->save();

        return redirect()->route('dashboard.home.seccion-nosotros')->with('success', 'Se guardó la sección Nosotros de la Home.');
    }

    // ---------------------------------------------------------------- Archivos

    /** Guarda el archivo en public/imagenes con un nombre propio y devuelve la ruta desde la raíz web. */
    private function subir(UploadedFile $archivo, string $prefijo): string
    {
        $nombre = $prefijo.bin2hex(random_bytes(6)).'.'.strtolower($archivo->extension() ?: $archivo->getClientOriginalExtension());
        $archivo->move(public_path(MarcaAliada::CARPETA), $nombre);

        return MarcaAliada::CARPETA.'/'.$nombre;
    }

    /** Borra un archivo subido desde estas pantallas (nunca uno de fábrica ni de otra sección). */
    private function borrar(?string $ruta): void
    {
        if ($ruta !== null && preg_match('#^imagenes/(marca_|home-nosotros_)[a-f0-9]{12}\.[a-z0-9]+$#', $ruta) === 1) {
            File::delete(public_path($ruta));
        }
    }

    /** «www.empresa.com» → «https://www.empresa.com»; vacío → null. Con $permitirRuta, «/nosotros» queda igual. */
    private function conProtocolo(mixed $valor, bool $permitirRuta = false): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }
        if ($permitirRuta && str_starts_with($valor, '/')) {
            return $valor;
        }

        return preg_match('#^[a-z][a-z0-9+.-]*://#i', $valor) ? $valor : 'https://'.$valor;
    }
}
