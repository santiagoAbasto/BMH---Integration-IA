<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Search\CatalogDescriptionEmbeddingIndex;
use Illuminate\Console\Command;

/**
 * Construye el índice de descripciones (búsqueda visual por embeddings).
 *
 * OJO: describe cada imagen con la API de visión (una llamada por producto),
 * así que tiene costo y tarda. Se puede acotar a un rubro con --category para
 * empezar por el problema (p. ej. 57 = fichas) y controlar el gasto.
 */
final class IndexCatalogDescriptionsCommand extends Command
{
    protected $signature = 'bmh:index-catalog-descriptions {--category= : Acotar a un rubro (categoria_id) para controlar costo}';

    protected $description = 'Describe las imágenes del catálogo y guarda el embedding de cada descripción';

    public function handle(CatalogDescriptionEmbeddingIndex $index): int
    {
        $category = $this->option('category');
        $categoryId = $category === null ? null : (int) $category;

        $this->warn('Describe cada imagen con la API de visión: tiene costo y puede tardar.');
        $this->line('Indexando descripciones'.($categoryId !== null ? " del rubro {$categoryId}" : ' de todo el catálogo').'…');

        $result = $index->rebuild($categoryId, function (int $done): void {
            if ($done % 50 === 0) {
                $this->line("  {$done} descritas…");
            }
        });

        $this->table(
            ['Indexadas', 'Sin imagen', 'Sin descripción'],
            [[(string) $result['indexed'], (string) $result['missing'], (string) $result['skipped']]],
        );

        $this->line('Índice guardado en: '.$result['path']);

        if ($result['indexed'] === 0) {
            $this->error('No se indexó ninguna descripción. Verificá las imágenes del catálogo y la API de visión.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
