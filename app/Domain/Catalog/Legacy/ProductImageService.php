<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Legacy;

use Illuminate\Support\Facades\DB;

/**
 * Imágenes del catálogo.
 *
 * `imagenes.path` guarda sólo el nombre de archivo; el binario vive en
 * `public/imagenes/`. En el checkout local hay menos archivos que registros,
 * así que se verifica existencia antes de ofrecer la URL: preferimos un
 * placeholder a una imagen rota en una card de producto.
 */
final class ProductImageService
{
    private const PUBLIC_DIR = 'imagenes';

    /** Cache de existencia por request; el filesystem no cambia en el medio. */
    private array $existsCache = [];

    /**
     * @param  list<int> $productIds
     * @return array<int, list<string>> producto_id => nombres de archivo verificados
     */
    public function forProducts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = DB::connection('mysql_legacy')->table('imagenes')
            ->where('sector', 'producto')
            ->whereIn('producto_id', $productIds)
            // `portada` primero: es la que va en la card.
            ->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")
            ->orderBy('orden')
            ->select('producto_id', 'path', 'tipo')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $path = trim((string) $row->path);

            if ($path === '' || ! $this->exists($path)) {
                continue;
            }

            $grouped[(int) $row->producto_id][] = $path;
        }

        return $grouped;
    }

    public function exists(string $filename): bool
    {
        return $this->existsCache[$filename] ??= is_file(public_path(self::PUBLIC_DIR . '/' . $filename));
    }

    public function url(string $filename): string
    {
        return asset(self::PUBLIC_DIR . '/' . $filename);
    }

    public function absolutePath(string $filename): string
    {
        return public_path(self::PUBLIC_DIR . '/' . $filename);
    }

    /**
     * Cuántas imágenes referenciadas no están en disco. Lo usa `bmh:data-audit`.
     *
     * @return array{referenced:int, missing:int, missing_examples:list<string>}
     */
    public function integrityReport(): array
    {
        $paths = DB::connection('mysql_legacy')->table('imagenes')
            ->where('sector', 'producto')
            ->pluck('path');

        $missing = [];

        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path !== '' && ! $this->exists($path)) {
                $missing[] = $path;
            }
        }

        return [
            'referenced'       => $paths->count(),
            'missing'          => count($missing),
            'missing_examples' => array_slice($missing, 0, 10),
        ];
    }
}
