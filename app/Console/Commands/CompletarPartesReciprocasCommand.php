<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Catalogo\PartesReciprocas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Completa las partes relacionadas cargadas de un solo lado: si NFSO42 tiene
 * a 0078 y 0078 no tiene a NFSO42, se lo agrega al final de su lista.
 *
 * Usa el mismo servicio que el guardado del admin, sobre todos los productos
 * que tienen partes, en una sola transacción. Sólo agrega: no saca ninguna
 * relación. Se puede correr de nuevo sin efecto.
 */
class CompletarPartesReciprocasCommand extends Command
{
    protected $signature = 'partes:completar-reciprocas
        {--dry-run : Hace todo en una transacción y la deshace; informa qué agregaría}';

    protected $description = 'Agrega del otro lado las partes relacionadas cargadas de un solo lado';

    public function handle(PartesReciprocas $reciprocas): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $antes = DB::table('partes_relacionadas')->count();
        $deUnLado = $this->deUnSoloLado();

        $this->info(($dryRun ? '[prueba] ' : '')."Relaciones: {$antes} · de un solo lado: {$deUnLado}");
        if ($deUnLado === 0) {
            return self::SUCCESS;
        }

        // producto => sus partes, en el orden de su lista.
        $listas = DB::table('partes_relacionadas')
            ->orderBy('producto_id')
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['producto_id', 'parte_id'])
            ->groupBy('producto_id')
            ->map(fn ($filas) => $filas->pluck('parte_id')->map(fn ($id): int => (int) $id)->all());

        $desde = (int) DB::table('partes_relacionadas')->max('id');

        DB::beginTransaction();
        try {
            foreach ($listas as $productoId => $partes) {
                $reciprocas->sincronizar((int) $productoId, $partes, []);
            }

            $agregadas = DB::table('partes_relacionadas as pr')
                ->join('productos as p', 'p.id', '=', 'pr.producto_id')
                ->join('productos as q', 'q.id', '=', 'pr.parte_id')
                ->where('pr.id', '>', $desde)
                ->orderBy('p.codigo')
                ->get(['pr.id', 'p.codigo as producto', 'q.codigo as parte', 'pr.orden']);
            $quedan = $this->deUnSoloLado();

            if ($quedan !== 0) {
                throw new \RuntimeException("Quedaron {$quedan} relaciones de un solo lado; no se guarda nada.");
            }

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($agregadas->take(15) as $fila) {
            $this->line("  {$fila->producto} ahora tiene a {$fila->parte} (orden {$fila->orden})");
        }
        if ($agregadas->count() > 15) {
            $this->line('  … y '.($agregadas->count() - 15).' más');
        }

        $this->info(sprintf('%s%d relaciones agregadas · total %d → %d · de un solo lado: 0',
            $dryRun ? '[prueba, deshecho] ' : '', $agregadas->count(), $antes, $antes + $agregadas->count()));

        if (! $dryRun) {
            $ruta = storage_path('app/partes-reciprocas-'.now()->format('Y-m-d_His').'.json');
            file_put_contents($ruta, $agregadas->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line("Registro (para deshacer: borrar estos id): {$ruta}");
        }

        return self::SUCCESS;
    }

    /** Relaciones A → B sin su B → A. */
    private function deUnSoloLado(): int
    {
        return DB::table('partes_relacionadas as pr')
            ->whereColumn('pr.producto_id', '<>', 'pr.parte_id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('partes_relacionadas as r')
                ->whereColumn('r.producto_id', 'pr.parte_id')
                ->whereColumn('r.parte_id', 'pr.producto_id'))
            ->count();
    }
}
