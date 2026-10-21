<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\Legacy\ProductImageService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Después de `imagenes:optimizar` el archivo es media_x.webp aunque la base
 * (o una copia de la base) todavía diga media_x.png.
 */
final class ProductImageServiceTest extends TestCase
{
    private string $publico;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publico = sys_get_temp_dir().'/bmh_publico_'.uniqid();
        File::ensureDirectoryExists($this->publico.'/imagenes');
        $this->app->usePublicPath($this->publico);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publico);
        parent::tearDown();
    }

    public function test_usa_el_webp_si_el_original_ya_se_convirtio(): void
    {
        file_put_contents($this->publico.'/imagenes/convertida.webp', 'x');
        file_put_contents($this->publico.'/imagenes/intacta.png', 'x');
        $servicio = new ProductImageService();

        $this->assertSame('convertida.webp', $servicio->current('convertida.png'));
        $this->assertSame('convertida.webp', $servicio->current('convertida.JPG'));
        $this->assertSame('intacta.png', $servicio->current('intacta.png'));
        $this->assertNull($servicio->current('no-existe.png'));
        $this->assertNull($servicio->current(''));
    }
}
