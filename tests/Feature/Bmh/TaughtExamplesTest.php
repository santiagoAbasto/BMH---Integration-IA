<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\Legacy\ProductImageService;
use App\Domain\Search\CandidateDisambiguationService;
use App\Domain\Search\CatalogImageFingerprintIndex;
use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\HybridProductSearchService;
use App\Domain\Search\VisionMatchService;
use App\Models\Ai\AiCorrectionRule;
use App\Services\Ai\ApprovedCorrectionService;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\ImageAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Caso real (2026-11-19, Alejandro): la foto de un estator (código 1836) dio
 * «ALTERNADOR UNIVERSAL BOSCH» como único candidato y el asesor preguntó la marca.
 *
 *  - El índice visual dio un falso positivo y la comparación visual dijo «ninguna
 *    coincide con claridad», pero el asesor igual lo devolvió: ahora el modelo veta.
 *  - Ante una foto sin marca visible se pregunta por medidas, no por marca.
 *  - Lo que el equipo enseña con una foto real sirve para OTRA foto de la misma
 *    pieza: entra como candidato y se compara con la foto nueva.
 */
final class TaughtExamplesTest extends TestCase
{
    private int $productId;

    private string $productCode;

    private int $categoryId;

    /** @var list<string> */
    private array $tmp = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2026_11_04_000001_add_correction_workflow_to_ai_feedback',
            '2026_11_05_000001_create_ai_correction_rules_table',
            '2026_11_16_000001_add_learning_to_ai_corrections_and_demo_accounts',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => "database/migrations/{$migration}.php", '--force' => true])->assertSuccessful();
        }

        DB::connection('mysql_ai')->table('ai_correction_rules')->delete();
        Storage::fake('ai_private');

        $product = app(CatalogRepositoryInterface::class)->findByCode('1836')[0];
        $this->productId = $product->id;
        $this->productCode = (string) $product->code;
        $this->categoryId = (int) $product->category?->id;
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $file) {
            @unlink($file);
        }

        DB::connection('mysql_ai')->table('ai_correction_rules')->delete();

        parent::tearDown();
    }

    private function save(\GdImage $im): string
    {
        $path = tempnam(sys_get_temp_dir(), 'te').'.png';
        imagepng($im, $path);

        return $this->tmp[] = $path;
    }

    /** Una foto «real» sintética: cobre sobre gris, distinta cada vez que cambia $seed. */
    private function photo(int $seed): string
    {
        $im = imagecreatetruecolor(500, 400);
        imagefill($im, 0, 0, imagecolorallocate($im, 200, 205, 210));
        $copper = imagecolorallocate($im, 190, 100 + $seed, 50);
        imagefilledellipse($im, 250, 220, 330, 200 + $seed, $copper);
        imagefilledellipse($im, 250, 220, 160, 90, imagecolorallocate($im, 200, 205, 210));

        return $this->save($im);
    }

    private function stator(): ImageAnalysis
    {
        return new ImageAnalysis(
            partType: 'estator',
            confidence: 0.95,
            categoryHints: ['estatores'],
            textConfidence: 1.0,
        );
    }

    /** @param array<string,mixed> $result */
    private function provider(array $result): AiProviderInterface
    {
        $provider = $this->createMock(AiProviderInterface::class);
        $provider->method('compareImages')->willReturn($result);

        return $provider;
    }

    public function test_la_comparacion_visual_veta_un_parecido_de_huella_que_no_es(): void
    {
        $index = app(CatalogImageFingerprintIndex::class);

        if (! is_file(storage_path('app/ai-private/catalog-image-index.json'))) {
            $this->markTestSkipped('Falta construir el índice visual (php artisan bmh:index-catalog-images).');
        }

        // Una foto del catálogo, recortada hasta quedar cerca pero no idéntica.
        $images = app(ProductImageService::class);
        $fotos = $images->forProducts([$this->productId])[$this->productId] ?? [];
        $this->assertNotEmpty($fotos, 'el producto 1836 debe tener foto en disco');
        $source = @imagecreatefromstring((string) file_get_contents($images->absolutePath($fotos[0])));
        $this->assertNotFalse($source);

        $cercana = null;

        foreach ([4, 6, 8, 10, 12, 15, 18, 22] as $pct) {
            $w = imagesx($source);
            $h = imagesy($source);
            $mx = (int) ($w * $pct / 100);
            $my = (int) ($h * $pct / 100);
            $crop = imagecrop($source, ['x' => $mx, 'y' => $my, 'width' => $w - 2 * $mx, 'height' => $h - 2 * $my]);
            $path = $this->save($crop);

            foreach ($index->matches($path) as $match) {
                if ($match['product_id'] === $this->productId && $match['distance'] >= 5) {
                    $cercana = $path;
                    break 2;
                }
            }
        }

        if ($cercana === null) {
            $this->markTestSkipped('No se encontró un recorte con huella cercana pero no idéntica.');
        }

        $vision = app(VisionMatchService::class);

        // El modelo vio las fotos y dice que ninguna coincide con claridad: se descarta.
        $vetado = $vision->match(
            $this->stator(),
            new SearchQuery(),
            $this->provider(['best_match' => null, 'similarity' => 0.0, 'ranking' => [], 'reason' => 'ninguna se parece']),
            $cercana,
        );

        $this->assertStringStartsNotWith('catalog_image_index', $vetado['strategy']);
        $this->assertGreaterThan(1, count($vetado['candidates']), 'sigue con la búsqueda por rubro');

        // Si el modelo confirma, el índice se respeta.
        $confirmado = $vision->match(
            $this->stator(),
            new SearchQuery(),
            $this->provider(['best_match' => 0, 'similarity' => 0.95, 'ranking' => [['ref' => 0, 'similarity' => 0.95]], 'reason' => 'misma pieza']),
            $cercana,
        );

        $this->assertSame('catalog_image_index+vision_compared', $confirmado['strategy']);
        $this->assertSame($this->productCode, $confirmado['candidates'][0]->product->code);
    }

    public function test_ante_una_foto_sin_marca_se_pregunta_por_medidas_no_por_marca(): void
    {
        $candidatos = app(HybridProductSearchService::class)->search(new SearchQuery(categoryIds: [$this->categoryId]), true);
        $this->assertGreaterThan(3, count($candidatos));

        $pregunta = app(CandidateDisambiguationService::class)->nextQuestion($candidatos, true);

        $this->assertTrue($pregunta['should_ask']);
        $this->assertNotContains($pregunta['attribute']['key'], ['brand', 'model']);
    }

    public function test_una_foto_ensenada_sirve_de_ejemplo_para_otra_foto_de_la_misma_pieza(): void
    {
        // Se prueba sólo el camino de los ejemplos: una imagen sintética lisa se
        // parece por casualidad a placas del catálogo y entraría por el índice.
        config(['bmh.vision.catalog_image_index.enabled' => false]);

        $service = app(ApprovedCorrectionService::class);

        // Alejandro enseña: «esta foto es el 1836».
        $ensenada = $this->photo(0);
        $rule = $service->remember($service->fingerprintFile($ensenada), $this->productId, 'admin_teach', null, 1, null, $ensenada);
        $this->assertNotNull($rule);
        Storage::disk('ai_private')->assertExists($rule->reference_path);

        $ejemplos = $service->taughtExamples([$this->categoryId]);
        $this->assertSame($this->productId, $ejemplos[0]['product']->id);
        $this->assertSame([], $service->taughtExamples([999999]), 'otro rubro: nada');

        // Otra foto de la misma pieza (otro ángulo): no coincide byte a byte ni por huella.
        $nueva = $this->photo(60);
        $this->assertFalse($service->apply('', [], [])['applied']);

        // El modelo compara con la foto enseñada (va primero, ref 0) y dice que es la misma.
        $resultado = app(VisionMatchService::class)->match(
            $this->stator(),
            new SearchQuery(),
            $this->provider(['best_match' => 0, 'similarity' => 0.9, 'ranking' => [['ref' => 0, 'similarity' => 0.9]], 'reason' => 'misma pieza']),
            $nueva,
        );

        $this->assertSame($this->productCode, $resultado['candidates'][0]->product->code);
        $this->assertContains($resultado['candidates'][0]->confidenceBand(), ['ambiguous', 'high']);

        // Una regla desactivada deja de aportar su foto.
        AiCorrectionRule::query()->update(['active' => false]);
        $this->assertSame([], $service->taughtExamples([$this->categoryId]));
    }
}
