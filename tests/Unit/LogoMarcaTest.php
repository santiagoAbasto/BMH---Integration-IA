<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Imagenes\LogoMarca;
use GdImage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Logos de «Trabajamos con»: sin importar el margen que traiga la imagen, quedan en un lienzo de 366 × 196, centrados,
 * con el mismo peso visual (un logo cuadrado más alto que uno ancho, la misma superficie).
 */
final class LogoMarcaTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/logo_marca_'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        rmdir($this->dir);
    }

    /** Un logo (rectángulo azul) de $w × $h dentro de una imagen con $margen px de aire, transparente o blanca. */
    private function logo(int $w, int $h, int $margen, bool $transparente): string
    {
        $img = imagecreatetruecolor($w + 2 * $margen, $h + 2 * $margen);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, $transparente ? imagecolorallocatealpha($img, 255, 255, 255, 127) : imagecolorallocate($img, 255, 255, 255));
        imagefilledrectangle($img, $margen, $margen, $margen + $w - 1, $margen + $h - 1, imagecolorallocate($img, 0, 90, 200));
        $ruta = $this->dir.'/'.uniqid().($transparente ? '.png' : '.jpg');
        $transparente ? imagepng($img, $ruta) : imagejpeg($img, $ruta, 95);
        imagedestroy($img);

        return $ruta;
    }

    /** @return array{int, int, int, int} caja del logo en el resultado */
    private function caja(string $ruta): array
    {
        $img = imagecreatefromwebp($ruta);
        $this->assertInstanceOf(GdImage::class, $img);
        $x0 = PHP_INT_MAX; $y0 = PHP_INT_MAX; $x1 = -1; $y1 = -1;
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                if ((imagecolorat($img, $x, $y) >> 24 & 0x7F) < 64) {
                    $x0 = min($x0, $x); $x1 = max($x1, $x); $y0 = min($y0, $y); $y1 = max($y1, $y);
                }
            }
        }

        return [$x0, $y0, $x1 - $x0 + 1, $y1 - $y0 + 1];
    }

    public static function margenes(): array
    {
        return ['sin margen, transparente' => [0, true], 'mucho margen, transparente' => [150, true], 'fondo blanco (JPG)' => [80, false]];
    }

    #[DataProvider('margenes')]
    public function test_el_margen_de_la_imagen_no_cambia_el_resultado(int $margen, bool $transparente): void
    {
        $salida = $this->dir.'/salida.webp';
        (new LogoMarca())->normalizar($this->logo(300, 300, $margen, $transparente), $salida);

        $this->assertSame([LogoMarca::ANCHO, LogoMarca::ALTO], array_slice(getimagesize($salida), 0, 2));
        [$x, $y, $w, $h] = $this->caja($salida);
        // Cuadrado: 70% del alto (137 px), centrado.
        $this->assertEqualsWithDelta(137, $h, 3);
        $this->assertEqualsWithDelta(137, $w, 3);
        $this->assertEqualsWithDelta((LogoMarca::ANCHO - $w) / 2, $x, 2);
        $this->assertEqualsWithDelta((LogoMarca::ALTO - $h) / 2, $y, 2);
    }

    public function test_un_logo_ancho_queda_mas_bajo_con_la_misma_superficie(): void
    {
        $cuadrado = $this->dir.'/c.webp';
        $ancho = $this->dir.'/a.webp';
        (new LogoMarca())->normalizar($this->logo(200, 200, 20, true), $cuadrado);
        (new LogoMarca())->normalizar($this->logo(400, 100, 20, true), $ancho);

        [, , $wc, $hc] = $this->caja($cuadrado);
        [, , $wa, $ha] = $this->caja($ancho);
        $this->assertLessThan($hc, $ha);
        $this->assertGreaterThan($wc, $wa);
        $this->assertEqualsWithDelta($wc * $hc, $wa * $ha, $wc * $hc * 0.05);
    }

    public function test_uno_muy_ancho_no_pasa_del_ancho_maximo(): void
    {
        $salida = $this->dir.'/s.webp';
        (new LogoMarca())->normalizar($this->logo(1200, 60, 0, true), $salida);

        [, , $w] = $this->caja($salida);
        $this->assertLessThanOrEqual((int) round(LogoMarca::ANCHO * 0.86) + 1, $w);
    }
}
