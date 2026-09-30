<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Imagenes\OptimizadorImagenes;
use App\Services\Imagenes\ReferenciasImagenes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Pasa a WebP las imágenes ya cargadas que usa el sitio y borra el original.
 *
 * Seguro ante cortes y ediciones simultáneas: por cada archivo escribe primero
 * el WebP, actualiza todas las referencias en una transacción que verifica la
 * cantidad de filas, y recién después borra el original. Si se corta a la
 * mitad quedan los dos archivos, nunca una referencia rota; volver a correrlo
 * sigue desde donde quedó.
 *
 * Deja un registro JSONL (viejo → nuevo, bytes, calidad) para poder restaurar
 * una imagen puntual desde el backup. No hace backups: se hacen afuera antes
 * de correrlo, porque el servidor no tiene lugar para duplicar las imágenes.
 */
class OptimizarImagenesCommand extends Command
{
    protected $signature = 'imagenes:optimizar
        {--dry-run : Sólo informa qué haría y cuánto ahorraría; no toca nada}
        {--limite=0 : Cantidad máxima de imágenes a procesar (0 = todas)}
        {--pausa=40 : Milisegundos de pausa entre imágenes, para no cargar el servidor}
        {--directorio= : Carpeta de las imágenes (por defecto config imagenes.directorio)}';

    protected $description = 'Convierte a WebP las imágenes en uso, actualiza sus referencias y borra el original';

    public function handle(OptimizadorImagenes $optimizador, ReferenciasImagenes $referencias): int
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $directorio = rtrim((string) ($this->option('directorio') ?: config('imagenes.directorio')), '/\\');
        $dryRun = (bool) $this->option('dry-run');
        $limite = max(0, (int) $this->option('limite'));
        $pausa = max(0, (int) $this->option('pausa'));
        $ahorroMinimo = (float) config('imagenes.ahorro_minimo', 10);

        if (! is_dir($directorio)) {
            $this->error("No existe la carpeta {$directorio}");

            return self::FAILURE;
        }

        $this->info(($dryRun ? '[prueba] ' : '')."Buscando referencias en la base…");
        $columnas = $referencias->columnasConReferencias();
        $this->line('Columnas con imágenes: '.implode(', ', $columnas));

        $candidatos = $this->candidatos($directorio, $referencias->excluidasPorSector());
        $this->line(sprintf('Candidatos (PNG/JPG, sin excluidos): %d · %s', count($candidatos), $this->mb(array_sum($candidatos))));

        $registro = $dryRun ? null : $this->abrirRegistro();
        $totales = ['procesadas' => 0, 'optimizadas' => 0, 'antes' => 0, 'despues' => 0];
        $omitidas = [];
        $huerfanas = ['n' => 0, 'bytes' => 0];

        foreach ($candidatos as $nombre => $bytes) {
            if ($limite > 0 && $totales['procesadas'] >= $limite) {
                break;
            }

            $refs = $referencias->de($nombre, $columnas);
            if ($refs['embebida']) {
                $omitidas['referenciada dentro de un texto'][] = $nombre;
                continue;
            }
            if (array_sum($refs['exactas']) === 0) {
                $huerfanas['n']++;
                $huerfanas['bytes'] += $bytes;
                continue;
            }

            $totales['procesadas']++;
            $origen = $directorio.DIRECTORY_SEPARATOR.$nombre;
            $nuevoNombre = $this->nombreWebp($directorio, $nombre);
            $destino = $dryRun
                ? sys_get_temp_dir().DIRECTORY_SEPARATOR.'optim_'.uniqid().'.webp'
                : $directorio.DIRECTORY_SEPARATOR.$nuevoNombre;

            $resultado = $optimizador->optimizar($origen, $destino, $ahorroMinimo);

            if (! $resultado->optimizada) {
                $omitidas[$resultado->motivo][] = $nombre;
                $this->fila($totales['procesadas'], $nombre, $bytes, null, $resultado->motivo);
                continue;
            }

            if ($dryRun) {
                @unlink($destino);
            } else {
                try {
                    $this->reemplazarReferencias($nombre, $nuevoNombre, $refs['exactas']);
                } catch (Throwable $e) {
                    @unlink($destino);
                    $omitidas['no se pudo actualizar la base'][] = $nombre;
                    $this->fila($totales['procesadas'], $nombre, $bytes, null, $e->getMessage());
                    continue;
                }

                // Referencias ya apuntan al WebP: el original sobra.
                if (! @unlink($origen)) {
                    $this->warn("  no se pudo borrar {$nombre}; ya no lo usa nadie");
                }

                fwrite($registro, json_encode([
                    'viejo' => $nombre, 'nuevo' => $nuevoNombre,
                    'bytes_antes' => $resultado->bytesOriginales, 'bytes_despues' => $resultado->bytesNuevos,
                    'ancho' => $resultado->ancho, 'alto' => $resultado->alto,
                    'modo' => $resultado->modo, 'calidad' => $resultado->calidad,
                    'psnr' => $resultado->psnr === null ? null : round($resultado->psnr, 2),
                    'referencias' => array_filter($refs['exactas']),
                    'fecha' => now()->toDateTimeString(),
                ], JSON_UNESCAPED_UNICODE)."\n");
            }

            $totales['optimizadas']++;
            $totales['antes'] += $resultado->bytesOriginales;
            $totales['despues'] += $resultado->bytesNuevos;
            $this->fila($totales['procesadas'], $nombre, $bytes, $resultado->bytesNuevos, sprintf('%s%s', $resultado->modo === 'sin pérdida' ? 'sin pérdida' : 'q'.$resultado->calidad, $resultado->psnr ? ' · '.round($resultado->psnr, 1).' dB' : ''));

            if ($pausa > 0) {
                usleep($pausa * 1000);
            }
        }

        if ($registro) {
            fclose($registro);
        }

        $this->newLine();
        $this->info(sprintf('%sOptimizadas %d de %d · %s → %s (ahorro %s)',
            $dryRun ? '[prueba] ' : '', $totales['optimizadas'], $totales['procesadas'],
            $this->mb($totales['antes']), $this->mb($totales['despues']), $this->mb($totales['antes'] - $totales['despues'])));
        foreach ($omitidas as $motivo => $nombres) {
            $this->line(sprintf('  omitidas (%s): %d', $motivo, count($nombres)));
        }
        $this->line(sprintf('  sin referencias en la base (no se tocan): %d · %s', $huerfanas['n'], $this->mb($huerfanas['bytes'])));
        if ($this->rutaRegistro !== null) {
            $this->line('Registro: '.$this->rutaRegistro);
        }

        return self::SUCCESS;
    }

    private ?string $rutaRegistro = null;

    /** @return array<string, int> nombre => bytes, de mayor a menor */
    private function candidatos(string $directorio, array $excluidasPorSector): array
    {
        $excluidas = array_flip([...config('imagenes.excluidas', []), ...$excluidasPorSector]);
        $archivos = [];

        foreach (new \DirectoryIterator($directorio) as $archivo) {
            if (! $archivo->isFile()) {
                continue;
            }
            $nombre = $archivo->getFilename();
            if (isset($excluidas[$nombre]) || ! preg_match('/\.(png|jpe?g)$/i', $nombre)) {
                continue;
            }
            $archivos[$nombre] = $archivo->getSize();
        }
        arsort($archivos);

        return $archivos;
    }

    private function nombreWebp(string $directorio, string $nombre): string
    {
        $base = pathinfo($nombre, PATHINFO_FILENAME);
        $candidato = $base.'.webp';

        return is_file($directorio.DIRECTORY_SEPARATOR.$candidato) ? $base.'-'.uniqid().'.webp' : $candidato;
    }

    /**
     * @param  array<string, int>  $esperadas  "tabla.columna" => filas que lo referencian
     */
    private function reemplazarReferencias(string $viejo, string $nuevo, array $esperadas): void
    {
        DB::transaction(function () use ($viejo, $nuevo, $esperadas): void {
            foreach ($esperadas as $clave => $cantidad) {
                if ($cantidad === 0) {
                    continue;
                }
                [$tabla, $columna] = explode('.', $clave, 2);
                $cambiadas = DB::table($tabla)->where($columna, $viejo)->update([$columna => $nuevo]);
                if ($cambiadas !== $cantidad) {
                    throw new RuntimeException("{$clave}: se esperaban {$cantidad} filas y cambiaron {$cambiadas} (¿se editó mientras corría?)");
                }
            }
        });
    }

    /** @return resource */
    private function abrirRegistro()
    {
        $carpeta = storage_path('app/optimizacion-imagenes');
        if (! is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }
        $this->rutaRegistro = $carpeta.DIRECTORY_SEPARATOR.now()->format('Y-m-d_His').'.jsonl';

        return fopen($this->rutaRegistro, 'ab');
    }

    private function fila(int $n, string $nombre, int $antes, ?int $despues, string $detalle): void
    {
        $this->line(sprintf('%5d  %-28s %9s → %9s  %s', $n, $nombre, $this->kb($antes), $despues === null ? '—' : $this->kb($despues), $detalle));
    }

    private function kb(int $bytes): string
    {
        return number_format($bytes / 1024, 0, ',', '.').' KB';
    }

    private function mb(int $bytes): string
    {
        return number_format($bytes / 1048576, 1, ',', '.').' MB';
    }
}
