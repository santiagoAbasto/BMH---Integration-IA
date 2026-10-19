<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ImagenBiblioteca;
use App\Services\Imagenes\BibliotecaImagenes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Admin → Productos → Imágenes: subir muchas a la vez, ver y renombrar el
 * código de cada una, y en qué productos se usa.
 */
class BibliotecaImagenesController extends Controller
{
    private const POR_PAGINA = 48;

    public function __construct(private readonly BibliotecaImagenes $biblioteca)
    {
    }

    public function index(Request $request): View
    {
        // Las imágenes que se cargaron desde la ficha de cada producto también tienen código.
        $this->biblioteca->sincronizar();

        $usos = $this->biblioteca->usos();
        $enUso = array_keys($usos);
        $filtro = in_array($request->query('filtro'), ['en-uso', 'sin-asignar'], true) ? $request->query('filtro') : 'todas';
        $orden = $request->query('orden') === 'codigo' ? 'codigo' : 'recientes';
        $busqueda = trim((string) $request->query('q'));

        $consulta = ImagenBiblioteca::query()
            ->where(function ($q) use ($busqueda, $usos): void {
                $q->buscar($busqueda);
                // También por código o nombre del producto que la usa.
                if ($busqueda !== '') {
                    $termino = mb_strtolower($busqueda);
                    $paths = [];
                    foreach ($usos as $path => $lista) {
                        foreach ($lista as $uso) {
                            if (str_contains(mb_strtolower($uso['codigo'].' '.$uso['nombre']), $termino)) {
                                $paths[] = $path;
                                break;
                            }
                        }
                    }
                    if ($paths !== []) {
                        $q->orWhereIn('path', $paths);
                    }
                }
            });

        $total = ImagenBiblioteca::query()->count();
        $totalEnUso = ImagenBiblioteca::query()->whereIn('path', $enUso)->count();
        $totales = ['todas' => $total, 'en-uso' => $totalEnUso, 'sin-asignar' => $total - $totalEnUso];

        if ($filtro === 'en-uso') {
            $consulta->whereIn('path', $enUso);
        } elseif ($filtro === 'sin-asignar') {
            $consulta->whereNotIn('path', $enUso);
        }
        $orden === 'codigo' ? $consulta->orderBy('codigo') : $consulta->orderByDesc('id');

        $imagenes = $consulta->paginate(self::POR_PAGINA)->withQueryString();

        return view('backend.biblioteca-imagenes', compact('imagenes', 'usos', 'filtro', 'orden', 'busqueda', 'totales'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'imagen' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ], [
            'imagen.mimes' => 'Tiene que ser una imagen JPG, PNG o WEBP.',
            'imagen.max' => 'Pesa más de 15 MB.',
            'imagen.required' => 'No llegó ninguna imagen.',
            'imagen.file' => 'No llegó ninguna imagen.',
        ]);

        ['imagen' => $imagen, 'motivo' => $motivo] = $this->biblioteca->subir($request->file('imagen'));

        return response()->json(['imagen' => $this->datos($imagen), 'motivo' => $motivo], 201);
    }

    public function update(Request $request, ImagenBiblioteca $imagen): JsonResponse
    {
        try {
            $this->biblioteca->renombrar($imagen, (string) $request->input('codigo'));
        } catch (RuntimeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['imagen' => $this->datos($imagen)]);
    }

    public function destroy(ImagenBiblioteca $imagen): JsonResponse
    {
        try {
            $this->biblioteca->eliminar($imagen);
        } catch (RuntimeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function datos(ImagenBiblioteca $imagen): array
    {
        return [
            'id' => $imagen->id,
            'codigo' => $imagen->codigo,
            'url' => $imagen->url,
            'nombre_original' => $imagen->nombre_original,
            'medidas' => $imagen->ancho && $imagen->alto ? "{$imagen->ancho} × {$imagen->alto} px" : null,
            'update_url' => route('dashboard.imagenes.update', $imagen),
            'destroy_url' => route('dashboard.imagenes.destroy', $imagen),
        ];
    }
}
