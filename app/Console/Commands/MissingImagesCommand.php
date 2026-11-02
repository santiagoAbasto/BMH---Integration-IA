<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lista los archivos de imagen que la base referencia pero no están en disco.
 *
 * El dump SQL trae las filas, no los binarios: en un checkout local siempre van
 * a faltar los que nunca se copiaron desde el servidor. En producción, en
 * cambio, un faltante significa que alguien borró el archivo sin limpiar la
 * fila.
 *
 *     php artisan bmh:missing-images
 *     php artisan bmh:missing-images --list      (rutas, una por línea)
 *     php artisan bmh:missing-images --rsync     (comando para traerlas)
 */
final class MissingImagesCommand extends Command
{
    protected $signature = 'bmh:missing-images
                            {--list : Sólo los nombres de archivo, uno por línea}
                            {--rsync : Imprime un rsync para traerlas del servidor}';

    protected $description = 'Detecta imágenes referenciadas en la base que faltan en public/imagenes';

    /** Columnas que guardan un nombre de archivo de public/imagenes. */
    private const SOURCES = [
        ['imagenes', 'path', 'Imágenes de producto y sitio'],
        ['categorias', 'portada', 'Portadas de rubro'],
        ['novedades', 'portada', 'Portadas de novedades'],
    ];

    public function handle(): int
    {
        $missing = [];
        $summary = [];

        foreach (self::SOURCES as [$table, $column, $label]) {
            if (! $this->tableExists($table)) {
                continue;
            }

            $paths = DB::connection('mysql_legacy')->table($table)
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->pluck($column)
                ->map(static fn ($p): string => trim((string) $p))
                ->filter()
                ->unique();

            $absent = $paths->reject(fn (string $p): bool => is_file(public_path('imagenes/' . $p)))->values();

            $summary[] = [$label, "{$table}.{$column}", $paths->count(), $absent->count()];

            foreach ($absent as $path) {
                $missing[$path] = true;
            }
        }

        $missing = array_keys($missing);
        sort($missing);

        if ($this->option('list')) {
            foreach ($missing as $path) {
                $this->line($path);
            }

            return $missing === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($this->option('rsync')) {
            $this->line('# Traer sólo los archivos faltantes desde el servidor de BMH.');
            $this->line('# Ajustá usuario, host y ruta remota.');
            $this->newLine();
            $this->line('php artisan bmh:missing-images --list > /tmp/faltantes.txt');
            $this->line('rsync -av --files-from=/tmp/faltantes.txt \\');
            $this->line('  usuario@bmhbobinajes.com.ar:/home/usuario/public_html/imagenes/ \\');
            $this->line('  ' . public_path('imagenes') . '/');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('<options=bold>BMH — Imágenes referenciadas que no están en disco</>');
        $this->newLine();

        $this->table(
            ['Origen', 'Columna', 'Referencias', 'Faltan'],
            $summary,
        );

        if ($missing === []) {
            $this->line('<fg=green;options=bold>No falta ninguna.</>');
            $this->newLine();

            return self::SUCCESS;
        }

        $this->line(sprintf('<fg=yellow;options=bold>%d archivos faltantes.</>', count($missing)));
        $this->newLine();

        foreach (array_slice($missing, 0, 10) as $path) {
            $this->line('  · ' . $path);
        }

        if (count($missing) > 10) {
            $this->line(sprintf('  … y %d más.', count($missing) - 10));
        }

        $this->newLine();
        $this->line('<fg=gray>El sitio no se rompe: las que faltan caen en un placeholder</>');
        $this->line('<fg=gray>(resources/views/components/image-fallback.blade.php).</>');
        $this->newLine();
        $this->line('Para traerlas del servidor:  <options=bold>php artisan bmh:missing-images --rsync</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function tableExists(string $table): bool
    {
        return DB::connection('mysql_legacy')->getSchemaBuilder()->hasTable($table);
    }
}
