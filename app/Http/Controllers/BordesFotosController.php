<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Producto;
use App\Services\Imagenes\EmparejarBordes;
use App\Services\Imagenes\FotosParejas;
use App\Services\Imagenes\NoSePuedeEmparejar;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * «Emparejar bordes» de las fotos de productos (y volver a las originales): las de un producto, una sola, o todas
 * (de a tandas, desde la Biblioteca de imágenes). Ver EmparejarBordes y FotosParejas.
 */
final class BordesFotosController extends Controller
{
    /** Tandas: las fotos se procesan de a varias por pedido, sin pasar estos segundos. */
    private const SEGUNDOS_POR_TANDA = 12;

    public function producto(Request $request, Producto $producto, FotosParejas $fotos): RedirectResponse
    {
        $volver = $request->input('accion') === 'original';
        $paths = Imagen::query()
            ->where('producto_id', $producto->id)->where('sector', 'producto')
            ->when($request->filled('imagen'), fn ($q) => $q->whereKey($request->integer('imagen')))
            ->pluck('path')->filter()->unique();

        $hechas = 0;
        $avisos = [];
        foreach ($paths as $path) {
            if ($volver !== EmparejarBordes::esPareja($path)) {
                continue; // ya está como se pidió
            }
            try {
                $volver ? $fotos->deshacer($path) : $fotos->emparejar($path, EmparejarBordes::PRODUCTO);
                $hechas++;
            } catch (NoSePuedeEmparejar $e) {
                $avisos[] = $e->getMessage();
            }
        }

        $mensaje = match (true) {
            $hechas === 0 && $avisos === [] => $volver ? 'Las fotos ya estaban como se subieron.' : 'Las fotos ya tenían los bordes emparejados.',
            $volver => $hechas === 1 ? 'La foto volvió a la original.' : "Las {$hechas} fotos volvieron a las originales.",
            default => ($hechas === 1 ? 'Se emparejaron los bordes de la foto.' : "Se emparejaron los bordes de {$hechas} fotos.").' Si no te gusta, «Volver a la original» la deja como estaba.',
        };
        $respuesta = redirect()->back()->with('success', $mensaje);

        return $avisos === [] ? $respuesta : $respuesta->with('warning', 'No se pudieron emparejar: '.implode(' ', array_unique($avisos)));
    }

    /**
     * Una tanda de todas las fotos de productos. La página la llama una y otra vez con «despues_de» (la última foto
     * de la tanda anterior) hasta que no quedan. Las que no se pueden emparejar se saltean y se informan.
     */
    public function lote(Request $request, FotosParejas $fotos): JsonResponse
    {
        $datos = $request->validate([
            'accion' => ['required', 'in:emparejar,original'],
            'despues_de' => ['nullable', 'string', 'max:255'],
        ]);
        $volver = $datos['accion'] === 'original';
        // Margen para la última foto de la tanda, si el servidor tiene un límite más corto (sin límite, no se toca).
        $limite = (int) ini_get('max_execution_time');
        if ($limite > 0 && $limite < self::SEGUNDOS_POR_TANDA * 4) {
            @set_time_limit(self::SEGUNDOS_POR_TANDA * 4);
        }

        $pendientes = fn (): Builder => DB::table('imagenes')
            ->where('sector', 'producto')->whereNotNull('path')->where('path', '!=', '')
            ->where('path', $volver ? 'like' : 'not like', '%'.EmparejarBordes::SUFIJO);

        $paths = $pendientes()
            ->when($datos['despues_de'] ?? null, fn ($q, $desde) => $q->where('path', '>', $desde))
            ->distinct()->orderBy('path')->limit($volver ? 200 : 60)->pluck('path');

        $inicio = microtime(true);
        $hechas = 0;
        $salteadas = [];
        $ultimo = $datos['despues_de'] ?? null;
        foreach ($paths as $path) {
            try {
                $volver ? $fotos->deshacer($path) : $fotos->emparejar($path, EmparejarBordes::PRODUCTO);
                $hechas++;
            } catch (NoSePuedeEmparejar $e) {
                $salteadas[] = ['foto' => $path, 'motivo' => $e->getMessage(), 'productos' => $this->codigosQueUsan($path)];
            }
            $ultimo = $path;
            if (microtime(true) - $inicio > self::SEGUNDOS_POR_TANDA) {
                break;
            }
        }

        $restantes = $ultimo === null ? 0 : $pendientes()->where('path', '>', $ultimo)->distinct()->count('path');

        return response()->json([
            'hechas' => $hechas,
            'salteadas' => $salteadas,
            'despues_de' => $ultimo,
            'restantes' => $restantes,
            'terminado' => $restantes === 0,
        ]);
    }

    /** @return list<string> códigos de los productos que usan la foto (para decir cuál no se pudo) */
    private function codigosQueUsan(string $path): array
    {
        return DB::table('imagenes')->join('productos', 'productos.id', '=', 'imagenes.producto_id')
            ->where('imagenes.sector', 'producto')->where('imagenes.path', $path)
            ->limit(5)->pluck('productos.codigo')->filter()->values()->all();
    }

    /** Cuántas fotos de productos hay y cuántas tienen los bordes emparejados (para la Biblioteca). */
    public static function resumen(): array
    {
        $base = fn () => DB::table('imagenes')->where('sector', 'producto')->whereNotNull('path')->where('path', '!=', '');

        return [
            'total' => $base()->distinct()->count('path'),
            'emparejadas' => $base()->where('path', 'like', '%'.EmparejarBordes::SUFIJO)->distinct()->count('path'),
        ];
    }
}
