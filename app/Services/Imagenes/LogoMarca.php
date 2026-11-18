<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use GdImage;
use RuntimeException;

/**
 * Logo de «Trabajamos con» (Home) con el mismo margen y el mismo peso visual que los demás.
 *
 * En la Home cada logo ocupa una caja de 183 × 98 px. Si se muestran tal cual, un logo cuadrado llena los 98 px de alto
 * y uno muy ancho queda en 30 px: se ven de tamaños muy distintos. Acá se recorta el margen que traiga la imagen y el
 * logo se ubica centrado en un lienzo transparente de 366 × 196 (el doble, para pantallas de alta densidad) con un
 * tamaño según su proporción: misma superficie para todos (el alto baja con la raíz de la proporción), sin pasarse de
 * un alto y un ancho máximos. Así todos quedan con el mismo aire alrededor.
 */
final class LogoMarca
{
    public const ANCHO = 366;

    public const ALTO = 196;

    /** Alto de un logo cuadrado, en fracción del alto del lienzo (los más anchos quedan más bajos). */
    private const ALTO_CUADRADO = 0.70;

    private const ALTO_MAXIMO = 0.72;

    private const ANCHO_MAXIMO = 0.86;

    /** Lado máximo con el que se analiza la imagen para encontrar el logo (el recorte se hace sobre la original). */
    private const LADO_ANALISIS = 700;

    public function normalizar(string $origen, string $destino): void
    {
        $info = @getimagesize($origen);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('El logo tiene que ser JPG, PNG o WebP.');
        }

        $imagen = $this->cargar($origen, $info[2]);
        $lienzo = null;
        try {
            [$x, $y, $w, $h] = $this->cajaDelLogo($imagen);
            $proporcion = $w / $h;

            $alto = min(self::ALTO_MAXIMO * self::ALTO, self::ALTO_CUADRADO * self::ALTO / sqrt($proporcion));
            $ancho = $alto * $proporcion;
            if ($ancho > self::ANCHO_MAXIMO * self::ANCHO) {
                $ancho = self::ANCHO_MAXIMO * self::ANCHO;
                $alto = $ancho / $proporcion;
            }
            $ancho = max(1, (int) round($ancho));
            $alto = max(1, (int) round($alto));

            $lienzo = imagecreatetruecolor(self::ANCHO, self::ALTO);
            imagealphablending($lienzo, false);
            imagesavealpha($lienzo, true);
            imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 255, 255, 255, 127));
            imagecopyresampled($lienzo, $imagen, intdiv(self::ANCHO - $ancho, 2), intdiv(self::ALTO - $alto, 2), $x, $y, $ancho, $alto, $w, $h);

            $calidad = defined('IMG_WEBP_LOSSLESS') ? IMG_WEBP_LOSSLESS : 100;
            if (! imagewebp($lienzo, $destino, $calidad)) {
                throw new RuntimeException('No se pudo guardar el logo.');
            }
        } finally {
            imagedestroy($imagen);
            if ($lienzo instanceof GdImage) {
                imagedestroy($lienzo);
            }
        }
    }

    private function cargar(string $ruta, int $tipo): GdImage
    {
        $imagen = match ($tipo) {
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
        };
        if (! $imagen instanceof GdImage) {
            throw new RuntimeException('No se pudo leer el logo.');
        }
        if (! imageistruecolor($imagen)) {
            imagepalettetotruecolor($imagen);
        }
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        return $imagen;
    }

    /**
     * [x, y, ancho, alto] del logo dentro de la imagen, sin el margen. Con fondo transparente, lo que no es
     * transparente; con fondo de color (un JPG con fondo blanco), lo que se distingue del color de las esquinas.
     *
     * @return array{int, int, int, int}
     */
    private function cajaDelLogo(GdImage $imagen): array
    {
        $W = imagesx($imagen);
        $H = imagesy($imagen);
        $escala = min(1, self::LADO_ANALISIS / max($W, $H));
        $w = max(1, (int) round($W * $escala));
        $h = max(1, (int) round($H * $escala));

        $chica = imagecreatetruecolor($w, $h);
        imagealphablending($chica, false);
        imagesavealpha($chica, true);
        imagecopyresampled($chica, $imagen, 0, 0, 0, 0, $w, $h, $W, $H);

        $color = static fn (int $x, int $y): array => [($c = imagecolorat($chica, $x, $y)) >> 16 & 0xFF, $c >> 8 & 0xFF, $c & 0xFF, $c >> 24 & 0x7F];
        $esquinas = [$color(0, 0), $color($w - 1, 0), $color(0, $h - 1), $color($w - 1, $h - 1)];
        // GD: alfa 0 = opaco, 127 = transparente.
        $fondoTransparente = count(array_filter($esquinas, static fn (array $p): bool => $p[3] > 100)) >= 2;
        $fondo = array_map(static fn (int $i): float => array_sum(array_column($esquinas, $i)) / 4, [0, 1, 2]);

        $x0 = $w;
        $y0 = $h;
        $x1 = -1;
        $y1 = -1;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $p = $color($x, $y);
                $esLogo = $fondoTransparente
                    ? $p[3] < 112
                    : $p[3] < 112 && max(abs($p[0] - $fondo[0]), abs($p[1] - $fondo[1]), abs($p[2] - $fondo[2])) > 24;
                if ($esLogo) {
                    $x0 = min($x0, $x);
                    $x1 = max($x1, $x);
                    $y0 = min($y0, $y);
                    $y1 = max($y1, $y);
                }
            }
        }
        imagedestroy($chica);

        if ($x1 < 0) {
            return [0, 0, $W, $H]; // imagen vacía o lisa: se usa entera
        }

        $X0 = max(0, (int) floor($x0 / $escala));
        $Y0 = max(0, (int) floor($y0 / $escala));
        $X1 = min($W, (int) ceil(($x1 + 1) / $escala));
        $Y1 = min($H, (int) ceil(($y1 + 1) / $escala));

        return [$X0, $Y0, max(1, $X1 - $X0), max(1, $Y1 - $Y0)];
    }
}
