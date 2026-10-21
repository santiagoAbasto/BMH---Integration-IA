<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaracteristicaRequest;
use App\Models\Caracteristica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaracteristicaController extends Controller
{
    /** Filas por página del listado del dashboard. */
    private const POR_PAGINA = 50;

    /**
     * Listado del ABM.
     *
     * Antes traía las 659 características de una y la vista dibujaba un modal
     * de edición por fila: ~1,7 MB de HTML. Ahora se pagina y se filtra en la
     * base, que es donde tiene que resolverse.
     */
    public function dash_caracteristicas(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        // Lista blanca: cualquier otro valor cae en el orden por defecto.
        $ordenar = (string) $request->query('ordenar', Caracteristica::ORDEN_CAMPO);
        if (! in_array($ordenar, Caracteristica::CRITERIOS_DE_ORDEN, true)) {
            $ordenar = Caracteristica::ORDEN_CAMPO;
        }

        $caracteristicas = Caracteristica::query()
            ->select(['id', 'orden', 'nombre'])
            ->buscar($buscar)
            ->ordenadoPor($ordenar)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('backend.dash-caracteristicas', compact('caracteristicas', 'buscar', 'ordenar'));
    }

    public function store(CaracteristicaRequest $request): RedirectResponse
    {
        $nombre = $request->nombre();

        if (Caracteristica::nombreOcupado($nombre)) {
            return $this->volver()->with('warning', 'Ya existe una característica con ese nombre');
        }

        Caracteristica::query()->create([
            'nombre' => $nombre,
            'orden' => $request->orden(),
        ]);

        return $this->volver()->with('success', 'Característica creada');
    }

    public function update(CaracteristicaRequest $request): RedirectResponse
    {
        $caracteristica = Caracteristica::query()->find($request->caracteristicaId());

        if ($caracteristica === null) {
            return $this->volver()->with('warning', 'La característica ya no existe');
        }

        $nombre = $request->nombre();

        if (Caracteristica::nombreOcupado($nombre, $caracteristica->id)) {
            return $this->volver()->with('warning', 'Ya existe una característica con ese nombre');
        }

        $caracteristica->update([
            'nombre' => $nombre,
            'orden' => $request->orden(),
        ]);

        return $this->volver()->with('success', 'Característica actualizada');
    }

    /**
     * Baja.
     *
     * El id llega por query string y puede no existir: doble clic sobre el
     * botón, reenvío del formulario al volver atrás, o dos pestañas borrando la
     * misma fila. Antes eso era `find(...)->delete()` sobre null y devolvía un
     * 500 —el error que reportó el cliente al borrar varias seguidas—; ahora se
     * responde con un aviso, que es lo que el usuario necesita ver.
     *
     * Las filas de `producto_caracteristica` y `categoria_caracteristica` las
     * arrastra el ON DELETE CASCADE de las foráneas.
     */
    public function delete(Request $request): RedirectResponse
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            return $this->volver()->with('warning', 'No se indicó qué característica borrar');
        }

        $caracteristica = Caracteristica::query()->find($id);

        if ($caracteristica === null) {
            return $this->volver()->with('warning', 'La característica ya no existe');
        }

        $caracteristica->delete();

        return $this->volver()->with('success', 'Característica eliminada');
    }

    /**
     * Vuelve al listado conservando página y búsqueda; si no hay referer
     * —un POST directo, o un navegador que no lo manda— cae en el listado.
     */
    private function volver(): RedirectResponse
    {
        return back(fallback: route('dashboard.caracteristicas'));
    }
}
