<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Catalog\CatalogSemanticLayer;
use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DTO\ProductView;
use App\Models\Ai\CatalogSearchDocument;
use App\Models\Ai\CatalogSyncRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Materializa el índice de búsqueda desde la base legacy.
 *
 * Idempotente: cada documento lleva un hash del contenido normalizado, así que
 * una corrida sobre un catálogo sin cambios no reescribe nada. No se duplican
 * 5.000 productos en cada ejecución.
 *
 *     php artisan bmh:catalog-sync
 *     php artisan bmh:catalog-sync --fresh   (reconstruye todo)
 */
final class CatalogSyncCommand extends Command
{
    protected $signature = 'bmh:catalog-sync
                            {--fresh : Reconstruye el índice desde cero}
                            {--chunk= : Tamaño de lote}';

    protected $description = 'Sincroniza el índice de búsqueda del catálogo desde la base legacy de BMH';

    public function handle(CatalogRepositoryInterface $catalog): int
    {
        $started = microtime(true);
        $chunk   = (int) ($this->option('chunk') ?: config('bmh.search.sync_chunk', 500));

        $run = CatalogSyncRun::query()->create(['status' => 'running']);

        if ($this->option('fresh')) {
            $this->warn('Reconstruyendo el índice desde cero.');
            CatalogSearchDocument::query()->delete();
        }

        $stats = [
            'read'      => 0,
            'created'   => 0,
            'updated'   => 0,
            'unchanged' => 0,
            'anomalies' => [],
        ];

        $seenProductIds = [];

        $ids = DB::connection('mysql_legacy')->table('productos')->orderBy('id')->pluck('id');

        $this->info(sprintf('Leyendo %d productos de la base legacy…', $ids->count()));
        $bar = $this->output->createProgressBar($ids->count());
        $bar->start();

        foreach ($ids->chunk($chunk) as $batch) {
            $products = $catalog->findMany($batch->map(static fn ($id): int => (int) $id)->all());

            foreach ($products as $product) {
                $stats['read']++;
                $seenProductIds[] = $product->id;

                $this->collectAnomalies($product, $stats['anomalies']);

                $payload = $this->toDocument($product);
                $hash    = hash('sha256', $payload['searchable_text'] . '|' . ($payload['list_price'] ?? ''));

                $existing = CatalogSearchDocument::query()
                    ->where('product_id', $product->id)
                    ->first();

                if ($existing === null) {
                    CatalogSearchDocument::query()->create([...$payload, 'content_hash' => $hash]);
                    $stats['created']++;
                } elseif ($existing->content_hash !== $hash) {
                    $existing->update([...$payload, 'content_hash' => $hash]);
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }
            }

            $bar->advance($batch->count());
        }

        $bar->finish();
        $this->newLine(2);

        // Los productos que ya no están en la legacy salen del índice.
        $deleted = CatalogSearchDocument::query()
            ->whereNotIn('product_id', $seenProductIds)
            ->delete();

        $duration = (int) ((microtime(true) - $started) * 1000);

        $run->update([
            'status'            => 'completed',
            'products_read'     => $stats['read'],
            'documents_created' => $stats['created'],
            'documents_updated' => $stats['updated'],
            'documents_deleted' => $deleted,
            'anomalies'         => count($stats['anomalies']),
            'report'            => ['anomalies' => array_slice($stats['anomalies'], 0, 200)],
            'duration_ms'       => $duration,
        ]);

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Productos leídos', $stats['read']],
                ['Documentos creados', $stats['created']],
                ['Documentos actualizados', $stats['updated']],
                ['Sin cambios', $stats['unchanged']],
                ['Eliminados', $deleted],
                ['Anomalías detectadas', count($stats['anomalies'])],
                ['Duración', sprintf('%.1f s', $duration / 1000)],
            ]
        );

        if ($stats['anomalies'] !== []) {
            $this->newLine();
            $this->warn('Se detectaron anomalías. Detalle completo en `php artisan bmh:data-audit`.');

            foreach (array_slice($stats['anomalies'], 0, 5) as $anomaly) {
                $this->line('  · ' . $anomaly);
            }
        }

        return self::SUCCESS;
    }

    private function toDocument(ProductView $product): array
    {
        return [
            'product_id'      => $product->id,
            'code'            => mb_substr($product->code, 0, 128),
            'normalized_code' => mb_substr(CatalogSemanticLayer::normalizeCode($product->code), 0, 128),
            'name'            => mb_substr($product->name, 0, 512),
            'category_id'     => $product->category?->id,
            'category_name'   => $product->category?->name,
            'brand'           => $product->brand === null ? null : mb_substr($product->brand, 0, 255),
            'model'           => $product->model === null ? null : mb_substr($product->model, 0, 512),
            'condition'       => $product->condition->value,
            'list_price'      => $product->listPrice,
            'has_image'       => $product->images !== [],
            'duplicate_code'  => $product->hasDuplicateCode,
            'attributes'      => array_reduce(
                $product->attributes,
                static function (array $carry, $attribute): array {
                    $carry[$attribute->key] = $attribute->value;

                    return $carry;
                },
                []
            ),
            'equivalences'    => array_map(static fn ($r): string => $r->code, $product->equivalences),
            'searchable_text' => $product->searchableText,
        ];
    }

    /** @param list<string> $anomalies */
    private function collectAnomalies(ProductView $product, array &$anomalies): void
    {
        if ($product->listPrice === null || $product->listPrice <= 1.0) {
            $anomalies[] = sprintf('[%s] #%d sin precio real', $product->code, $product->id);
        }

        if ($product->images === []) {
            $anomalies[] = sprintf('[%s] #%d sin imagen disponible', $product->code, $product->id);
        }

        if ($product->category === null) {
            $anomalies[] = sprintf('[%s] #%d sin rubro asignado', $product->code, $product->id);
        }

        if ($product->attributes === [] && $product->brand === null) {
            $anomalies[] = sprintf('[%s] #%d sin atributos ni marca: no es discriminable', $product->code, $product->id);
        }
    }
}
