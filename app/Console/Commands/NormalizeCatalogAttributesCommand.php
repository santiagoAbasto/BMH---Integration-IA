<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\LegacyAttributeMap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class NormalizeCatalogAttributesCommand extends Command
{
    protected $signature = 'bmh:normalize-catalog-attributes
        {--dry-run : Audita sin escribir}
        {--verify-only : Verifica una migración ya ejecutada}
        {--chunk=250 : Productos procesados por lote}';

    protected $description = 'Copia sin pérdida los 78 slots legacy al modelo normalizado de atributos';

    public function handle(): int
    {
        if (! Schema::hasTable('producto_atributo')) {
            $this->error('Falta ejecutar la migración que crea las tablas normalizadas.');
            return self::FAILURE;
        }

        $sourceCount = $this->sourceCount();
        $this->info("Valores legacy no vacíos detectados: {$sourceCount}");

        if (! $this->option('dry-run') && ! $this->option('verify-only')) {
            $this->seedAttributeDictionary();
            $this->seedCategoryApplicability();
            $this->copyValues((int) $this->option('chunk'));
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: no se escribió ningún dato.');
            return self::SUCCESS;
        }

        return $this->verify($sourceCount);
    }

    private function seedAttributeDictionary(): void
    {
        $now = now();
        $definitions = LegacyAttributeMap::all();

        for ($slot = 1; $slot <= LegacyAttributeMap::MAX_SLOT; $slot++) {
            $known = $definitions[$slot] ?? null;
            DB::table('atributos_producto')->updateOrInsert(
                ['legacy_slot' => $slot],
                [
                    'clave' => $known[0] ?? "legacy_slot_{$slot}",
                    'nombre' => $known[1] ?? "Slot legacy {$slot} (semántica pendiente)",
                    'tipo' => $known[2] ?? 'unknown',
                    'unidad' => $known[3] ?? null,
                    'semantica_confirmada' => $known !== null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedCategoryApplicability(): void
    {
        $attributeIds = DB::table('atributos_producto')->pluck('id', 'legacy_slot');
        $categoryIds = DB::table('categorias')->pluck('id');
        $now = now();

        foreach ($categoryIds as $categoryId) {
            $category = DB::table('categorias')->find($categoryId);
            foreach (LegacyAttributeMap::slots() as $slot) {
                $column = LegacyAttributeMap::categoryLabelColumn($slot);
                if (trim((string) ($category->{$column} ?? '')) === '') {
                    continue;
                }
                DB::table('atributo_producto_categoria')->insertOrIgnore([
                    'atributo_id' => $attributeIds[$slot],
                    'categoria_id' => $categoryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function copyValues(int $chunk): void
    {
        $attributeIds = DB::table('atributos_producto')->pluck('id', 'legacy_slot');
        $columns = ['id'];
        for ($slot = 1; $slot <= LegacyAttributeMap::MAX_SLOT; $slot++) {
            $columns[] = LegacyAttributeMap::column($slot);
        }

        DB::table('productos')->select($columns)->orderBy('id')->chunkById($chunk, function ($products) use ($attributeIds): void {
            $rows = [];
            $now = now();
            foreach ($products as $product) {
                for ($slot = 1; $slot <= LegacyAttributeMap::MAX_SLOT; $slot++) {
                    $value = $product->{LegacyAttributeMap::column($slot)};
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $rows[] = [
                        'producto_id' => $product->id,
                        'atributo_id' => $attributeIds[$slot],
                        'valor' => $value,
                        'origen' => 'legacy_slot',
                        'valor_hash' => hash('sha256', $value),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            foreach (array_chunk($rows, 500) as $batch) {
                DB::table('producto_atributo')->upsert(
                    $batch,
                    ['producto_id', 'atributo_id'],
                    ['valor', 'origen', 'valor_hash', 'updated_at']
                );
            }
        });
    }

    private function verify(int $expected): int
    {
        $actual = (int) DB::table('producto_atributo')->where('origen', 'legacy_slot')->count();
        $mismatches = 0;

        for ($slot = 1; $slot <= LegacyAttributeMap::MAX_SLOT; $slot++) {
            $column = LegacyAttributeMap::column($slot);
            $mismatches += (int) DB::table('productos as p')
                ->join('atributos_producto as a', 'a.legacy_slot', '=', DB::raw((string) $slot))
                ->leftJoin('producto_atributo as pa', function ($join): void {
                    $join->on('pa.producto_id', '=', 'p.id')->on('pa.atributo_id', '=', 'a.id');
                })
                ->whereNotNull("p.{$column}")
                ->where("p.{$column}", '<>', '')
                ->where(function ($query) use ($column): void {
                    $query->whereNull('pa.id')->orWhereRaw("BINARY pa.valor <> BINARY p.{$column}");
                })
                ->count();
        }

        $orphans = (int) DB::table('producto_atributo as pa')
            ->leftJoin('productos as p', 'p.id', '=', 'pa.producto_id')
            ->leftJoin('atributos_producto as a', 'a.id', '=', 'pa.atributo_id')
            ->where(fn ($q) => $q->whereNull('p.id')->orWhereNull('a.id'))
            ->count();

        $this->table(['Control', 'Resultado'], [
            ['Origen no vacío', $expected],
            ['Destino normalizado', $actual],
            ['Diferencias byte a byte', $mismatches],
            ['Relaciones huérfanas', $orphans],
        ]);

        if ($actual !== $expected || $mismatches !== 0 || $orphans !== 0) {
            throw new RuntimeException('La verificación clínica falló. Las columnas legacy permanecen intactas; revisar antes de continuar.');
        }

        $this->info('VERIFICACIÓN OK: todos los valores fueron copiados sin diferencias.');
        return self::SUCCESS;
    }

    private function sourceCount(): int
    {
        $total = 0;
        for ($slot = 1; $slot <= LegacyAttributeMap::MAX_SLOT; $slot++) {
            $column = LegacyAttributeMap::column($slot);
            $total += (int) DB::table('productos')->whereNotNull($column)->where($column, '<>', '')->count();
        }
        return $total;
    }
}
