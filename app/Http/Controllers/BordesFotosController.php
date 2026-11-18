<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Producto;
use App\Services\Imagenes\AjustesBordes;
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
            'accion' => ['required', 'in:emparejar,original,rehacer'],
            'despues_de' => ['nullable', 'string', 'max:255'],
        ]);
        $volver = $datos['accion'] === 'original';
        $rehacer = $datos['accion'] === 'rehacer';
        // Margen para la última foto de la tanda, si el servidor tiene un límite más corto (sin límite, no se toca).
        $limite = (int) ini_get('max_execution_time');
        if ($limite > 0 && $limite < self::SEGUNDOS_POR_TANDA * 4) {
            @set_time_limit(self::SEGUNDOS_POR_TANDA * 4);
        }

        // emparejar: las que no lo están; original: las emparejadas; rehacer: las emparejadas con otro margen.
        $pendientes = fn (): Builder => self::fotos()
            ->where('path', $volver || $rehacer ? 'like' : 'not like', self::EMPAREJADA)
            ->when($rehacer, fn ($q) => $q->where('path', 'not like', '%'.self::sufijoActual()));

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

    /** Las emparejadas, con cualquier margen: «x-parejo.webp», «x-parejo-12.webp». */
    private const EMPAREJADA = '%-parejo%.webp';

    private static function fotos(): Builder
    {
        return DB::table('imagenes')->where('sector', 'producto')->whereNotNull('path')->where('path', '!=', '');
    }

    /** Cómo termina el nombre de una foto emparejada con el margen elegido ahora. */
    private static function sufijoActual(): string
    {
        return substr((new EmparejarBordes())->nombrePara('x.png', EmparejarBordes::PRODUCTO), 1);
    }

    /** Cuántas fotos de productos hay, cuántas tienen los bordes emparejados y cuántas con otro margen (Biblioteca). */
    public static function resumen(): array
    {
        return [
            'total' => self::fotos()->distinct()->count('path'),
            'emparejadas' => self::fotos()->where('path', 'like', self::EMPAREJADA)->distinct()->count('path'),
            'con_otro_margen' => self::fotos()->where('path', 'like', self::EMPAREJADA)->where('path', 'not like', '%'.self::sufijoActual())->distinct()->count('path'),
            'margenes' => AjustesBordes::actuales(),
            'limites' => AjustesBordes::LIMITES,
            'defecto' => AjustesBordes::DEFECTO,
        ];
    }

    /** Biblioteca › margen de «Emparejar bordes» (productos y categorías). */
    public function ajustes(Request $request): RedirectResponse
    {
        [$pMin, $pMax] = AjustesBordes::LIMITES[AjustesBordes::PRODUCTO];
        [$cMin, $cMax] = AjustesBordes::LIMITES[AjustesBordes::CATEGORIA];
        $datos = $request->validate([
            'producto' => ['required', 'integer', "between:{$pMin},{$pMax}"],
            'categoria' => ['required', 'integer', "between:{$cMin},{$cMax}"],
        ], [
            'producto.between' => "El margen de las fotos de productos va de {$pMin}% a {$pMax}%.",
            'categoria.between' => "El margen de las fotos de categorías va de {$cMin}% a {$cMax}%.",
        ]);
        $antes = AjustesBordes::actuales();
        AjustesBordes::guardar(['producto' => (int) $datos['producto'], 'categoria' => (int) $datos['categoria']]);

        $mensaje = 'Margen guardado. Se usa en las fotos que se emparejen desde ahora.';
        if ($antes !== AjustesBordes::actuales()) {
            $mensaje .= ' Las que ya estaban emparejadas siguen con el margen anterior: «Rehacer con el margen nuevo» las actualiza.';
        }

        return redirect()->to(url()->previous().'#bordes')->with('success', $mensaje);
    }
}
