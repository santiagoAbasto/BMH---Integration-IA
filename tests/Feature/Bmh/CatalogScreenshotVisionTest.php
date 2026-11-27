<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Search\CatalogImageFingerprintIndex;
use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\VisionMatchService;
use App\Services\Ai\DTO\ImageAnalysis;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Ai\Support\ImageAnalysisSchema;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Un cliente sube una CAPTURA de la ficha del catálogo de BMH.
 *
 * Fallos reales que esto fija:
 *  - la imagen se reducía a 512 px y el texto de la página era ilegible: el
 *    modelo rellenaba con lo que suponía (medidas y rótulos que no existían);
 *  - la captura trae también los códigos de "productos relacionados", y buscar
 *    todos devolvía artículos que el cliente no pidió;
 *  - una captura se trataba como una foto mala ("falta una foto de la pieza").
 */
final class CatalogScreenshotVisionTest extends TestCase
{
    public function test_el_analisis_manda_la_imagen_con_resolucion_suficiente_para_leer_texto(): void
    {
        Http::fake(['*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['usable' => true, 'confidence' => 0.5])]]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ])]);

        $ruta = tempnam(sys_get_temp_dir(), 'shot').'.jpg';
        $lienzo = imagecreatetruecolor(1280, 1100);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 250, 250, 250));
        imagejpeg($lienzo, $ruta, 90);

        try {
            (new OpenAiProvider([
                'api_key' => 'sk-test',
                'base_url' => 'https://api.test/v1',
                'vision_model' => 'gpt-4o',
            ]))->analyzeImage($ruta);
        } finally {
            @unlink($ruta);
        }

        Http::assertSent(function (Request $request): bool {
            $imagen = $request['messages'][1]['content'][1]['image_url'];
            [$ancho] = getimagesizefromstring(base64_decode(explode(',', $imagen['url'], 2)[1]));

            // A 512 px el texto chico de una captura no se lee; 'auto' lo baja.
            return $ancho > 1000 && $imagen['detail'] === 'high';
        });
    }

    public function test_una_captura_con_codigo_legible_es_una_consulta_valida(): void
    {
        $analisis = ImageAnalysisSchema::hydrate([
            'usable' => false, // el modelo la tomó por una "foto insuficiente"
            'reason' => 'Falta una foto de la pieza real.',
            'image_kind' => 'catalog_screenshot',
            'catalog_code' => '1984',
            'detected_text' => ['1984', 'EST3430001'],
            'text_confidence' => 1,
        ]);

        $this->assertTrue($analisis->isCatalogScreenshot());
        $this->assertTrue($analisis->imageUsable);
        $this->assertSame('1984', $analisis->catalogCode);
        $this->assertSame(['1984'], $analisis->searchCodes());
    }

    public function test_catalog_code_solo_vale_en_una_captura(): void
    {
        $foto = ImageAnalysisSchema::hydrate([
            'usable' => true,
            'confidence' => 0.8,
            'image_kind' => 'part_photo',
            'catalog_code' => '1984',
            'visible_codes' => ['1833'],
            'text_confidence' => 0.9,
        ]);

        $this->assertNull($foto->catalogCode);
        $this->assertSame(['1833'], $foto->searchCodes());

        $desconocido = ImageAnalysisSchema::hydrate(['usable' => true, 'image_kind' => 'inventado']);

        $this->assertSame(ImageAnalysis::KIND_PART_PHOTO, $desconocido->imageKind);
    }

    public function test_la_captura_busca_solo_el_producto_principal_y_no_los_relacionados(): void
    {
        // 1833 es el producto de la ficha; 1984 sólo aparece como "relacionado".
        $analisis = new ImageAnalysis(
            partType: null,
            confidence: 0.0,
            detectedText: ['1833', '1984'],
            visibleCodes: ['1833', '1984'],
            textConfidence: 1.0,
            imageKind: ImageAnalysis::KIND_CATALOG_SCREENSHOT,
            catalogCode: '1833',
        );

        $resultado = app(VisionMatchService::class)->match($analisis, new SearchQuery());

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertSame(['1833'], $resultado['code_hits']);

        $codigos = array_map(static fn ($c): string => (string) $c->product->code, $resultado['candidates']);

        $this->assertSame(['1833'], $codigos);
        $this->assertStringContainsString('captura', implode(' ', $resultado['notes']));
    }

    public function test_sin_tipo_de_imagen_se_conserva_el_comportamiento_de_foto_de_pieza(): void
    {
        $foto = new ImageAnalysis(
            partType: 'rotor',
            confidence: 0.8,
            visibleCodes: ['1833', '1984'],
            textConfidence: 1.0,
        );

        $resultado = app(VisionMatchService::class)->match($foto, new SearchQuery());

        $this->assertEqualsCanonicalizing(['1833', '1984'], $resultado['code_hits']);
        $this->assertStringContainsString('pieza', implode(' ', $resultado['notes']));
    }

    /** Pieza asimétrica sobre fondo blanco, como una foto del catálogo. */
    private function piece(int $size, bool $other = false): \GdImage
    {
        $im = imagecreatetruecolor($size, $size);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        $brown = imagecolorallocate($im, 150, 110, 60);
        $grey = imagecolorallocate($im, 60, 60, 70);
        $u = $size / 10;

        if ($other) {
            imagefilledellipse($im, (int) (5 * $u), (int) (5 * $u), (int) (6 * $u), (int) (3 * $u), $grey);
            imagefilledrectangle($im, (int) (1 * $u), (int) (7 * $u), (int) (9 * $u), (int) (8 * $u), $brown);
        } else {
            imagefilledrectangle($im, (int) (2 * $u), (int) (3 * $u), (int) (8 * $u), (int) (7 * $u), $brown);
            imagefilledrectangle($im, (int) (2 * $u), (int) (1 * $u), (int) (4 * $u), (int) (3 * $u), $grey);
            imagefilledellipse($im, (int) (7 * $u), (int) (8.5 * $u), (int) (2 * $u), (int) (2 * $u), $grey);
        }

        return $im;
    }

    private function save(\GdImage $im): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fp').'.png';
        imagepng($im, $path);

        return $path;
    }

    public function test_la_huella_ignora_los_margenes_y_el_marco_de_una_captura(): void
    {
        $index = app(CatalogImageFingerprintIndex::class);

        // La foto del catálogo, y la misma foto dentro de una tarjeta web:
        // más chica, con margen blanco y un borde gris de 2 px (rgb 216).
        $catalogo = $this->save($this->piece(800));
        $tarjeta = imagecreatetruecolor(1206, 882);
        imagefill($tarjeta, 0, 0, imagecolorallocate($tarjeta, 255, 255, 255));
        imagecopyresampled($tarjeta, $this->piece(800), 300, 110, 0, 0, 560, 560, 800, 800);
        imagerectangle($tarjeta, 40, 18, 1165, 862, imagecolorallocate($tarjeta, 216, 216, 216));
        imagerectangle($tarjeta, 41, 19, 1164, 861, imagecolorallocate($tarjeta, 216, 216, 216));
        $captura = $this->save($tarjeta);
        $otra = $this->save($this->piece(800, other: true));

        $a = $index->fingerprints($catalogo);
        $b = $index->fingerprints($captura);
        $c = $index->fingerprints($otra);

        foreach ([$catalogo, $captura, $otra] as $file) {
            @unlink($file);
        }

        $distance = static fn (array $x, array $y): int => CatalogImageFingerprintIndex::hamming($x['average_hash'], $y['average_hash'])
            + CatalogImageFingerprintIndex::hamming($x['difference_hash'], $y['difference_hash']);

        // Misma foto: dentro del umbral del índice (8). Otra pieza: lejos.
        $this->assertLessThanOrEqual(8, $distance($a, $b), 'la captura con marco debe parecerse a la foto del catálogo');
        $this->assertGreaterThan(8, $distance($a, $c), 'otra pieza no puede confundirse');
    }

    public function test_el_titulo_impreso_identifica_un_producto_aunque_no_haya_codigo(): void
    {
        /*
         * Caso real: foto hecha a la pantalla, con el visor ampliado de la foto
         * del producto. No se lee el código, sólo el final del título y las
         * cotas dibujadas. El nombre da con un único producto (30032).
         */
        $foto = new ImageAnalysis(
            partType: 'alternador',
            confidence: 0.5,
            detectedText: ['PASSAT, AMAROK POLEA CRICKET BOSCH', '130 mm', '140 mm'],
            textConfidence: 1.0,
            imageKind: ImageAnalysis::KIND_CATALOG_SCREENSHOT,
            productText: 'PASSAT, AMAROK POLEA CRICKET BOSCH 130 mm 140 mm',
        );

        $resultado = app(VisionMatchService::class)->match($foto, new SearchQuery());

        $this->assertSame('vision_title', $resultado['strategy']);
        $this->assertSame('30032', $resultado['candidates'][0]->product->code);
        // Sin código no se afirma: coincidencia parcial que el cliente confirma.
        $this->assertSame('ambiguous', $resultado['candidates'][0]->confidenceBand());
    }

    public function test_un_titulo_generico_que_trae_medio_catalogo_no_identifica(): void
    {
        $foto = new ImageAnalysis(
            partType: 'alternador',
            confidence: 0.8,
            categoryHints: ['alternadores'],
            productText: 'ALTERNADOR BOSCH',
        );

        $resultado = app(VisionMatchService::class)->match($foto, new SearchQuery());

        $this->assertNotSame('vision_title', $resultado['strategy']);
    }

    public function test_el_titulo_leido_se_conserva_y_se_acota(): void
    {
        $analisis = ImageAnalysisSchema::hydrate([
            'usable' => false,
            'image_kind' => 'catalog_screenshot',
            'product_text' => str_repeat('ALTERNADOR ', 40),
        ]);

        $this->assertNotNull($analisis->productText);
        $this->assertLessThanOrEqual(160, mb_strlen($analisis->productText));
        $this->assertNull(ImageAnalysisSchema::hydrate(['usable' => true])->productText);
    }

    public function test_un_codigo_impreso_que_existe_manda_sobre_el_indice_visual(): void
    {
        /*
         * Caso real: captura de celular. La visión leyó bien el código (10173),
         * pero el índice visual corría antes, devolvía otro producto por
         * casualidad y se salteaba la lectura. Acá la imagen ES la foto del
         * producto 1779 en el catálogo, y la captura dice que es el 1833.
         */
        if (! is_file(storage_path('app/ai-private/catalog-image-index.json'))) {
            $this->markTestSkipped('Falta construir el índice visual (php artisan bmh:index-catalog-images).');
        }

        $id = (int) \Illuminate\Support\Facades\DB::connection('mysql_legacy')->table('productos')->where('codigo', '1779')->value('id');
        $fotos = app(\App\Domain\Catalog\Legacy\ProductImageService::class)->forProducts([$id])[$id] ?? [];
        $this->assertNotEmpty($fotos, 'el producto 1779 debe tener foto en disco');
        $ruta = app(\App\Domain\Catalog\Legacy\ProductImageService::class)->absolutePath($fotos[0]);

        $captura = new ImageAnalysis(
            partType: null,
            confidence: 0.0,
            visibleCodes: ['1833'],
            textConfidence: 1.0,
            imageKind: ImageAnalysis::KIND_CATALOG_SCREENSHOT,
            catalogCode: '1833',
        );

        $resultado = app(VisionMatchService::class)->match($captura, new SearchQuery(), null, $ruta);

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertSame('1833', $resultado['candidates'][0]->product->code);
    }

    public function test_el_ocr_que_confunde_i_con_1_igual_encuentra_el_codigo(): void
    {
        // El modelo leyó "POLI6066" (una I) donde el catálogo dice POL16066.
        $captura = new ImageAnalysis(
            partType: null,
            confidence: 0.0,
            visibleCodes: ['POLI6066'],
            textConfidence: 1.0,
            imageKind: ImageAnalysis::KIND_CATALOG_SCREENSHOT,
            catalogCode: 'POLI6066',
        );

        $resultado = app(VisionMatchService::class)->match($captura, new SearchQuery());

        $this->assertSame('vision_code', $resultado['strategy']);
        $this->assertSame('POL16066', $resultado['candidates'][0]->product->code);

        // Y cuando el OCR agrega una de más: "POLI16066" por POL16066.
        $sobra = new ImageAnalysis(
            partType: null,
            confidence: 0.0,
            visibleCodes: ['POLI16066'],
            textConfidence: 1.0,
            imageKind: ImageAnalysis::KIND_CATALOG_SCREENSHOT,
            catalogCode: 'POLI16066',
        );

        $this->assertSame(
            'POL16066',
            app(VisionMatchService::class)->match($sobra, new SearchQuery())['candidates'][0]->product->code,
        );

        // Un código con muchas letras ambiguas no se adivina ni rompe nada.
        $ruido = new ImageAnalysis(
            partType: null,
            confidence: 0.0,
            visibleCodes: ['OILOILOILO'],
            textConfidence: 1.0,
            imageKind: ImageAnalysis::KIND_CATALOG_SCREENSHOT,
            catalogCode: 'OILOILOILO',
        );

        $this->assertNotSame('vision_code', app(VisionMatchService::class)->match($ruido, new SearchQuery())['strategy']);
    }

    public function test_una_captura_ilegible_con_huella_floja_no_arriesga_un_producto(): void
    {
        // Bug real (impo1734): una foto del monitor que el modelo no puede leer
        // —ni código ni nombre— coincidía DE LEJOS con otra ficha, y la
        // comparación visual la "confirmaba" porque los conectores se ven casi
        // iguales. Con huella floja no se arriesga un producto: se pide una
        // captura legible. Una huella casi idéntica (captura real) sí matchea.
        $productId = (int) DB::connection('mysql_legacy')->table('productos')->where('estado', 1)->value('id');
        $this->assertGreaterThan(0, $productId, 'hace falta al menos un producto en el catálogo');

        $im = imagecreatetruecolor(400, 400);
        imagefill($im, 0, 0, imagecolorallocate($im, 120, 90, 60));
        imagefilledrectangle($im, 80, 80, 320, 300, imagecolorallocate($im, 30, 30, 40));
        $ruta = tempnam(sys_get_temp_dir(), 'cust').'.png';
        imagepng($im, $ruta);

        $index = app(CatalogImageFingerprintIndex::class);
        $fp = $index->fingerprints($ruta);
        $flip = static function (string $hash, int $bits): string {
            for ($i = 0; $i < $bits; $i++) {
                $hash[$i] = dechex(hexdec($hash[$i]) ^ 1);
            }

            return $hash;
        };

        $indexPath = storage_path('app/ai-private/catalog-image-index.json');
        File::ensureDirectoryExists(dirname($indexPath));
        $backup = is_file($indexPath) ? file_get_contents($indexPath) : null;

        $write = static function (string $avg, string $diff) use ($indexPath, $productId): void {
            file_put_contents($indexPath, json_encode([
                'version' => 2,
                'generated_at' => now()->toIso8601String(),
                'entries' => [[
                    'product_id' => $productId,
                    'path' => 'media_test.webp',
                    'average_hash' => $avg,
                    'difference_hash' => $diff,
                ]],
            ], JSON_THROW_ON_ERROR));
        };

        $ilegible = new ImageAnalysis(
            partType: null,
            confidence: 0.0,
            imageUsable: false,
            unusableReason: 'No se ve el código ni el nombre del producto.',
            imageKind: ImageAnalysis::KIND_CATALOG_SCREENSHOT,
        );

        try {
            // Huella FLOJA (distancia 8 de 128): no es casi idéntica -> se descarta.
            $write($flip($fp['average_hash'], 4), $flip($fp['difference_hash'], 4));
            $flojo = app(VisionMatchService::class)->match($ilegible, new SearchQuery(), null, $ruta);
            $this->assertSame('unusable_image', $flojo['strategy'], 'una huella floja sobre una imagen ilegible no debe devolver productos');
            $this->assertSame([], $flojo['candidates']);

            // Huella CASI IDÉNTICA (distancia 0): una captura real sí matchea.
            $write($fp['average_hash'], $fp['difference_hash']);
            $fuerte = app(VisionMatchService::class)->match($ilegible, new SearchQuery(), null, $ruta);
            $this->assertSame('catalog_image_index', $fuerte['strategy']);
            $this->assertNotEmpty($fuerte['candidates']);
        } finally {
            $backup === null ? @unlink($indexPath) : file_put_contents($indexPath, $backup);
            @unlink($ruta);
        }
    }

    public function test_la_firma_fina_separa_variantes_que_el_hash_no_distingue(): void
    {
        $index = app(CatalogImageFingerprintIndex::class);
        $forma = static function (int $size, array $rgb): \GdImage {
            $im = imagecreatetruecolor($size, $size);
            imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
            $color = imagecolorallocate($im, ...$rgb);
            imagefilledrectangle($im, (int) ($size * .2), (int) ($size * .3), (int) ($size * .8), (int) ($size * .7), $color);
            imagefilledellipse($im, (int) ($size * .5), (int) ($size * .25), (int) ($size * .3), (int) ($size * .3), $color);

            return $im;
        };
        $save = static function (\GdImage $im): string {
            $path = tempnam(sys_get_temp_dir(), 'fs').'.png';
            imagepng($im, $path);

            return $path;
        };

        // Misma forma: amarilla (la foto), azul (una variante) y la amarilla reducida.
        $amarilla = $save($forma(800, [230, 190, 40]));
        $azul = $save($forma(800, [40, 90, 200]));
        $reducida = $save($forma(300, [230, 190, 40]));

        $consulta = $index->fineSignature($reducida);
        $dAmarilla = CatalogImageFingerprintIndex::fineDistance($consulta, $index->fineSignature($amarilla));
        $dAzul = CatalogImageFingerprintIndex::fineDistance($consulta, $index->fineSignature($azul));

        foreach ([$amarilla, $azul, $reducida] as $file) {
            @unlink($file);
        }

        $this->assertLessThan(10, $dAmarilla, 'la misma foto reducida casi no cambia');
        $this->assertGreaterThan($dAmarilla * 3, $dAzul, 'otro color con la misma forma se nota');
    }
}
