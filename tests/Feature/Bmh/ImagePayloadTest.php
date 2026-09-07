<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Services\Ai\Support\ImagePayload;
use Tests\TestCase;

/**
 * Lo que se le manda al modelo tiene que ser liviano.
 *
 * Las fotos del catálogo llegan a 4,5 MB. Cuatro de esas en base64 son ~24 MB de
 * subida por comparación: el turno tardaba más de 30 segundos y PHP mataba el
 * proceso a la mitad del stream. Con `detail: low` el modelo mira 512 px de
 * todos modos, así que mandar el original era pagar por píxeles descartados.
 */
final class ImagePayloadTest extends TestCase
{
    private string $grande;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grande = tempnam(sys_get_temp_dir(), 'bmh') . '.jpg';

        // 2000×1500 con ruido: sin ruido el JPEG comprime tanto que la prueba
        // no mide nada.
        $lienzo = imagecreatetruecolor(2000, 1500);

        for ($x = 0; $x < 2000; $x += 4) {
            for ($y = 0; $y < 1500; $y += 4) {
                $color = imagecolorallocate($lienzo, ($x * 7) % 255, ($y * 13) % 255, ($x + $y) % 255);
                imagefilledrectangle($lienzo, $x, $y, $x + 3, $y + 3, $color);
            }
        }

        imagejpeg($lienzo, $this->grande, 95);
        imagedestroy($lienzo);
    }

    protected function tearDown(): void
    {
        @unlink($this->grande);

        parent::tearDown();
    }

    public function test_una_foto_grande_se_achica_antes_de_mandarla(): void
    {
        $original = (int) filesize($this->grande);

        [$mime, $base64] = ImagePayload::encode($this->grande);

        $this->assertSame('image/jpeg', $mime);
        $this->assertLessThan(
            $original,
            strlen($base64),
            'el base64 reducido tiene que pesar menos que el archivo original',
        );

        // Y el lado mayor tiene que quedar en 512: es lo que consume el modelo.
        $medidas = getimagesizefromstring(base64_decode($base64, true) ?: '');

        $this->assertNotFalse($medidas);
        $this->assertLessThanOrEqual(512, max($medidas[0], $medidas[1]));
    }

    public function test_el_mime_declarado_coincide_con_lo_que_realmente_se_manda(): void
    {
        // Un PNG reducido sale como JPEG: si se declarara image/png el proveedor
        // recibiría un cuerpo que no coincide con su cabecera.
        $png = tempnam(sys_get_temp_dir(), 'bmh') . '.png';

        $lienzo = imagecreatetruecolor(1200, 900);
        imagefilledrectangle($lienzo, 0, 0, 1200, 900, imagecolorallocate($lienzo, 10, 120, 218));
        imagepng($lienzo, $png);
        imagedestroy($lienzo);

        [$mime, $base64] = ImagePayload::encode($png);

        $medidas = getimagesizefromstring(base64_decode($base64, true) ?: '');

        $this->assertNotFalse($medidas);
        $this->assertSame($mime, $medidas['mime']);

        @unlink($png);
    }

    public function test_una_foto_ya_chica_se_manda_tal_cual(): void
    {
        $chica = tempnam(sys_get_temp_dir(), 'bmh') . '.png';

        $lienzo = imagecreatetruecolor(64, 64);
        imagefilledrectangle($lienzo, 0, 0, 64, 64, imagecolorallocate($lienzo, 200, 30, 30));
        imagepng($lienzo, $chica);
        imagedestroy($lienzo);

        [$mime, $base64] = ImagePayload::encode($chica);

        $this->assertSame('image/png', $mime, 'reencodear una imagen chica sólo agrega pérdida');
        $this->assertSame(base64_encode((string) file_get_contents($chica)), $base64);

        @unlink($chica);
    }

    public function test_un_archivo_ilegible_no_rompe_el_turno(): void
    {
        $roto = tempnam(sys_get_temp_dir(), 'bmh') . '.jpg';
        file_put_contents($roto, 'esto no es una imagen');

        [$mime, $base64] = ImagePayload::encode($roto);

        $this->assertNotSame('', $base64);
        $this->assertNotSame('', $mime);

        @unlink($roto);
    }
}
