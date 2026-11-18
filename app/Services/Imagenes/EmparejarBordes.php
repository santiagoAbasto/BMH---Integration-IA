<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use GdImage;
use RuntimeException;

/**
 * «Emparejar bordes»: la foto con el producto centrado y el mismo margen que las demás.
 *
 * Busca el producto sobre el fondo (el fondo se estima con los colores de las cuatro esquinas: sirve para fondos
 * lisos y con degradé), lo centra en un cuadrado con el tamaño del perfil y completa alrededor estirando el borde de
 * la propia foto, así el fondo sigue igual. La foto original no se toca: el resultado es otro archivo,
 * «{nombre}-parejo.webp», y se puede volver a la original (original()).
 *
 * Perfiles:
 *  - producto: la ficha muestra la foto entera en un cuadrado; el producto ocupa el 84% del lado más largo.
 *  - categoria: la tarjeta recorta la foto a 5:4; el producto queda dentro de la zona que se ve, con el mismo «peso»
 *    (superficie) en todas.
 *
 * No se empareja (y se dice por qué) si el fondo no es liso (una escena: las esquinas de un mismo lado son de colores
 * distintos) o si el producto ya llena toda la foto.
 */
final class EmparejarBordes
{
    public const PRODUCTO = 'producto';

    public const CATEGORIA = 'categoria';

    public const SUFIJO = '-parejo.webp';

    private const ANALISIS = 320;

    private const EXTENSIONES = ['webp', 'png', 'jpg', 'jpeg'];

    public function __construct(private readonly ?string $directorio = null)
    {
    }

    public static function esPareja(string $nombre): bool
    {
        return str_ends_with($nombre, self::SUFIJO);
    }

    /**
     * Empareja public/imagenes/{nombre} y devuelve el nombre del archivo nuevo. Si ya es una foto emparejada, la
     * vuelve a hacer desde la original.
     *
     * @throws NoSePuedeEmparejar con el motivo, para mostrárselo al admin
     */
    public function emparejar(string $nombre, string $perfil): string
    {
        $nombre = basename($nombre);
        $origen = self::esPareja($nombre) ? ($this->original($nombre) ?? throw new NoSePuedeEmparejar('No se encontró la foto original.')) : $nombre;
        $ruta = $this->ruta($origen);
        $info = @getimagesize($ruta);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            throw new NoSePuedeEmparejar('No es una imagen JPG, PNG o WebP.');
        }
        if ($info[0] * $info[1] > 40_000_000) {
            throw new NoSePuedeEmparejar('La imagen es demasiado grande para procesarla.');
        }

        $imagen = $this->cargar($ruta, $info[2]);
        try {
            $lienzo = $this->componer($imagen, $perfil);
        } finally {
            imagedestroy($imagen);
        }

        $destino = pathinfo($origen, PATHINFO_FILENAME).self::SUFIJO;
        try {
            if (! imagewebp($lienzo, $this->ruta($destino), 88)) {
                throw new RuntimeException('No se pudo guardar la foto emparejada.');
            }
        } finally {
            imagedestroy($lienzo);
        }

        return $destino;
    }

    /** La foto original de una emparejada («x-parejo.webp» → «x.png», la que exista), o null. */
    public function original(string $pareja): ?string
    {
        if (! self::esPareja($pareja)) {
            return null;
        }
        $base = substr(basename($pareja), 0, -strlen(self::SUFIJO));
        foreach (self::EXTENSIONES as $extension) {
            if (is_file($this->ruta($base.'.'.$extension))) {
                return $base.'.'.$extension;
            }
        }

        return null;
    }

    /**
     * La foto y su compañera: la original y la emparejada (las que existan). Para borrar las dos juntas cuando la foto
     * se reemplaza.
     *
     * @return list<string>
     */
    public function familia(string $nombre): array
    {
        $nombre = basename($nombre);
        $otra = self::esPareja($nombre) ? $this->original($nombre) : pathinfo($nombre, PATHINFO_FILENAME).self::SUFIJO;

        return array_values(array_filter([$nombre, $otra], fn (?string $n): bool => $n !== null && is_file($this->ruta($n))));
    }

    // ------------------------------------------------------------------ composición

    private function componer(GdImage $imagen, string $perfil): GdImage
    {
        $W = imagesx($imagen);
        $H = imagesy($imagen);
        $a = $this->analizar($imagen);
        [$x0, $y0, $x1, $y1] = $a['caja'];
        $bw = $x1 - $x0;
        $bh = $y1 - $y0;

        $toca = [
            'izq' => $x0 <= $W * 0.012, 'der' => $x1 >= $W * 0.988,
            'arr' => $y0 <= $H * 0.012, 'aba' => $y1 >= $H * 0.988,
        ];
        if ($toca['izq'] && $toca['der'] && $toca['arr'] && $toca['aba']) {
            throw new NoSePuedeEmparejar('El producto ya ocupa toda la foto: no hay fondo alrededor para emparejar.');
        }

        $costado = $toca['izq'] || $toca['der'];
        if ($perfil === self::CATEGORIA) {
            $lado = 1080;
            $s = min(($costado ? 0.99 : 0.68) * $lado / $bw, 0.54 * $lado / $bh);
            if (! $costado) {
                $s = min($s, 0.52 * $lado / sqrt($bw * $bh));
            }
        } else {
            // Sin agrandar de más: el lado sale del tamaño del producto (entre 800 y 1400 px).
            $lado = (int) max(800, min(1400, round(max($bw, $bh) / 0.84)));
            $s = min(($costado ? 0.99 : 0.84) * $lado / $bw, 0.84 * $lado / $bh);
        }

        $rw = max(1, (int) round($W * $s));
        $rh = max(1, (int) round($H * $s));
        $ox = (int) round($lado / 2 - ($x0 + $x1) / 2 * $s);
        $oy = (int) round($lado / 2 - ($y0 + $y1) / 2 * $s);
        if ($toca['izq']) {
            $ox = 0;
        } elseif ($toca['der']) {
            $ox = $lado - $rw;
        }

        $lienzo = imagecreatetruecolor($lado, $lado);
        imagealphablending($lienzo, false);
        imagesavealpha($lienzo, true);

        // Fondo: la foto en chico (promedia el ruido) y sus bordes estirados hasta el borde del cuadrado.
        $chica = $this->achicar($imagen, max(1, intdiv($W, 8)), max(1, intdiv($H, 8)));
        $cw = imagesx($chica);
        $ch = imagesy($chica);
        $izq = max(0, $ox);
        $arr = max(0, $oy);
        $der = max(0, $lado - ($ox + $rw));
        $aba = max(0, $lado - ($oy + $rh));
        $copiar = static function (int $dx, int $dy, int $dw, int $dh, int $sx, int $sy, int $sw, int $sh) use ($lienzo, $chica): void {
            if ($dw > 0 && $dh > 0) {
                imagecopyresampled($lienzo, $chica, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh);
            }
        };
        $copiar($ox, $oy, $rw, $rh, 0, 0, $cw, $ch);                       // debajo de la foto
        $copiar(0, $oy, $izq, $rh, 0, 0, 1, $ch);                          // izquierda
        $copiar($ox + $rw, $oy, $der, $rh, $cw - 1, 0, 1, $ch);            // derecha
        $copiar($ox, 0, $rw, $arr, 0, 0, $cw, 1);                          // arriba
        $copiar($ox, $oy + $rh, $rw, $aba, 0, $ch - 1, $cw, 1);            // abajo
        $copiar(0, 0, $izq, $arr, 0, 0, 1, 1);                             // esquinas
        $copiar($ox + $rw, 0, $der, $arr, $cw - 1, 0, 1, 1);
        $copiar(0, $oy + $rh, $izq, $aba, 0, $ch - 1, 1, 1);
        $copiar($ox + $rw, $oy + $rh, $der, $aba, $cw - 1, $ch - 1, 1, 1);
        imagedestroy($chica);

        imagecopyresampled($lienzo, $imagen, $ox, $oy, 0, 0, $rw, $rh, $W, $H);

        return $lienzo;
    }

    /**
     * Dónde está el producto (en píxeles de la original) y si el fondo permite emparejar.
     *
     * @return array{caja: array{int, int, int, int}}
     */
    private function analizar(GdImage $imagen): array
    {
        $W = imagesx($imagen);
        $H = imagesy($imagen);
        $escala = min(1, self::ANALISIS / max($W, $H));
        $w = max(8, (int) round($W * $escala));
        $h = max(8, (int) round($H * $escala));
        $chica = $this->achicar($imagen, $w, $h);

        $px = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($chica, $x, $y);
                $px[$y][$x] = [$c >> 16 & 0xFF, $c >> 8 & 0xFF, $c & 0xFF, $c >> 24 & 0x7F];
            }
        }
        imagedestroy($chica);

        $k = max(3, intdiv($w, 60));
        $esquina = static function (int $x0, int $y0) use ($px, $k): array {
            $s = [0, 0, 0, 0];
            for ($y = $y0; $y < $y0 + $k; $y++) {
                for ($x = $x0; $x < $x0 + $k; $x++) {
                    for ($i = 0; $i < 4; $i++) {
                        $s[$i] += $px[$y][$x][$i];
                    }
                }
            }

            return array_map(static fn ($v) => $v / ($k * $k), $s);
        };
        $tl = $esquina(0, 0);
        $tr = $esquina($w - $k, 0);
        $bl = $esquina(0, $h - $k);
        $br = $esquina($w - $k, $h - $k);

        $transparente = ($tl[3] + $tr[3] + $bl[3] + $br[3]) / 4 > 100;
        $dif = static fn (array $a, array $b): float => max(abs($a[0] - $b[0]), abs($a[1] - $b[1]), abs($a[2] - $b[2]));
        // Un degradé de arriba hacia abajo está bien; colores distintos a los costados es una escena.
        if (! $transparente && ($dif($tl, $tr) > 40 || $dif($bl, $br) > 40)) {
            throw new NoSePuedeEmparejar('El fondo no es liso (parece una escena): no se puede emparejar sin cortar algo.');
        }

        $cols = array_fill(0, $w, 0);
        $filas = array_fill(0, $h, 0);
        for ($y = 0; $y < $h; $y++) {
            $t = $h > 1 ? $y / ($h - 1) : 0;
            for ($x = 0; $x < $w; $x++) {
                $p = $px[$y][$x];
                if ($transparente) {
                    $distinto = $p[3] < 100;
                } else {
                    $s = $w > 1 ? $x / ($w - 1) : 0;
                    $f0 = ($tl[0] * (1 - $s) + $tr[0] * $s) * (1 - $t) + ($bl[0] * (1 - $s) + $br[0] * $s) * $t;
                    $f1 = ($tl[1] * (1 - $s) + $tr[1] * $s) * (1 - $t) + ($bl[1] * (1 - $s) + $br[1] * $s) * $t;
                    $f2 = ($tl[2] * (1 - $s) + $tr[2] * $s) * (1 - $t) + ($bl[2] * (1 - $s) + $br[2] * $s) * $t;
                    $distinto = max(abs($p[0] - $f0), abs($p[1] - $f1), abs($p[2] - $f2)) > 22;
                }
                if ($distinto) {
                    $cols[$x]++;
                    $filas[$y]++;
                }
            }
        }

        $xs = array_keys(array_filter($cols, static fn ($n) => $n > $h * 0.006));
        $ys = array_keys(array_filter($filas, static fn ($n) => $n > $w * 0.006));
        if ($xs === [] || $ys === []) {
            throw new NoSePuedeEmparejar('No se distingue el producto del fondo.');
        }

        $fx = $W / $w;
        $fy = $H / $h;

        return ['caja' => [
            (int) floor(min($xs) * $fx), (int) floor(min($ys) * $fy),
            (int) min($W, ceil((max($xs) + 1) * $fx)), (int) min($H, ceil((max($ys) + 1) * $fy)),
        ]];
    }

    private function achicar(GdImage $imagen, int $w, int $h): GdImage
    {
        $chica = imagecreatetruecolor($w, $h);
        imagealphablending($chica, false);
        imagesavealpha($chica, true);
        imagecopyresampled($chica, $imagen, 0, 0, 0, 0, $w, $h, imagesx($imagen), imagesy($imagen));

        return $chica;
    }

    private function cargar(string $ruta, int $tipo): GdImage
    {
        $imagen = match ($tipo) {
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_WEBP => @imagecreatefromwebp($ruta),
        };
        if (! $imagen instanceof GdImage) {
            throw new NoSePuedeEmparejar('No se pudo leer la imagen.');
        }
        if (! imageistruecolor($imagen)) {
            imagepalettetotruecolor($imagen);
        }
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        return $imagen;
    }

    private function ruta(string $nombre): string
    {
        return rtrim($this->directorio ?? (string) config('imagenes.directorio', public_path('imagenes')), '/\\').DIRECTORY_SEPARATOR.$nombre;
    }
}
