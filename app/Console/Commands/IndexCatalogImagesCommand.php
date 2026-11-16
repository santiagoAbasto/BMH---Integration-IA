<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Search\CatalogImageFingerprintIndex;
use Illuminate\Console\Command;

/** Construye la búsqueda visual estricta para imágenes ya presentes en BMH. */
final class IndexCatalogImagesCommand extends Command
{
    protected $signature = 'bmh:index-catalog-images';

    protected $description = 'Indexa las huellas visuales de las imágenes de productos que PHP puede leer';

    public function handle(CatalogImageFingerprintIndex $index): int
    {
        $this->line('Leyendo imágenes del catálogo…');
        $result = $index->rebuild();

        $this->table(
            ['Indexadas', 'No encontradas', 'No decodificables'],
            [[(string) $result['indexed'], (string) $result['missing'], (string) $result['invalid']]],
        );

        $this->line('Índice guardado en: '.$result['path']);

        if ($result['indexed'] === 0) {
            $this->error('No se indexó ninguna imagen. Verificá que BMH_CATALOG_IMAGES_PATH apunte al directorio real de imágenes.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
