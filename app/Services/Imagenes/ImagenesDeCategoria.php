<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use App\Models\ImagenBiblioteca;
use App\Models\Producto;
use Illuminate\Support\Facades\Schema;

/**
 * Qué archivos van en el ZIP de imágenes de una categoría y con qué nombre.
 *
 * Cada imagen se llama como su código de la biblioteca (NFSO42.webp,
 * NFSO42-2.webp): el mismo que se escribe en la columna Imágenes del Excel.
 * Si una todavía no tiene código, como antes: CODIGO.webp si el producto
 * tiene una, CODIGO-1, CODIGO-2… si tiene varias (primero la portada). Si
 * dos productos comparten código, el segundo lleva su id para no pisarse.
 */
final class ImagenesDeCategoria
{
    public function __construct(private readonly ?string $directorio = null)
    {
    }

    /**
     * @return array{archivos: list<array{string, string}>, sinImagenes: list<string>}
     *               archivos: [nombre dentro del ZIP, ruta en disco]
     */
    public function armar(int $categoriaId): array
    {
        $directorio = rtrim($this->directorio ?? (string) config('imagenes.directorio'), '/\\');
        $archivos = [];
        $sinImagenes = [];
        $usados = [];
        $enZip = [];
        $codigoPorPath = Schema::hasTable('biblioteca_imagenes')
            ? ImagenBiblioteca::query()->pluck('codigo', 'path')->mapWithKeys(fn ($c, $p) => [mb_strtolower((string) $p) => $c])->all()
            : [];

        $productos = Producto::query()
            ->where('categoria_id', $categoriaId)
            ->with('imagenesGaleria')
            ->orderBy('codigo')
            ->orderBy('id')
            ->get(['id', 'codigo', 'nombre']);

        foreach ($productos as $producto) {
            $rutas = $producto->imagenesGaleria
                ->map(fn ($imagen) => $directorio.DIRECTORY_SEPARATOR.$imagen->path)
                ->filter(fn ($ruta) => $ruta !== $directorio.DIRECTORY_SEPARATOR && is_file($ruta))
                ->unique()
                ->values();

            if ($rutas->isEmpty()) {
                $sinImagenes[] = trim(($producto->codigo ?: 'sin código').' — '.$producto->nombre);
                continue;
            }

            $base = $this->nombreBase($producto);
            if (isset($usados[$base])) {
                $base .= '_'.$producto->id;
            }
            $usados[$base] = true;

            foreach ($rutas as $i => $ruta) {
                $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
                $codigo = $codigoPorPath[mb_strtolower(basename($ruta))] ?? null;
                $nombre = $codigo !== null
                    ? "{$codigo}.{$extension}"
                    : ($rutas->count() === 1 ? "{$base}.{$extension}" : $base.'-'.($i + 1).".{$extension}");
                // Una foto que comparten dos productos de la categoría va una sola vez.
                if (isset($enZip[$nombre])) {
                    continue;
                }
                $enZip[$nombre] = true;
                $archivos[] = [$nombre, $ruta];
            }
        }

        return ['archivos' => $archivos, 'sinImagenes' => $sinImagenes];
    }

    /** Cantidad de imágenes que tendría el ZIP, para mostrarla en el botón. */
    public function contar(int $categoriaId): int
    {
        return count($this->armar($categoriaId)['archivos']);
    }

    /** El código, sin los caracteres que no valen en un nombre de archivo. */
    private function nombreBase(Producto $producto): string
    {
        $codigo = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/', '-', (string) $producto->codigo), " .-");

        return $codigo !== '' ? $codigo : 'producto-'.$producto->id;
    }
}
