<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Catalog\Legacy\ProductImageService;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\DTO\ImageAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Índice de DESCRIPCIONES del catálogo para búsqueda visual por embeddings.
 *
 * El índice de huellas (CatalogImageFingerprintIndex) sólo reconoce la MISMA
 * imagen recomprimida: muere con una foto del monitor o de la pieza física.
 * Éste resuelve el otro caso: una foto describible de una pieza parecida. El
 * modelo describe la foto ("ficha negra de 2 pines, sello naranja") y se busca
 * la descripción más parecida del catálogo por coseno, SIN depender de que el
 * rubro se haya adivinado bien (una ficha de 2 pines caía en "reguladores" y se
 * perdía; por embedding aparece igual).
 *
 * No es un código: lo que sale de acá entra como candidato y lo confirma la
 * comparación visual y el cliente, nunca equivale a un match duro.
 *
 * Reusa `embed()` del proveedor (text-embedding-3-small): sin modelo nuevo, sin
 * servicio aparte. El vector se trunca a DIMS (Matryoshka) para que el índice
 * de ~5.200 productos sea chico y la consulta por fuerza bruta instantánea.
 *
 * ponytail: índice JSON + coseno por fuerza bruta; si la carga por consulta
 * pesa, pasar a binario empaquetado o a un índice vectorial.
 */
final class CatalogDescriptionEmbeddingIndex
{
    private const VERSION = 1;

    private const INDEX_PATH = 'app/ai-private/catalog-description-index.json';

    /** Matryoshka: las primeras DIMS de text-embedding-3 alcanzan para descripciones cortas. */
    private const DIMS = 256;

    public function __construct(
        private readonly ProductImageService $images,
        private readonly AiProviderManager $providers,
    ) {
    }

    /**
     * Describe cada imagen del catálogo (visión) y guarda el embedding de su
     * descripción. Costoso: una llamada de visión por imagen. Idempotente: se
     * puede acotar a un rubro para controlar costo y empezar por el problema.
     *
     * @return array{indexed:int,missing:int,skipped:int,path:string}
     */
    public function rebuild(?int $categoryId = null, ?callable $onProgress = null): array
    {
        $provider = $this->providers->primary();

        $images = DB::connection('mysql_legacy')->table('imagenes as i')
            ->join('productos as p', 'p.id', '=', 'i.producto_id')
            ->where('i.sector', 'producto')
            ->whereNotNull('i.producto_id')
            ->whereNotNull('i.path')
            ->where('p.estado', 1);

        if ($categoryId !== null) {
            $images->where('p.categoria_id', $categoryId);
        }

        $entries = [];
        $seen = [];
        $missing = 0;
        $skipped = 0;

        foreach ($images->orderBy('i.id')->select('i.producto_id', 'i.path')->cursor() as $row) {
            $productId = (int) $row->producto_id;

            if (isset($seen[$productId])) {
                continue;
            }

            $filename = $this->images->current(trim((string) $row->path));
            $abs = $filename === null ? null : $this->images->absolutePath($filename);

            if ($abs === null || ! is_file($abs)) {
                $missing++;

                continue;
            }

            $vector = $this->embedText($provider, self::textFor($provider->analyzeImage($abs, '')));

            if ($vector === null) {
                $skipped++;

                continue;
            }

            $seen[$productId] = true;
            $entries[] = ['product_id' => $productId, 'vector' => $vector];

            if ($onProgress !== null) {
                $onProgress(count($entries));
            }
        }

        $payload = json_encode([
            'version' => self::VERSION,
            'dimensions' => self::DIMS,
            'generated_at' => now()->toIso8601String(),
            'entries' => $entries,
        ], JSON_THROW_ON_ERROR);

        $path = $this->indexPath();
        File::ensureDirectoryExists(dirname($path));
        $temporary = $path.'.tmp';
        file_put_contents($temporary, $payload, LOCK_EX);
        rename($temporary, $path);

        return ['indexed' => count($entries), 'missing' => $missing, 'skipped' => $skipped, 'path' => $path];
    }

    /**
     * Los productos cuya descripción más se parece a la de la foto del cliente.
     *
     * @return list<array{product_id:int,similarity:float}>
     */
    public function matches(ImageAnalysis $analysis): array
    {
        if (! (bool) config('bmh.vision.catalog_description_index.enabled', true) || ! $analysis->imageUsable) {
            return [];
        }

        $text = self::textFor($analysis);

        if (mb_strlen($text) < 8) {
            return [];
        }

        $index = $this->read();

        if ($index === []) {
            return [];
        }

        $needle = $this->embedText($this->providers->primary(), $text);

        if ($needle === null) {
            return [];
        }

        $min = (float) config('bmh.vision.catalog_description_index.min_similarity', 0.55);
        $scored = [];

        foreach ($index as $entry) {
            $sim = self::cosine($needle, $entry['vector']);

            if ($sim >= $min) {
                $scored[$entry['product_id']] = max($scored[$entry['product_id']] ?? 0.0, $sim);
            }
        }

        arsort($scored);
        $limit = max(1, (int) config('bmh.vision.catalog_description_index.limit', 6));

        $out = [];

        foreach (array_slice($scored, 0, $limit, true) as $productId => $sim) {
            $out[] = ['product_id' => (int) $productId, 'similarity' => round($sim, 4)];
        }

        return $out;
    }

    /**
     * Texto embebible de un análisis: lo que se VE, sin el texto suelto de la
     * interfaz (que ensucia). Igual al indexar y al consultar, o no comparan.
     */
    public static function textFor(ImageAnalysis $analysis): string
    {
        $parts = [$analysis->partType, $analysis->description, $analysis->brandGuess];

        foreach ($analysis->attributes as $key => $value) {
            $parts[] = $key.' '.$value;
        }

        $parts = array_filter(array_map('trim', array_map('strval', $parts)), static fn (string $p): bool => $p !== '');

        return trim(implode('. ', $parts));
    }

    /** @return list<float>|null */
    private function embedText(object $provider, string $text): ?array
    {
        if (mb_strlen($text) < 8) {
            return null;
        }

        $vector = $provider->embed($text);

        if (! is_array($vector) || $vector === []) {
            return null;
        }

        // Matryoshka: las primeras DIMS de un embedding de text-embedding-3
        // conservan casi toda la señal y achican el índice.
        $vector = array_slice(array_map(static fn ($x): float => (float) $x, array_values($vector)), 0, self::DIMS);

        return $vector === [] ? null : $vector;
    }

    /** @return list<array{product_id:int,vector:list<float>}> */
    private function read(): array
    {
        $path = $this->indexPath();

        if (! is_file($path)) {
            return [];
        }

        try {
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('No se pudo leer el índice de descripciones.', ['error' => $e->getMessage()]);

            return [];
        }

        if (($payload['version'] ?? null) !== self::VERSION || ! is_array($payload['entries'] ?? null)) {
            return [];
        }

        $entries = [];

        foreach ($payload['entries'] as $entry) {
            if (is_array($entry) && is_int($entry['product_id'] ?? null) && is_array($entry['vector'] ?? null) && $entry['vector'] !== []) {
                $entries[] = ['product_id' => $entry['product_id'], 'vector' => array_map('floatval', $entry['vector'])];
            }
        }

        return $entries;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $n = min(count($a), count($b));

        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }

        return $na > 0.0 && $nb > 0.0 ? $dot / (sqrt($na) * sqrt($nb)) : 0.0;
    }

    private function indexPath(): string
    {
        return storage_path(self::INDEX_PATH);
    }
}
