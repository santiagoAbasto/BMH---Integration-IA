<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Services\Imagenes\OptimizadorImagenes;
use App\Services\Imagenes\ResultadoOptimizacion;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Optimizador de imágenes a WebP. No usa base: trabaja con archivos en una
 * carpeta temporal, generados con características conocidas.
 */
final class OptimizadorImagenesTest extends TestCase
{
    private string $dir;

    private OptimizadorImagenes $optimizador;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD sin soporte WebP');
        }

        $this->dir = sys_get_temp_dir().'/bmh_optim_'.uniqid();
        File::ensureDirectoryExists($this->dir);
        $this->optimizador = new OptimizadorImagenes(
            ladoMaximo: 2000,
            calidades: [90, 92, 94, 96, 98],
            fidelidad: ['luma' => 47.0, 'color' => 44.0, 'zona' => 38.0],
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    // ---------------------------------------------------------- imágenes de prueba

    /**
     * Una «foto»: degradé con textura suave, como las del catálogo guardadas
     * en PNG. Ruido fuerte no sirve: es incompresible y ninguna foto real lo
     * tiene (con 80 fotos reales el ahorro fue del 96 %).
     */
    private function foto(int $ancho, int $alto, string $nombre = 'foto.png'): string
    {
        $img = imagecreatetruecolor($ancho, $alto);
        mt_srand(3);
        for ($y = 0; $y < $alto; $y += 2) {
            for ($x = 0; $x < $ancho; $x += 2) {
                $ruido = mt_rand(-4, 4);
                $c = imagecolorallocate($img,
                    max(0, min(255, (int) (90 + 120 * $x / $ancho) + $ruido)),
                    max(0, min(255, (int) (80 + 100 * $y / $alto) + $ruido)),
                    max(0, min(255, 140 + $ruido)));
                imagefilledrectangle($img, $x, $y, $x + 1, $y + 1, $c);
            }
        }

        return $this->guardarPng($img, $nombre);
    }

    private function guardarPng(GdImage $img, string $nombre): string
    {
        $ruta = $this->dir.'/'.$nombre;
        imagesavealpha($img, true);
        imagepng($img, $ruta, 9);
        imagedestroy($img);

        return $ruta;
    }

    /**
     * ¿El WebP tiene canal alfa? Con pérdida el flag está en el chunk VP8X;
     * sin pérdida, en el encabezado VP8L (bit 28 después de la firma 0x2F).
     */
    private function webpTieneAlfa(string $ruta): bool
    {
        $datos = (string) file_get_contents($ruta, false, null, 0, 64);

        return match (substr($datos, 12, 4)) {
            'VP8X' => (ord($datos[20]) & 0x10) !== 0,
            'VP8L' => ((unpack('V', substr($datos, 21, 4))[1] >> 28) & 1) === 1,
            default => false,
        };
    }

    // ------------------------------------------------------------------ casos

    public function test_una_foto_grande_se_achica_al_lado_maximo_y_pesa_mucho_menos(): void
    {
        $origen = $this->foto(2600, 1800);
        $destino = $this->dir.'/foto.webp';

        $r = $this->optimizador->optimizar($origen, $destino);

        $this->assertTrue($r->optimizada, $r->motivo);
        $this->assertFileExists($destino);
        [$ancho, $alto, $tipo] = getimagesize($destino);
        $this->assertSame(IMAGETYPE_WEBP, $tipo);
        $this->assertSame([2000, 1385], [$ancho, $alto]);
        $this->assertLessThan($r->bytesOriginales * 0.5, $r->bytesNuevos);
        $this->assertSame(ResultadoOptimizacion::CON_PERDIDA, $r->modo);
        $this->assertContains($r->calidad, [90, 92, 94, 96, 98]);
        $this->assertGreaterThanOrEqual(47.0, $r->psnr);
        // El original no se toca: borrarlo es decisión de quien llama.
        $this->assertFileExists($origen);
    }

    public function test_nunca_agranda_una_imagen_chica(): void
    {
        $r = $this->optimizador->optimizar($this->foto(300, 200), $this->dir.'/chica.webp');

        $this->assertTrue($r->optimizada, $r->motivo);
        $this->assertSame([300, 200], [$r->ancho, $r->alto]);
    }

    public function test_conserva_la_transparencia_cuando_la_imagen_la_usa(): void
    {
        $img = imagecreatetruecolor(400, 400);
        imagealphablending($img, false);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagefilledellipse($img, 200, 200, 300, 300, imagecolorallocate($img, 200, 30, 30));
        $origen = $this->guardarPng($img, 'recorte.png');
        $destino = $this->dir.'/recorte.webp';

        $r = $this->optimizador->optimizar($origen, $destino);

        $this->assertTrue($r->optimizada, $r->motivo);
        $this->assertTrue($this->webpTieneAlfa($destino));
        $webp = imagecreatefromwebp($destino);
        $this->assertSame(127, (imagecolorat($webp, 5, 5) >> 24) & 0x7F, 'la esquina tiene que seguir transparente');
        $this->assertSame(0, (imagecolorat($webp, 200, 200) >> 24) & 0x7F, 'el centro tiene que seguir opaco');
    }

    public function test_no_guarda_canal_alfa_si_la_imagen_no_tiene_transparencia(): void
    {
        // PNG con canal alfa (RGBA) pero todos los píxeles opacos: el caso de
        // casi todas las fotos del catálogo.
        $origen = $this->foto(600, 400, 'rgba.png');
        $destino = $this->dir.'/rgba.webp';

        $this->assertTrue($this->optimizador->optimizar($origen, $destino)->optimizada);
        $this->assertFalse($this->webpTieneAlfa($destino));
    }

    public function test_un_grafico_de_pocos_colores_usa_webp_sin_perdida_si_es_mas_chico(): void
    {
        $img = imagecreatetruecolor(800, 600);
        $colores = [imagecolorallocate($img, 255, 255, 255), imagecolorallocate($img, 0, 152, 218), imagecolorallocate($img, 20, 20, 20)];
        imagefill($img, 0, 0, $colores[0]);
        for ($i = 0; $i < 12; $i++) {
            imagefilledrectangle($img, 40 + $i * 60, 100, 80 + $i * 60, 500, $colores[1 + $i % 2]);
        }
        $r = $this->optimizador->optimizar($this->guardarPng($img, 'grafico.png'), $this->dir.'/grafico.webp');

        $this->assertTrue($r->optimizada, $r->motivo);
        $this->assertSame(ResultadoOptimizacion::SIN_PERDIDA, $r->modo);
        // Sin pérdida: los colores quedan exactos.
        $webp = imagecreatefromwebp($this->dir.'/grafico.webp');
        $this->assertSame(0x0098DA, imagecolorat($webp, 50, 300) & 0xFFFFFF);
    }

    public function test_no_escribe_nada_si_no_ahorra_lo_pedido(): void
    {
        // Ya es un WebP chico: reconvertirlo no puede ahorrar la mitad.
        $origen = $this->dir.'/ya.webp';
        $img = imagecreatetruecolor(200, 200);
        imagefill($img, 0, 0, imagecolorallocate($img, 10, 120, 200));
        imagewebp($img, $origen, 80);
        $destino = $this->dir.'/ya-optimizada.webp';

        $r = $this->optimizador->optimizar($origen, $destino, ahorroMinimo: 50);

        $this->assertFalse($r->optimizada);
        $this->assertFileDoesNotExist($destino);
    }

    public function test_un_archivo_que_no_es_imagen_se_omite_sin_romper(): void
    {
        $origen = $this->dir.'/falso.png';
        file_put_contents($origen, 'no soy una imagen');

        $r = $this->optimizador->optimizar($origen, $this->dir.'/falso.webp');

        $this->assertFalse($r->optimizada);
        $this->assertFileDoesNotExist($this->dir.'/falso.webp');
    }

    public function test_una_subida_del_admin_se_guarda_como_webp(): void
    {
        $subida = new UploadedFile($this->foto(1200, 900, 'subida.png'), 'Foto del producto.PNG', 'image/png', null, true);
        $destino = $this->dir.'/imagenes';
        File::ensureDirectoryExists($destino);

        $nombre = $this->optimizador->guardarSubida($subida, $destino);

        $this->assertMatchesRegularExpression('/^media_[0-9a-f]+\.webp$/', $nombre);
        $this->assertSame(IMAGETYPE_WEBP, getimagesize($destino.'/'.$nombre)[2]);
    }

    public function test_si_el_color_se_dana_no_se_conforma_con_la_luminancia(): void
    {
        // Rayas rojas y verdes de casi la misma luminancia: con la luminancia
        // sola cualquier calidad «pasa», pero el WebP con pérdida submuestrea
        // el color y las mezcla. Tiene que quedar exacto.
        $img = imagecreatetruecolor(600, 400);
        $rojo = imagecolorallocate($img, 255, 0, 0);
        $verde = imagecolorallocate($img, 0, 128, 0);
        for ($x = 0; $x < 600; $x += 2) {
            imagefilledrectangle($img, $x, 0, $x, 399, $x % 4 === 0 ? $rojo : $verde);
        }
        $destino = $this->dir.'/rayas.webp';

        $r = $this->optimizador->optimizar($this->guardarPng($img, 'rayas.png'), $destino);

        $this->assertTrue($r->optimizada, $r->motivo);
        $this->assertSame(ResultadoOptimizacion::SIN_PERDIDA, $r->modo);
        $webp = imagecreatefromwebp($destino);
        $this->assertSame(0xFF0000, imagecolorat($webp, 0, 200) & 0xFFFFFF);
        $this->assertSame(0x008000, imagecolorat($webp, 2, 200) & 0xFFFFFF);
    }

    public function test_conserva_el_perfil_de_color_si_no_es_srgb(): void
    {
        $perfil = str_pad('Display P3 · perfil de prueba', 600, "\x01");
        $origen = $this->pngConPerfil($this->foto(800, 800, 'p3.png'), $perfil);
        $destino = $this->dir.'/p3.webp';

        $r = $this->optimizador->optimizar($origen, $destino);

        $this->assertTrue($r->optimizada, $r->motivo);
        $datos = (string) file_get_contents($destino);
        $this->assertSame('VP8X', substr($datos, 12, 4));
        $this->assertSame(0x20, ord($datos[20]) & 0x20, 'marca de ICC en VP8X');
        $this->assertStringContainsString('ICCP'.pack('V', strlen($perfil)).$perfil, $datos);
        // Sigue siendo un WebP válido.
        $this->assertSame(IMAGETYPE_WEBP, getimagesize($destino)[2]);
        $this->assertInstanceOf(GdImage::class, imagecreatefromwebp($destino));

        // Con un perfil sRGB no se agrega nada: sin perfil ya se asume sRGB.
        $srgb = $this->pngConPerfil($this->foto(800, 800, 'srgb.png'), str_pad('sRGB IEC61966-2.1', 600, "\x01"));
        $this->assertTrue($this->optimizador->optimizar($srgb, $this->dir.'/srgb.webp')->optimizada);
        $this->assertStringNotContainsString('ICCP', (string) file_get_contents($this->dir.'/srgb.webp'));
    }

    public function test_una_subida_webp_ya_comprimida_no_se_vuelve_a_comprimir(): void
    {
        // Recomprimir agregaría pérdida y no ahorraría lo suficiente: queda igual.
        $img = imagecreatefrompng($this->foto(1200, 900, 'base.png'));
        $ruta = $this->dir.'/ya.webp';
        imagewebp($img, $ruta, 85);
        $subida = new UploadedFile($ruta, 'Foto.webp', 'image/webp', null, true);
        $hash = md5_file($ruta);
        $destino = $this->dir.'/imagenes';
        File::ensureDirectoryExists($destino);

        $nombre = $this->optimizador->guardarSubida($subida, $destino);

        $this->assertMatchesRegularExpression('/^media_[0-9a-f]+\.webp$/', $nombre);
        $this->assertSame($hash, md5_file($destino.'/'.$nombre));
    }

    /** Inserta un chunk iCCP (perfil comprimido) antes de los datos del PNG. */
    private function pngConPerfil(string $ruta, string $perfil): string
    {
        $png = (string) file_get_contents($ruta);
        $datos = "perfil\0\0".gzcompress($perfil);
        $chunk = pack('N', strlen($datos)).'iCCP'.$datos.pack('N', crc32('iCCP'.$datos));
        // Después del IHDR (8 de firma + 25 del chunk IHDR).
        file_put_contents($ruta, substr($png, 0, 33).$chunk.substr($png, 33));

        return $ruta;
    }

    public function test_un_webp_ya_achicado_por_el_navegador_se_guarda_como_webp(): void
    {
        // public/js/achicar-imagenes.js manda WebP de 2000 px a calidad 95.
        $img = imagecreatefrompng($this->foto(2000, 1500, 'navegador.png'));
        $ruta = $this->dir.'/navegador.webp';
        imagewebp($img, $ruta, 95);
        $subida = new UploadedFile($ruta, 'Foto Camara.webp', 'image/webp', null, true);
        $destino = $this->dir.'/imagenes';
        File::ensureDirectoryExists($destino);

        $nombre = $this->optimizador->guardarSubida($subida, $destino);

        $this->assertMatchesRegularExpression('/^media_[0-9a-f]+\.webp$/', $nombre);
        [$ancho, $alto, $tipo] = getimagesize($destino.'/'.$nombre);
        $this->assertSame([2000, 1500, IMAGETYPE_WEBP], [$ancho, $alto, $tipo]);
    }

    public function test_una_subida_que_no_se_puede_optimizar_se_guarda_como_vino(): void
    {
        // Un GIF (formato no soportado por el optimizador) queda igual.
        $gif = imagecreate(50, 50);
        imagecolorallocate($gif, 255, 0, 0);
        $ruta = $this->dir.'/animado.gif';
        imagegif($gif, $ruta);
        $subida = new UploadedFile($ruta, 'animado.gif', 'image/gif', null, true);
        $destino = $this->dir.'/imagenes';
        File::ensureDirectoryExists($destino);

        $nombre = $this->optimizador->guardarSubida($subida, $destino);

        $this->assertMatchesRegularExpression('/^media_[0-9a-f]+\.gif$/', $nombre);
        $this->assertFileExists($destino.'/'.$nombre);
    }
}
