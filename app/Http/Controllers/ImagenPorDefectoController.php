<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarImagenPorDefectoRequest;
use App\Models\Producto;
use App\Services\LogosSitio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Extras del admin: la imagen que muestran los productos sin portada.
 *
 * Si no se carga una propia, los productos usan el SVG con el logo del sitio
 * (ProductoController::imagen_predeterminada).
 */
class ImagenPorDefectoController extends Controller
{
    public function edit(LogosSitio $logos): View
    {
        $propia = $logos->urlSiExiste(LogosSitio::PRODUCTO_DEFECTO);

        // Un producto real sin portada, para que la vista previa muestre
        // exactamente lo que se ve en el catálogo.
        $ejemplo = Producto::query()
            ->whereDoesntHave('portadaImagen')
            ->whereNotNull('nombre')
            ->select(['id', 'codigo', 'nombre', 'marca', 'modelo'])
            ->first();

        return view('backend.extras.imagen-por-defecto', [
            'imagenActual' => $propia ?? Producto::imagenPredeterminadaBmhUrl(),
            'imagenBmh' => Producto::imagenPredeterminadaBmhUrl(),
            'usaPropia' => $propia !== null,
            'productosSinPortada' => Producto::query()->whereDoesntHave('portadaImagen')->count(),
            'ejemplo' => $ejemplo,
            'ladoMinimo' => ActualizarImagenPorDefectoRequest::LADO_MINIMO,
            'pesoMaximoKb' => ActualizarImagenPorDefectoRequest::PESO_MAXIMO_KB,
        ]);
    }

    public function update(ActualizarImagenPorDefectoRequest $request, LogosSitio $logos): RedirectResponse
    {
        $logos->reemplazar(LogosSitio::PRODUCTO_DEFECTO, $request->file('imagen'));

        return redirect()
            ->route('dashboard.extras.imagen-por-defecto')
            ->with('success', 'Imagen por defecto actualizada');
    }

    public function destroy(LogosSitio $logos): RedirectResponse
    {
        $logos->quitar(LogosSitio::PRODUCTO_DEFECTO);

        return redirect()
            ->route('dashboard.extras.imagen-por-defecto')
            ->with('success', 'Se volvió a la imagen de BMH');
    }
}
