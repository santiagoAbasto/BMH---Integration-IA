<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactoMensaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → Contacto › Mensajes: la bandeja de las consultas de /contacto (como en Conelec, con admin-inbox.v1).
 * La lista queda a la izquierda y la consulta abierta (?mensaje={id}, que la marca como leída) a la derecha.
 * Los filtros (?filtro=no-leidos, ?q=) viajan en cada link y en cada redirección.
 */
final class ContactoMensajeController extends Controller
{
    private const POR_PAGINA = 20;

    public function index(Request $request): View
    {
        $filtros = $this->filtros($request);
        $abierto = null;

        if ($request->filled('mensaje')) {
            $abierto = ContactoMensaje::query()->find($request->integer('mensaje'));
            $abierto?->marcarLeido();
        }

        $mensajes = ContactoMensaje::query()
            ->when($filtros['filtro'] === 'no-leidos', fn ($q) => $q->noLeidos())
            ->buscar($filtros['q'])
            ->latest('created_at')->latest('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('backend.dash-contacto-mensajes', [
            'mensajes' => $mensajes,
            'abierto' => $abierto,
            'filtros' => $filtros,
            'total' => ContactoMensaje::query()->count(),
            'sinLeer' => ContactoMensaje::query()->noLeidos()->count(),
        ]);
    }

    /** Marca como leída o no leída (leido=0 vuelve a la bandeja: si quedara abierta se marcaría otra vez). */
    public function leido(Request $request, ContactoMensaje $mensaje): RedirectResponse
    {
        $leido = $request->boolean('leido');
        $mensaje->forceFill(['leido_at' => $leido ? ($mensaje->leido_at ?? now()) : null])->save();

        return redirect()->to($this->bandeja($request, $leido ? $mensaje->id : null))
            ->with('success', $leido ? 'Marcada como leída.' : 'Marcada como no leída.');
    }

    public function destroy(Request $request, ContactoMensaje $mensaje): RedirectResponse
    {
        $mensaje->delete();

        return redirect()->to($this->bandeja($request))->with('success', "Se eliminó la consulta de {$mensaje->nombre}.");
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'max:200'], 'ids.*' => ['integer']])['ids'];
        $borrados = ContactoMensaje::query()->whereKey($ids)->delete();

        return redirect()->to($this->bandeja($request))
            ->with('success', $borrados === 1 ? 'Se eliminó 1 consulta.' : "Se eliminaron {$borrados} consultas.");
    }

    /** @return array{filtro: string|null, q: string} */
    private function filtros(Request $request): array
    {
        return [
            'filtro' => $request->query('filtro') === 'no-leidos' ? 'no-leidos' : null,
            'q' => mb_substr(trim((string) $request->query('q', $request->input('q', ''))), 0, 120),
        ];
    }

    private function bandeja(Request $request, ?int $abierto = null): string
    {
        $filtros = array_filter([
            'filtro' => $request->input('filtro') === 'no-leidos' ? 'no-leidos' : null,
            'q' => mb_substr(trim((string) $request->input('q', '')), 0, 120) ?: null,
            'mensaje' => $abierto,
        ]);

        return route('dashboard.contacto.mensajes', $filtros);
    }
}
