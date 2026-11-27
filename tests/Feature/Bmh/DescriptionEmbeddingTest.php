<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Search\CatalogDescriptionEmbeddingIndex;
use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\VisionMatchService;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\ImageAnalysis;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Búsqueda visual por DESCRIPCIÓN (embeddings).
 *
 * Caso real (IMPO1426): una ficha de 2 pines que el rubro resolvía como
 * "regulador" no aparecía. Por embedding de su descripción entra igual, y la
 * comparación visual la confirma. No es un match duro: sólo la hace aparecer.
 */
final class DescriptionEmbeddingTest extends TestCase
{
    private string $indexPath;

    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->indexPath = storage_path('app/ai-private/catalog-description-index.json');
        File::ensureDirectoryExists(dirname($this->indexPath));
        $this->backup = is_file($this->indexPath) ? file_get_contents($this->indexPath) : null;
    }

    protected function tearDown(): void
    {
        $this->backup === null ? @unlink($this->indexPath) : file_put_contents($this->indexPath, $this->backup);

        parent::tearDown();
    }

    /** @param list<float> $queryVector */
    private function mockEmbed(array $queryVector): void
    {
        $provider = $this->createMock(AiProviderInterface::class);
        $provider->method('isAvailable')->willReturn(true);
        $provider->method('name')->willReturn('fake');
        $provider->method('embed')->willReturn($queryVector);

        config(['bmh.features.ai' => true, 'bmh.ai.provider' => 'fake']);
        app(AiProviderManager::class)->extend('fake', $provider);
    }

    /** @param list<array{product_id:int,vector:list<float>}> $entries */
    private function writeIndex(array $entries): void
    {
        file_put_contents($this->indexPath, json_encode([
            'version' => 1,
            'dimensions' => 256,
            'generated_at' => now()->toIso8601String(),
            'entries' => $entries,
        ], JSON_THROW_ON_ERROR));
    }

    public function test_devuelve_el_producto_de_descripcion_mas_parecida(): void
    {
        $this->mockEmbed([1.0, 0.0, 0.0]);
        $this->writeIndex([
            ['product_id' => 4103, 'vector' => [0.98, 0.10, 0.0]], // el más parecido
            ['product_id' => 9991, 'vector' => [0.0, 1.0, 0.0]],
            ['product_id' => 9992, 'vector' => [0.0, 0.0, 1.0]],
        ]);

        $analysis = new ImageAnalysis(
            partType: 'ficha',
            confidence: 0.6,
            attributes: ['pins' => '2'],
            description: 'Ficha negra con dos pines y sello naranja',
            imageUsable: true,
        );

        $out = app(CatalogDescriptionEmbeddingIndex::class)->matches($analysis);

        $this->assertNotEmpty($out);
        $this->assertSame(4103, $out[0]['product_id']);
    }

    public function test_una_imagen_ilegible_no_consulta_el_indice(): void
    {
        $this->mockEmbed([1.0, 0.0, 0.0]);
        $this->writeIndex([['product_id' => 4103, 'vector' => [1.0, 0.0, 0.0]]]);

        $ilegible = new ImageAnalysis(partType: null, confidence: 0.0, imageUsable: false);

        $this->assertSame([], app(CatalogDescriptionEmbeddingIndex::class)->matches($ilegible));
    }

    public function test_el_producto_por_descripcion_entra_al_pool_aunque_el_rubro_no_lo_traiga(): void
    {
        // El producto 4103 (una ficha) sólo está en el índice de descripciones.
        // Aunque la foto se describa como "regulador" y el rubro no lo traiga,
        // entra al pool de candidatos por el parecido de su descripción.
        $this->mockEmbed([1.0, 0.0, 0.0]);
        $this->writeIndex([['product_id' => 4103, 'vector' => [1.0, 0.0, 0.0]]]);

        $analysis = new ImageAnalysis(
            partType: 'regulador',
            confidence: 0.6,
            attributes: ['pins' => '2'],
            description: 'Pieza negra con pines',
            imageUsable: true,
        );

        // provider = null: salteamos la comparación visual, miramos sólo el pool.
        $res = app(VisionMatchService::class)->match($analysis, new SearchQuery(), null, null);

        $ids = array_map(static fn ($c): int => $c->product->id, $res['candidates']);
        $this->assertContains(4103, $ids, 'el producto por descripción debe entrar al pool de candidatos');
    }
}
