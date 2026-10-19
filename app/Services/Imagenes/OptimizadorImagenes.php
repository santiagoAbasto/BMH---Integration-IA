<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use GdImage;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Convierte imágenes (PNG, JPEG, WebP) a WebP livianas, cambiando la calidad
 * lo menos posible.
 *
 * Usa GD, que tiene el PHP del sitio con soporte WebP: la misma conversión
 * sirve al subir desde el admin y al procesar en lote las que ya están.
 *
 * Criterios (ver config/imagenes.php):
 *  - Achica al lado máximo si hace falta; nunca agranda.
 *  - Endereza las fotos de celular según la orientación EXIF.
 *  - Guarda canal alfa sólo si la imagen tiene transparencia de verdad.
 *  - Calidad adaptativa: prueba de menor a mayor y se queda con la primera
 *    que pasa los tres controles de fidelidad contra el original: luminancia
 *    global, color (el WebP con pérdida submuestrea el color: corre rojos y
 *    azules en bordes y letras) y la peor zona de la imagen (un promedio
 *    bueno puede esconder un borde o una etiqueta dañados).
 *  - Si ninguna calidad los pasa, WebP sin pérdida (exacto); si tampoco
 *    conviene, el original queda como está. Nunca se baja de la fidelidad.
 *  - Gráficos de pocos colores: prueba también WebP sin pérdida y elige el
 *    más chico.
 *  - Conserva el perfil de color (ICC) si el original trae uno que no es sRGB.
 *  - Si el WebP no es más chico que el original, no escribe nada.
 */
final class OptimizadorImagenes
{
    private const LOSSLESS = 101; // IMG_WEBP_LOSSLESS (PHP ≥ 8.1)

    /** Muestras para medir la fidelidad: bastan para que el PSNR sea estable. */
    private const MUESTRAS_PSNR = 250_000;

    /** Zonas de LADO_ZONA × LADO_ZONA muestras para buscar la peor parte. */
    private const LADO_ZONA = 8;

    /** La «peor zona» es el percentil 1: un solo punto raro no decide. */
    private const PERCENTIL_ZONA = 0.01;

    /** Hasta cuántos colores se considera un gráfico (logo, dibujo, texto). */
    private const COLORES_GRAFICO = 128;

    /** Una subida que ya es WebP sólo se recomprime si ahorra al menos esto (%). */
    private const AHORRO_PARA_RECOMPRIMIR_WEBP = 30.0;

    private int $ladoMaximo;

    /** @var list<int> */
    private array $calidades;

    /** @var array{luma: float, color: float, zona: float} PSNR mínimos (dB) */
    private array $fidelidad;

    /**
     * @param  array{luma?: float, color?: float, zona?: float}|null  $fidelidad
     */
    public function __construct(?int $ladoMaximo = null, ?array $calidades = null, ?array $fidelidad = null)
    {
        $this->ladoMaximo = $ladoMaximo ?? (int) config('imagenes.lado_maximo', 2000);
        $this->calidades = array_values($calidades ?? config('imagenes.calidades', [90, 92, 94, 96, 98]));
        $this->fidelidad = array_map('floatval', ($fidelidad ?? []) + config('imagenes.fidelidad', []) + ['luma' => 47.0, 'color' => 44.0, 'zona' => 38.0]);
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
            $perfil = $this->perfilDeColor($origen, $info[2]);
            if ($perfil !== null) {
                $datos = $this->conPerfil($datos, $perfil, imagesx($imagen), imagesy($imagen));
            }
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
        // El navegador ya manda las fotos achicadas en WebP casi sin pérdida
        // (public/js/achicar-imagenes.js): recomprimir un WebP agrega otra
        // generación de pérdida, así que sólo se hace si ahorra de verdad.
        $esWebp = (@getimagesize($archivo->getRealPath())[2] ?? null) === IMAGETYPE_WEBP;

        $nombre = 'media_'.uniqid().'.webp';
        $resultado = $this->optimizar($archivo->getRealPath(), $directorio.DIRECTORY_SEPARATOR.$nombre,
            $esWebp ? self::AHORRO_PARA_RECOMPRIMIR_WEBP : 0.0);

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
            $medida = $this->fidelidad($imagen, $datos);
            if ($this->alcanza($medida)) {
                $elegido = [$datos, ResultadoOptimizacion::CON_PERDIDA, $calidad, $medida['luma']];
                break;
            }
        }

        // Sin pérdida (exacto): los gráficos (logos, dibujos) suelen quedar
        // más chicos así; y si ninguna calidad con pérdida pasó los controles,
        // es la única forma de no bajar la calidad. Que convenga frente al
        // original lo decide quien llama (ahorro mínimo).
        if ($elegido === null || $this->esGrafico($imagen)) {
            $sinPerdida = $this->webp($imagen, self::LOSSLESS);
            if ($sinPerdida !== null && ($elegido === null || strlen($sinPerdida) < strlen($elegido[0]))) {
                $elegido = [$sinPerdida, ResultadoOptimizacion::SIN_PERDIDA, null, null];
            }
        }

        return $elegido;
    }

    /** @param  array{luma: float, color: float, zona: float}  $medida */
    private function alcanza(array $medida): bool
    {
        return $medida['luma'] >= $this->fidelidad['luma']
            && $medida['color'] >= $this->fidelidad['color']
            && $medida['zona'] >= $this->fidelidad['zona'];
    }

    // ------------------------------------------------------- perfil de color

    /**
     * El perfil ICC del original, si trae uno que no es sRGB (sin perfil se
     * asume sRGB: no hace falta guardarlo). GD lo descarta al decodificar;
     * sin él, una foto en Display P3 o Adobe RGB se vería más apagada.
     */
    private function perfilDeColor(string $ruta, int $tipo): ?string
    {
        $datos = (string) @file_get_contents($ruta);
        $perfil = match ($tipo) {
            IMAGETYPE_JPEG => $this->perfilJpeg($datos),
            IMAGETYPE_PNG => $this->perfilPng($datos),
            IMAGETYPE_WEBP => $this->chunkWebp($datos, 'ICCP'),
            default => null,
        };

        if ($perfil === null || strlen($perfil) < 128) {
            return null;
        }
        // La descripción va en ASCII (ICC v2) o UTF-16 (v4).
        $cabecera = substr($perfil, 0, 4096);

        return stripos($cabecera, 'sRGB') !== false || str_contains($cabecera, "s\0R\0G\0B") ? null : $perfil;
    }

    /** JPEG: segmentos APP2 «ICC_PROFILE», que pueden venir partidos en varios. */
    private function perfilJpeg(string $datos): ?string
    {
        $partes = [];
        $i = 2;
        $largo = strlen($datos);
        while ($i + 4 <= $largo && $datos[$i] === "\xFF") {
            $marca = ord($datos[$i + 1]);
            if ($marca === 0xDA || $marca === 0xD9) {
                break; // empieza la imagen: no hay más metadatos
            }
            $tam = unpack('n', substr($datos, $i + 2, 2))[1];
            if ($marca === 0xE2 && substr($datos, $i + 4, 12) === "ICC_PROFILE\0") {
                $partes[ord($datos[$i + 16])] = substr($datos, $i + 18, $tam - 16);
            }
            $i += 2 + $tam;
        }
        if ($partes === []) {
            return null;
        }
        ksort($partes);

        return implode('', $partes);
    }

    /** PNG: chunk iCCP (nombre, método de compresión y el perfil comprimido). */
    private function perfilPng(string $datos): ?string
    {
        $i = 8;
        $largo = strlen($datos);
        while ($i + 8 <= $largo) {
            $tam = unpack('N', substr($datos, $i, 4))[1];
            $tipo = substr($datos, $i + 4, 4);
            if ($tipo === 'iCCP') {
                $contenido = substr($datos, $i + 8, $tam);
                $fin = strpos($contenido, "\0");
                $perfil = $fin === false ? false : @gzuncompress(substr($contenido, $fin + 2));

                return $perfil === false ? null : $perfil;
            }
            if ($tipo === 'IDAT' || $tipo === 'IEND') {
                return null; // el perfil va antes de los datos de imagen
            }
            $i += 12 + $tam;
        }

        return null;
    }

    /** El contenido de un chunk de un WebP (RIFF), o null. */
    private function chunkWebp(string $datos, string $buscado): ?string
    {
        if (substr($datos, 0, 4) !== 'RIFF' || substr($datos, 8, 4) !== 'WEBP') {
            return null;
        }
        for ($i = 12, $largo = strlen($datos); $i + 8 <= $largo;) {
            $tam = unpack('V', substr($datos, $i + 4, 4))[1];
            if (substr($datos, $i, 4) === $buscado) {
                return substr($datos, $i + 8, $tam);
            }
            $i += 8 + $tam + ($tam % 2);
        }

        return null;
    }

    /**
     * Agrega el perfil ICC al WebP: pasa al formato extendido (chunk VP8X con
     * la marca de ICC) y pone el chunk ICCP antes de la imagen, como pide el
     * formato. Si algo no cuadra, devuelve el WebP como estaba.
     */
    private function conPerfil(string $webp, string $perfil, int $ancho, int $alto): string
    {
        if (substr($webp, 0, 4) !== 'RIFF' || substr($webp, 8, 4) !== 'WEBP' || $this->chunkWebp($webp, 'ICCP') !== null) {
            return $webp;
        }

        $chunks = substr($webp, 12);
        $iccp = 'ICCP'.pack('V', strlen($perfil)).$perfil.(strlen($perfil) % 2 ? "\0" : '');

        if (substr($chunks, 0, 4) === 'VP8X') {
            // Ya es extendido (con pérdida y transparencia): se marca el ICC.
            $cuerpo = substr($chunks, 0, 8).chr(ord($chunks[8]) | 0x20).substr($chunks, 9, 9).$iccp.substr($chunks, 18);
        } else {
            // Simple (VP8 o VP8L): en VP8L la transparencia es el bit 28 del encabezado.
            $alfa = substr($chunks, 0, 4) === 'VP8L' && ((unpack('V', substr($chunks, 9, 4))[1] >> 28) & 1) ? 0x10 : 0;
            $vp8x = 'VP8X'.pack('V', 10).chr(0x20 | $alfa)."\0\0\0"
                .substr(pack('V', $ancho - 1), 0, 3).substr(pack('V', $alto - 1), 0, 3);
            $cuerpo = $vp8x.$iccp.$chunks;
        }

        return 'RIFF'.pack('V', 4 + strlen($cuerpo)).'WEBP'.$cuerpo;
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
     * Fidelidad del WebP codificado contra la imagen, en PSNR (dB), sobre
     * una grilla de muestras: luminancia global, color (lo peor entre Cb y
     * Cr) y la peor zona de luminancia (percentil 1 de zonas de 8 × 8
     * muestras). Las zonas transparentes se comparan sobre blanco, que es
     * como se ven en el sitio.
     *
     * @return array{luma: float, color: float, zona: float}
     */
    private function fidelidad(GdImage $original, string $webp): array
    {
        $codificada = @imagecreatefromstring($webp);
        if (! $codificada instanceof GdImage) {
            return ['luma' => 0.0, 'color' => 0.0, 'zona' => 0.0];
        }

        $ancho = imagesx($original);
        $alto = imagesy($original);
        $paso = max(1, (int) floor(sqrt($ancho * $alto / self::MUESTRAS_PSNR)));
        $columnasZona = (int) ceil(ceil($ancho / $paso) / self::LADO_ZONA);

        $errorLuma = $errorCb = $errorCr = 0.0;
        $n = 0;
        $zonas = []; // índice => [suma de error de luma, muestras]
        for ($y = 0, $fila = 0; $y < $alto; $y += $paso, $fila++) {
            for ($x = 0, $columna = 0; $x < $ancho; $x += $paso, $columna++) {
                [$l1, $cb1, $cr1] = $this->ycbcr(imagecolorat($original, $x, $y));
                [$l2, $cb2, $cr2] = $this->ycbcr(imagecolorat($codificada, $x, $y));
                $d = ($l1 - $l2) ** 2;
                $errorLuma += $d;
                $errorCb += ($cb1 - $cb2) ** 2;
                $errorCr += ($cr1 - $cr2) ** 2;
                $n++;

                $zona = intdiv($fila, self::LADO_ZONA) * $columnasZona + intdiv($columna, self::LADO_ZONA);
                $zonas[$zona][0] = ($zonas[$zona][0] ?? 0.0) + $d;
                $zonas[$zona][1] = ($zonas[$zona][1] ?? 0) + 1;
            }
        }
        imagedestroy($codificada);

        $errorZonas = array_map(fn (array $z): float => $z[0] / $z[1], $zonas);
        rsort($errorZonas);
        $peor = $errorZonas[(int) floor((count($errorZonas) - 1) * self::PERCENTIL_ZONA)] ?? 0.0;

        return [
            'luma' => self::psnr($n > 0 ? $errorLuma / $n : 0.0),
            'color' => min(self::psnr($n > 0 ? $errorCb / $n : 0.0), self::psnr($n > 0 ? $errorCr / $n : 0.0)),
            'zona' => self::psnr($peor),
        ];
    }

    private static function psnr(float $mse): float
    {
        return $mse <= 1e-10 ? 99.0 : 10 * log10(255 ** 2 / $mse);
    }

    /**
     * Y, Cb y Cr (BT.601) del color compuesto sobre blanco.
     *
     * @return array{float, float, float}
     */
    private function ycbcr(int $color): array
    {
        $opacidad = 1 - (($color >> 24) & 0x7F) / 127;
        $r = (($color >> 16) & 0xFF) * $opacidad + 255 * (1 - $opacidad);
        $g = (($color >> 8) & 0xFF) * $opacidad + 255 * (1 - $opacidad);
        $b = ($color & 0xFF) * $opacidad + 255 * (1 - $opacidad);

        return [
            0.299 * $r + 0.587 * $g + 0.114 * $b,
            -0.168736 * $r - 0.331264 * $g + 0.5 * $b,
            0.5 * $r - 0.418688 * $g - 0.081312 * $b,
        ];
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
