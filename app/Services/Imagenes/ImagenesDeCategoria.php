<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use App\Models\Producto;

/**
 * Qué archivos van en el ZIP de imágenes de una categoría y con qué nombre.
 *
 * Cada imagen se llama como el código del producto: CODIGO.webp si tiene una,
 * CODIGO-1, CODIGO-2… si tiene varias (primero la portada, después en el
 * orden del admin). Si dos productos comparten código, el segundo lleva su id
 * para no pisarse.
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
                $nombre = $rutas->count() === 1 ? "{$base}.{$extension}" : $base.'-'.($i + 1).".{$extension}";
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
