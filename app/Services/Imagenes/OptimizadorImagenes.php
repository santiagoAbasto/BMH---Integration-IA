<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use GdImage;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Convierte imágenes (PNG, JPEG, WebP) a WebP livianas sin pérdida visible.
 *
 * Usa GD, que tiene el PHP del sitio con soporte WebP: la misma conversión
 * sirve al subir desde el admin y al procesar en lote las que ya están.
 *
 * Criterios (ver config/imagenes.php):
 *  - Achica al lado máximo si hace falta; nunca agranda.
 *  - Endereza las fotos de celular según la orientación EXIF.
 *  - Guarda canal alfa sólo si la imagen tiene transparencia de verdad.
 *  - Calidad adaptativa: prueba de menor a mayor y se queda con la primera
 *    cuya fidelidad (PSNR de luminancia contra el original) alcanza el mínimo.
 *  - Gráficos de pocos colores: prueba también WebP sin pérdida y elige el
 *    más chico.
 *  - Si el WebP no es más chico que el original, no escribe nada.
 */
final class OptimizadorImagenes
{
    private const LOSSLESS = 101; // IMG_WEBP_LOSSLESS (PHP ≥ 8.1)

    /** Muestras para medir la fidelidad: bastan para que el PSNR sea estable. */
    private const MUESTRAS_PSNR = 250_000;

    /** Hasta cuántos colores se considera un gráfico (logo, dibujo, texto). */
    private const COLORES_GRAFICO = 128;

    private int $ladoMaximo;

    /** @var list<int> */
    private array $calidades;

    private float $psnrMinimo;

    public function __construct(?int $ladoMaximo = null, ?array $calidades = null, ?float $psnrMinimo = null)
    {
        $this->ladoMaximo = $ladoMaximo ?? (int) config('imagenes.lado_maximo', 2000);
        $this->calidades = array_values($calidades ?? config('imagenes.calidades', [82, 88, 94]));
        $this->psnrMinimo = $psnrMinimo ?? (float) config('imagenes.psnr_minimo', 40.0);
    }

    /**
     * Optimiza $origen y, si conviene, escribe el WebP en $destino.
     * El origen nunca se toca: borrarlo es decisión de quien llama.
     *
     * @param  float  $ahorroMinimo  porcentaje que tiene que ahorrar para escribirse
     */
    public function optimizar(string $origen, string $destino, float $ahorroMinimo = 0.0): ResultadoOptimizacion
    {
        $bytes = @filesize($origen) ?: 0;
        $info = @getimagesize($origen);

        if ($bytes === 0 || $info === false) {
            return ResultadoOptimizacion::omitida('no es una imagen legible', $bytes);
        }
        if (! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            return ResultadoOptimizacion::omitida('formato no soportado', $bytes);
        }
        if (! $this->entraEnMemoria($info[0], $info[1])) {
            return ResultadoOptimizacion::omitida('demasiado grande para la memoria disponible', $bytes);
        }

        $imagen = null;
        try {
            $imagen = $this->cargar($origen, $info[2]);
            if ($imagen === null) {
                return ResultadoOptimizacion::omitida('no se pudo decodificar', $bytes);
            }

            $imagen = $this->enderezar($imagen, $origen, $info[2]);
            $imagen = $this->achicar($imagen);
            $conAlfa = $this->tieneTransparencia($imagen, $origen, $info[2]);

            $elegido = $this->mejorCodificacion($imagen, $conAlfa);
            if ($elegido === null) {
                return ResultadoOptimizacion::omitida('ninguna calidad alcanzó la fidelidad mínima', $bytes);
            }

            [$datos, $modo, $calidad, $psnr] = $elegido;
            $nuevos = strlen($datos);

            if ($nuevos >= $bytes * (1 - $ahorroMinimo / 100)) {
                return ResultadoOptimizacion::omitida('el WebP no ahorra lo suficiente', $bytes);
            }

            if (@file_put_contents($destino, $datos, LOCK_EX) !== $nuevos) {
                @unlink($destino);

                return ResultadoOptimizacion::omitida('no se pudo escribir el archivo', $bytes);
            }

            return ResultadoOptimizacion::optimizada($bytes, $nuevos, imagesx($imagen), imagesy($imagen), $modo, $calidad, $psnr);
        } catch (Throwable $e) {
            @unlink($destino);

            return ResultadoOptimizacion::omitida('error: '.$e->getMessage(), $bytes);
        } finally {
            if ($imagen instanceof GdImage) {
                imagedestroy($imagen);
            }
        }
    }

    /**
     * Guarda una imagen subida desde el admin en $directorio, optimizada a
     * WebP si conviene; si no, tal como vino. Devuelve el nombre del archivo.
     */
    public function guardarSubida(UploadedFile $archivo, string $directorio): string
    {
        $nombre = 'media_'.uniqid().'.webp';
        $resultado = $this->optimizar($archivo->getRealPath(), $directorio.DIRECTORY_SEPARATOR.$nombre);

        if ($resultado->optimizada) {
            return $nombre;
        }

        $nombre = 'media_'.uniqid().'.'.strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension());
        $archivo->move($directorio, $nombre);

        return $nombre;
    }

    // ------------------------------------------------------------- carga

    private function cargar(string $ruta, int $tipo): ?GdImage
    {
        $imagen = match ($tipo) {
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
        };

        if (! $imagen instanceof GdImage) {
            return null;
        }

        if (! imageistruecolor($imagen)) {
            imagepalettetotruecolor($imagen);
        }
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        return $imagen;
    }

    /** Estima si decodificar y trabajar la imagen entra en la memoria de PHP. */
    private function entraEnMemoria(int $ancho, int $alto): bool
    {
        $limite = $this->bytesDeIni((string) ini_get('memory_limit'));
        if ($limite <= 0) {
            return true;
        }

        // Original + copia achicada + candidato decodificado para medir.
        $necesario = $ancho * $alto * 5 + 3 * $this->ladoMaximo ** 2 * 5;

        return $necesario < ($limite - memory_get_usage(true)) * 0.8;
    }

    private function bytesDeIni(string $valor): int
    {
        $valor = trim($valor);
        if ($valor === '-1') {
            return -1;
        }
        $numero = (int) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g' => $numero * 1024 ** 3,
            'm' => $numero * 1024 ** 2,
            'k' => $numero * 1024,
            default => $numero,
        };
    }

    // ---------------------------------------------------------- transformación

    /** Aplica la orientación EXIF de las fotos (celulares guardan girado). */
    private function enderezar(GdImage $imagen, string $ruta, int $tipo): GdImage
    {
        if ($tipo !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $imagen;
        }

        $orientacion = (int) (@exif_read_data($ruta)['Orientation'] ?? 1);

        if (in_array($orientacion, [2, 4, 5, 7], true)) {
            imageflip($imagen, IMG_FLIP_HORIZONTAL);
        }

        $grados = match ($orientacion) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($grados === 0) {
            return $imagen;
        }

        $girada = imagerotate($imagen, $grados, 0);
        if (! $girada instanceof GdImage) {
            return $imagen;
        }
        imagedestroy($imagen);
        imagealphablending($girada, false);
        imagesavealpha($girada, true);

        return $girada;
    }

    private function achicar(GdImage $imagen): GdImage
    {
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $mayor = max($ancho, $alto);

        if ($mayor <= $this->ladoMaximo) {
            return $imagen;
        }

        $escala = $this->ladoMaximo / $mayor;
        $nuevoAncho = max(1, (int) round($ancho * $escala));
        $nuevoAlto = max(1, (int) round($alto * $escala));

        $chica = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagealphablending($chica, false);
        imagesavealpha($chica, true);
        imagefill($chica, 0, 0, imagecolorallocatealpha($chica, 0, 0, 0, 127));
        imagecopyresampled($chica, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagedestroy($imagen);

        return $chica;
    }

    /**
     * ¿Tiene píxeles transparentes? Un PNG sin canal alfa ni tRNS no puede;
     * si puede, se revisa la imagen (reducida, así no se recorren millones
     * de píxeles: un área transparente sobrevive al promedio).
     */
    private function tieneTransparencia(GdImage $imagen, string $ruta, int $tipo): bool
    {
        if ($tipo === IMAGETYPE_JPEG) {
            return false;
        }
        if ($tipo === IMAGETYPE_PNG && ! $this->pngPuedeTenerAlfa($ruta)) {
            return false;
        }

        $muestra = $this->reducida($imagen, 256, suavizar: true);
        $ancho = imagesx($muestra);
        $alto = imagesy($muestra);
        $hay = false;

        for ($y = 0; $y < $alto && ! $hay; $y++) {
            for ($x = 0; $x < $ancho; $x++) {
                if ((imagecolorat($muestra, $x, $y) >> 24) & 0x7F) {
                    $hay = true;
                    break;
                }
            }
        }
        imagedestroy($muestra);

        return $hay;
    }

    private function pngPuedeTenerAlfa(string $ruta): bool
    {
        $cabecera = (string) @file_get_contents($ruta, false, null, 0, 64);
        $tipoColor = strlen($cabecera) > 25 ? ord($cabecera[25]) : 6;

        // 4 = gris + alfa, 6 = RGBA; tRNS = transparencia en paleta o color fijo.
        return in_array($tipoColor, [4, 6], true) || str_contains($cabecera, 'tRNS')
            || str_contains((string) @file_get_contents($ruta, false, null, 0, 8192), 'tRNS');
    }

    // ----------------------------------------------------------- codificación

    /**
     * @return array{string, string, ?int, ?float}|null datos, modo, calidad, psnr
     */
    private function mejorCodificacion(GdImage $imagen, bool $conAlfa): ?array
    {
        imagesavealpha($imagen, $conAlfa);

        $elegido = null;
        foreach ($this->calidades as $calidad) {
            $datos = $this->webp($imagen, $calidad);
            if ($datos === null) {
                continue;
            }
            $psnr = $this->psnr($imagen, $datos);
            $elegido = [$datos, ResultadoOptimizacion::CON_PERDIDA, $calidad, $psnr];
            if ($psnr >= $this->psnrMinimo) {
                break;
            }
            $elegido = null;
        }

        // Gráficos (logos, dibujos): el WebP sin pérdida suele ganar, y es exacto.
        if ($this->esGrafico($imagen)) {
            $sinPerdida = $this->webp($imagen, self::LOSSLESS);
            if ($sinPerdida !== null && ($elegido === null || strlen($sinPerdida) < strlen($elegido[0]))) {
                $elegido = [$sinPerdida, ResultadoOptimizacion::SIN_PERDIDA, null, null];
            }
        }

        return $elegido;
    }

    private function webp(GdImage $imagen, int $calidad): ?string
    {
        ob_start();
        $ok = imagewebp($imagen, null, $calidad);
        $datos = (string) ob_get_clean();

        return $ok && $datos !== '' ? $datos : null;
    }

    private function esGrafico(GdImage $imagen): bool
    {
        // Sin suavizar: el promedio inventaría colores intermedios.
        $muestra = $this->reducida($imagen, 96, suavizar: false);
        $colores = [];
        for ($y = 0, $alto = imagesy($muestra); $y < $alto; $y++) {
            for ($x = 0, $ancho = imagesx($muestra); $x < $ancho; $x++) {
                $colores[imagecolorat($muestra, $x, $y)] = true;
                if (count($colores) > self::COLORES_GRAFICO) {
                    imagedestroy($muestra);

                    return false;
                }
            }
        }
        imagedestroy($muestra);

        return true;
    }

    /**
     * PSNR de luminancia entre la imagen y el WebP codificado, sobre una
     * grilla de muestras. Las zonas transparentes se comparan sobre blanco,
     * que es como se ven en el sitio.
     */
    private function psnr(GdImage $original, string $webp): float
    {
        $codificada = @imagecreatefromstring($webp);
        if (! $codificada instanceof GdImage) {
            return 0.0;
        }

        $ancho = imagesx($original);
        $alto = imagesy($original);
        $paso = max(1, (int) floor(sqrt($ancho * $alto / self::MUESTRAS_PSNR)));

        $suma = 0.0;
        $n = 0;
        for ($y = 0; $y < $alto; $y += $paso) {
            for ($x = 0; $x < $ancho; $x += $paso) {
                $d = $this->luma(imagecolorat($original, $x, $y)) - $this->luma(imagecolorat($codificada, $x, $y));
                $suma += $d * $d;
                $n++;
            }
        }
        imagedestroy($codificada);

        $mse = $n > 0 ? $suma / $n : 0.0;

        return $mse <= 1e-10 ? 99.0 : 10 * log10(255 ** 2 / $mse);
    }

    /** Luminancia (BT.601) del color compuesto sobre blanco. */
    private function luma(int $color): float
    {
        $opacidad = 1 - (($color >> 24) & 0x7F) / 127;
        $r = (($color >> 16) & 0xFF) * $opacidad + 255 * (1 - $opacidad);
        $g = (($color >> 8) & 0xFF) * $opacidad + 255 * (1 - $opacidad);
        $b = ($color & 0xFF) * $opacidad + 255 * (1 - $opacidad);

        return 0.299 * $r + 0.587 * $g + 0.114 * $b;
    }

    private function reducida(GdImage $imagen, int $lado, bool $suavizar): GdImage
    {
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $escala = min(1, $lado / max($ancho, $alto));
        $w = max(1, (int) round($ancho * $escala));
        $h = max(1, (int) round($alto * $escala));

        $chica = imagecreatetruecolor($w, $h);
        imagealphablending($chica, false);
        imagesavealpha($chica, true);
        imagefill($chica, 0, 0, imagecolorallocatealpha($chica, 0, 0, 0, 127));
        $suavizar
            ? imagecopyresampled($chica, $imagen, 0, 0, 0, 0, $w, $h, $ancho, $alto)
            : imagecopyresized($chica, $imagen, 0, 0, 0, 0, $w, $h, $ancho, $alto);

        return $chica;
    }
}
