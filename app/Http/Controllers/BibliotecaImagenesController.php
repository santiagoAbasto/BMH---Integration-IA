<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ImagenBiblioteca;
use App\Services\Imagenes\BibliotecaImagenes;
use App\Services\Imagenes\CodigoOcupado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Admin → Productos → Imágenes: subir muchas a la vez (el nombre del archivo
 * es el código y la foto se asigna sola al producto), ver y corregir el
 * código de cada una, y en qué productos se usa.
 */
class BibliotecaImagenesController extends Controller
{
    private const POR_PAGINA = 48;

    /** Al elegir el lugar de una foto: reemplazar una (objetivo = su path) o agregarla en una posición. */
    private const REGLAS_LUGAR = [
        'modo' => ['nullable', 'in:reemplazar,agregar'],
        'objetivo' => ['nullable', 'string', 'max:255'],
        'posicion' => ['nullable', 'integer', 'min:1', 'max:99'],
    ];

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

        // Sin asignar y con un código que no es de ningún producto: se marcan para corregirlos.
        $sinProducto = $imagenes->getCollection()
            ->filter(fn (ImagenBiblioteca $i) => ! isset($usos[mb_strtolower($i->path)]) && $this->biblioteca->destinoDe($i->codigo) === null)
            ->mapWithKeys(fn (ImagenBiblioteca $i) => [$i->id => true])
            ->all();

        return view('backend.biblioteca-imagenes', compact('imagenes', 'usos', 'filtro', 'orden', 'busqueda', 'totales', 'sinProducto'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'imagen' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            ...self::REGLAS_LUGAR,
        ], [
            'imagen.mimes' => 'Tiene que ser una imagen JPG, PNG o WEBP.',
            'imagen.max' => 'Pesa más de 15 MB.',
            'imagen.required' => 'No llegó ninguna imagen.',
            'imagen.file' => 'No llegó ninguna imagen.',
        ]);

        try {
            $subida = $this->biblioteca->subir($request->file('imagen'), $request->input('modo'), $request->input('objetivo'), $request->integer('posicion') ?: null);
        } catch (RuntimeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        if ($subida['resultado'] === BibliotecaImagenes::CONFLICTO) {
            // No se guardó nada: el navegador conserva el archivo y pregunta qué hacer.
            $existente = $subida['existente'];

            return response()->json([
                'resultado' => $subida['resultado'],
                'mensaje' => "Ya hay una foto con el código {$existente->codigo} ".$this->dondeSeUsa($existente).'.',
                'existente' => $this->datos($existente),
            ] + $this->lugares($subida['destino'], $existente->path), 409);
        }

        $imagen = $subida['imagen'];
        $destino = $subida['destino'];
        $mensaje = match ($subida['resultado']) {
            BibliotecaImagenes::SIN_PRODUCTO => "No hay ningún producto con el código {$imagen->codigo}. Corregilo y se asigna sola.",
            BibliotecaImagenes::REEMPLAZADA => $destino !== null
                ? 'Reemplazó a la '.self::foto($destino).'.'
                : "Reemplazó a la imagen anterior con el código {$imagen->codigo}.",
            default => 'Es la '.self::foto($destino).'.',
        };

        return response()->json([
            'resultado' => $subida['resultado'],
            'mensaje' => $mensaje,
            'imagen' => $this->datos($imagen),
            'productos' => $this->productos($destino),
            'donde' => $destino !== null ? ucfirst(self::foto($destino)) : null,
            'sugerencia' => $this->sugerencia($subida['sugerencia']),
        ], 201);
    }

    public function update(Request $request, ImagenBiblioteca $imagen): JsonResponse
    {
        $request->validate(self::REGLAS_LUGAR);

        try {
            $destino = $this->biblioteca->renombrar($imagen, (string) $request->input('codigo'), $request->input('modo'), $request->input('objetivo'), $request->integer('posicion') ?: null);
        } catch (CodigoOcupado $e) {
            // Sin asignar y con el código de otra foto del producto: se elige el lugar.
            $ocupante = ImagenBiblioteca::query()->where('path', $e->objetivo)->first();

            return response()->json([
                'resultado' => BibliotecaImagenes::CONFLICTO,
                'mensaje' => "El código {$e->codigo} ya lo tiene otra foto ".($ocupante ? $this->dondeSeUsa($ocupante) : 'del producto').'.',
                'codigo' => $e->codigo,
            ] + $this->lugares($e->destino, $e->objetivo), 409);
        } catch (RuntimeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        $enUso = $this->biblioteca->usosDe($imagen->path) !== [];

        return response()->json([
            'resultado' => $destino !== null || $enUso ? BibliotecaImagenes::ASIGNADA : BibliotecaImagenes::SIN_PRODUCTO,
            'mensaje' => match (true) {
                ($destino['reemplazo'] ?? false) => 'Reemplazó a la '.self::foto($destino).'.',
                $destino !== null => 'Es la '.self::foto($destino).'.',
                $enUso => 'Código cambiado.',
                default => "No hay ningún producto con el código {$imagen->codigo}.",
            },
            'imagen' => $this->datos($imagen),
            'productos' => $this->productos($destino),
            'donde' => $destino !== null ? ucfirst(self::foto($destino)) : null,
            'sugerencia' => $destino === null && ! $enUso ? $this->sugerencia($this->biblioteca->sugerencia($imagen->codigo)) : null,
        ]);
    }

    public function destroy(Request $request, ImagenBiblioteca $imagen): JsonResponse
    {
        try {
            $this->biblioteca->eliminar($imagen, $request->boolean('quitar_de_productos'));
        } catch (RuntimeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Las fotos del producto, para elegir cuál reemplazar o en qué lugar
     * poner la nueva; «objetivo» es la que tiene el código pedido.
     *
     * @return array{producto?: array{codigo: string, nombre: string}, fotos?: list<array{path: string, codigo: string|null, url: string, posicion: int}>, objetivo?: string}
     */
    private function lugares(?array $destino, string $objetivo): array
    {
        if ($destino === null) {
            return [];
        }
        $producto = $destino['productos'][0];

        return [
            'producto' => ['codigo' => $producto['codigo'], 'nombre' => $producto['nombre']],
            'fotos' => $this->biblioteca->fotosDe($producto['id']),
            'objetivo' => $objetivo,
        ];
    }

    /** «foto principal de NFSO42», «foto 2 de NFSO42 (y de 1 producto más con ese código)». */
    private static function foto(array $destino): string
    {
        $cual = $destino['posicion'] === 1 ? 'foto principal' : 'foto '.$destino['posicion'];
        $otros = count($destino['productos']) - 1;

        return "{$cual} de {$destino['productos'][0]['codigo']}"
            .($otros > 0 ? " (y de {$otros} producto".($otros === 1 ? '' : 's').' más con ese código)' : '');
    }

    /**
     * El producto que probablemente se quiso escribir y el código para usar:
     * el del producto, o su próxima foto libre si ya tiene principal.
     *
     * @return array{producto: string, codigo: string}|null
     */
    private function sugerencia(?string $producto): ?array
    {
        return $producto === null ? null : ['producto' => $producto, 'codigo' => $this->biblioteca->codigoLibre($producto)];
    }

    /** @return list<array{codigo: string, nombre: string, url: string}> */
    private function productos(?array $destino): array
    {
        return array_map(fn (array $p) => [
            'codigo' => $p['codigo'],
            'nombre' => $p['nombre'],
            'url' => route('producto.edit', ['id' => $p['id']]),
        ], $destino['productos'] ?? []);
    }

    private function dondeSeUsa(ImagenBiblioteca $imagen): string
    {
        $usos = $this->biblioteca->usosDe($imagen->path);
        if ($usos === []) {
            return '(no está en ningún producto)';
        }
        $primero = $usos[0];

        return '(es la '.($primero['rol'] === 'principal' ? 'foto principal' : 'otra foto').' de '.$primero['codigo'].')';
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
