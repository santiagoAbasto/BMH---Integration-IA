<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Panel\Notificaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/** Campana del panel: su estado para actualizarla sin recargar (resources/js/admin/campana.js). */
final class PanelNotificacionesController extends Controller
{
    public function __invoke(Notificaciones $notificaciones): JsonResponse
    {
        $avisos = $notificaciones->ultimas();
        $sinVer = $notificaciones->sinVer();

        return response()->json([
            'sin_ver' => $sinVer,
            'avisos' => $avisos->map(fn (array $a): array => ['key' => $a['key'], 'nombre' => $a['nombre'], 'etiqueta' => $a['etiqueta']])->all(),
            'html' => view('backend.partials.panel.notificaciones', ['avisos' => $avisos, 'sinVer' => $sinVer])->render(),
        ])->header('Cache-Control', 'no-store, private');
    }

    /** Un cliente nuevo desde la campana: queda revisado y se abre la lista de clientes (los más nuevos arriba). */
    public function revisarCliente(User $cliente): RedirectResponse
    {
        abort_unless($cliente->rol === 'cliente', 404);
        if ($cliente->revisado_at === null) {
            $cliente->forceFill(['revisado_at' => now()])->saveQuietly();
        }

        return redirect()->route('dashboard.clientes');
    }
}
