<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarBarraSuperiorRequest;
use App\Http\Requests\ActualizarFaviconRequest;
use App\Http\Requests\ActualizarFooterRequest;
use App\Http\Requests\ActualizarHeaderRequest;
use App\Models\Apariencia;
use App\Models\Contacto;
use App\Models\Imagen;
use App\Services\LogosSitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Extras del admin: logos y colores del header y el footer del sitio.
 *
 * Reemplaza a la sección de logos de /dashboard/logo.
 */
class AparienciaController extends Controller
{
    public function header(LogosSitio $logos): View
    {
        // El hero real de la home, para que la preview del header transparente
        // se vea sobre la imagen que el visitante va a ver de verdad.
        $hero = Imagen::query()->where('sector', 'home-slider')->orderBy('orden')->first();

        return view('backend.extras.header', [
            'apariencia' => Apariencia::actual(),
            'logoTransparente' => $logos->url(LogosSitio::HEADER_TRANSPARENTE),
            'logoBlanco' => $logos->url(LogosSitio::HEADER_BLANCO),
            'logoBlancoPropio' => $logos->tienePropio(LogosSitio::HEADER_BLANCO),
            'heroUrl' => $hero && $hero->tipo === 'imagen' ? asset('imagenes/'.$hero->path) : null,
            'contacto' => Contacto::query()->find(1),
        ]);
    }

    public function updateHeader(ActualizarHeaderRequest $request, LogosSitio $logos): RedirectResponse
    {
        $this->guardar($request->valores());

        if ($request->hasFile('logo_transparente')) {
            $logos->reemplazar(LogosSitio::HEADER_TRANSPARENTE, $request->file('logo_transparente'));
        }
        if ($request->hasFile('logo_blanco')) {
            $logos->reemplazar(LogosSitio::HEADER_BLANCO, $request->file('logo_blanco'));
        }

        return redirect()->route('dashboard.extras.header')->with('success', 'Header actualizado');
    }

    /** Estados del header que se pueden ver en la vista previa (?estado=…). */
    public const ESTADOS_HEADER = ['inicio', 'inicio-scroll', 'pagina', 'pagina-scroll', 'movil', 'movil-menu'];

    /**
     * Vista previa de los editores: el header o el footer reales del sitio (los mismos partials y el mismo CSS), para
     * mostrar en un iframe. El editor le pasa los colores y los logos que se están eligiendo antes de guardar.
     */
    public function vistaPrevia(Request $request, LogosSitio $logos, string $pieza): View
    {
        $estado = $pieza === 'header' && in_array($request->query('estado'), self::ESTADOS_HEADER, true)
            ? $request->query('estado')
            : 'inicio';
        $hero = Imagen::query()->where('sector', 'home-slider')->orderBy('orden')->first();

        return view('backend.extras.vista-previa', [
            'pieza' => $pieza,
            'estado' => $estado,
            'logosSitio' => $logos,
            'contacto' => Contacto::actual(),
            'heroUrl' => $hero && $hero->tipo === 'imagen' ? asset('imagenes/'.$hero->path) : null,
        ]);
    }

    public function footer(LogosSitio $logos): View
    {
        return view('backend.extras.footer', [
            'apariencia' => Apariencia::actual(),
            'logoFooter' => $logos->url(LogosSitio::FOOTER),
            'contacto' => Contacto::query()->find(1),
        ]);
    }

    public function barraSuperior(): View
    {
        return view('backend.extras.barra-superior', [
            'apariencia' => Apariencia::actual(),
            'contacto' => Contacto::actual(),
        ]);
    }

    public function updateBarraSuperior(ActualizarBarraSuperiorRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $this->guardar($request->colores());

            // Los mismos datos que edita la sección Contacto: una sola fila.
            $contacto = Contacto::query()->find(1) ?? tap(new Contacto(), function (Contacto $nuevo): void {
                $nuevo->id = 1;
                $nuevo->iframe = '';
            });

            foreach ($request->datosContacto() as $campo => $valor) {
                $contacto->{$campo} = $valor;
            }

            $contacto->save();
        });

        return redirect()->route('dashboard.extras.barra-superior')->with('success', 'Barra superior actualizada');
    }

    public function favicon(LogosSitio $logos): View
    {
        return view('backend.extras.favicon', [
            'apariencia' => Apariencia::actual(),
            'faviconUrl' => $logos->url(LogosSitio::FAVICON),
        ]);
    }

    public function updateFavicon(ActualizarFaviconRequest $request, LogosSitio $logos): RedirectResponse
    {
        $logos->reemplazar(LogosSitio::FAVICON, $request->file('favicon'));

        return redirect()->route('dashboard.extras.favicon')->with('success', 'Favicon actualizado');
    }

    public function updateFooter(ActualizarFooterRequest $request, LogosSitio $logos): RedirectResponse
    {
        $this->guardar($request->valores());

        if ($request->hasFile('logo')) {
            $logos->reemplazar(LogosSitio::FOOTER, $request->file('logo'));
        }

        return redirect()->route('dashboard.extras.footer')->with('success', 'Footer actualizado');
    }

    /** @param array<string, string> $valores */
    private function guardar(array $valores): void
    {
        // Sin pasar por Apariencia::actual(): esa puede venir del caché.
        $apariencia = Apariencia::query()->first() ?? new Apariencia(Apariencia::defaults());
        $apariencia->fill($valores)->save();
    }
}
