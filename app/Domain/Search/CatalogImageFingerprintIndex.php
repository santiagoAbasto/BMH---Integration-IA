<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Catalog\Legacy\ProductImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Índice local de huellas visuales del catálogo.
 *
 * Resuelve el caso acotado y frecuente de una foto que proviene del propio
 * catálogo (descargada, reenviada o capturada). A diferencia del modelo de
 * visión, este índice compara contra TODAS las fotos conocidas antes de que
 * la búsqueda por atributos reduzca el universo.
 *
 * La huella no intenta identificar repuestos distintos con una forma parecida:
 * se usa un umbral estricto y, ante duda, devuelve cero resultados. Después el
 * resultado sigue pasando por la comparación de visión y requiere confirmación
 * del cliente; nunca equivale a un código de producto.
 */
final class CatalogImageFingerprintIndex
{
    // 2: la huella se calcula sobre el contenido recortado (ver contentSquare()).
    private const VERSION = 2;

    private const INDEX_PATH = 'app/ai-private/catalog-image-index.json';

    public function __construct(private readonly ProductImageService $images)
    {
    }

    /**
     * Reconstruye el índice a partir de las imágenes que la base realmente
     * referencia y que PHP puede leer en este servidor.
     *
     * @return array{indexed:int,missing:int,invalid:int,path:string}
     */
    public function rebuild(): array
    {
        $entries = [];
        $seen = [];
        $missing = 0;
        $invalid = 0;

        foreach (DB::connection('mysql_legacy')->table('imagenes')
            ->where('sector', 'producto')
            ->whereNotNull('producto_id')
            ->whereNotNull('path')
            ->orderBy('id')
            ->select('producto_id', 'path')
            ->cursor() as $row) {
            $filename = $this->images->current(trim((string) $row->path));

            if ($filename === null) {
                $missing++;

                continue;
            }

            $productId = (int) $row->producto_id;
            $key = $productId.'|'.$filename;

            if (isset($seen[$key])) {
                continue;
            }

            $hashes = $this->fingerprints($this->images->absolutePath($filename));

            if ($hashes === null) {
                $invalid++;

                continue;
            }

            $seen[$key] = true;
            $entries[] = [
                'product_id' => $productId,
                'path' => $filename,
                'average_hash' => $hashes['average_hash'],
                'difference_hash' => $hashes['difference_hash'],
            ];
        }

        $payload = json_encode([
            'version' => self::VERSION,
            'generated_at' => now()->toIso8601String(),
            'entries' => $entries,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $path = $this->indexPath();
        File::ensureDirectoryExists(dirname($path));
        $temporary = $path.'.tmp';
        file_put_contents($temporary, $payload, LOCK_EX);
        rename($temporary, $path);

        return [
            'indexed' => count($entries),
            'missing' => $missing,
            'invalid' => $invalid,
            'path' => $path,
        ];
    }

    /**
     * @return list<array{product_id:int,path:string,distance:int,strength:float}>
     */
    public function matches(string $customerImagePath): array
    {
        if (! (bool) config('bmh.vision.catalog_image_index.enabled', true)) {
            return [];
        }

        $needle = $this->fingerprints($customerImagePath);
        $index = $this->read();

        if ($needle === null || $index === []) {
            return [];
        }

        $maximum = max(0, min(128, (int) config('bmh.vision.catalog_image_index.max_distance', 8)));
        $maximumPerHash = max(0, min(64, (int) config('bmh.vision.catalog_image_index.max_distance_per_hash', 6)));
        $byProduct = [];

        foreach ($index as $entry) {
            $averageDistance = self::hamming($needle['average_hash'], $entry['average_hash']);
            $differenceDistance = self::hamming($needle['difference_hash'], $entry['difference_hash']);
            $distance = $averageDistance + $differenceDistance;

            // Las dos huellas tienen que estar cerca. Evita que dos fotos con
            // fondo blanco y silueta apenas parecida se tomen por iguales.
            if ($distance > $maximum || $averageDistance > $maximumPerHash || $differenceDistance > $maximumPerHash) {
                continue;
            }

            $match = [
                'product_id' => $entry['product_id'],
                'path' => $entry['path'],
                'distance' => $distance,
                'strength' => round(1 - ($distance / 128), 4),
            ];

            $current = $byProduct[$match['product_id']] ?? null;

            if ($current === null || $match['distance'] < $current['distance']) {
                $byProduct[$match['product_id']] = $match;
            }
        }

        $matches = array_values($byProduct);
        usort($matches, static fn (array $a, array $b): int => $a['distance'] <=> $b['distance']);

        return array_slice(
            $this->sortByFineSignature($customerImagePath, $matches),
            0,
            max(1, (int) config('bmh.vision.catalog_image_index.limit', 3)),
        );
    }

    /**
     * El hash decide QUIÉN pasa; la firma fina decide quién va PRIMERO.
     *
     * Con capturas reales de la web, la foto correcta quedaba primera sólo el
     * 40 % de las veces: sus variantes casi idénticas (una polea de otra, un
     * color de otro) quedaban a una distancia de hash parecida. Reordenando por
     * color y detalle sube al 63 %, sin dejar pasar nada nuevo.
     *
     * Las fuerzas se vuelven decrecientes en el nuevo orden para que el score
     * posterior lo respete; la de la primera no cambia (de ella depende la
     * confianza).
     *
     * @param  list<array{product_id:int,path:string,distance:int,strength:float}>  $matches
     * @return list<array{product_id:int,path:string,distance:int,strength:float}>
     */
    private function sortByFineSignature(string $customerImagePath, array $matches): array
    {
        if (count($matches) < 2) {
            return $matches;
        }

        $needle = $this->fineSignature($customerImagePath);

        if ($needle === null) {
            return $matches;
        }

        $pool = array_slice($matches, 0, 12);

        foreach ($pool as &$match) {
            $signature = $this->fineSignature($this->images->absolutePath($match['path']));
            $match['fine'] = $signature === null ? 255.0 : self::fineDistance($needle, $signature);
        }
        unset($match);

        usort($pool, static fn (array $a, array $b): int => [$a['fine'], $a['distance']] <=> [$b['fine'], $b['distance']]);

        $previous = null;

        foreach ($pool as &$match) {
            if ($previous !== null) {
                $match['strength'] = min($match['strength'], round($previous - 0.001, 4));
            }

            $previous = $match['strength'];
            unset($match['fine']);
        }
        unset($match);

        return $pool;
    }

    /** @return list<array{product_id:int,path:string,average_hash:string,difference_hash:string}> */
    private function read(): array
    {
        $path = $this->indexPath();

        if (! is_file($path)) {
            return [];
        }

        try {
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (($payload['version'] ?? null) !== self::VERSION || ! is_array($payload['entries'] ?? null)) {
            return [];
        }

        $entries = [];

        foreach ($payload['entries'] as $entry) {
            if (! is_array($entry)
                || ! is_int($entry['product_id'] ?? null)
                || ! is_string($entry['path'] ?? null)
                || ! $this->validHash($entry['average_hash'] ?? null)
                || ! $this->validHash($entry['difference_hash'] ?? null)) {
                continue;
            }

            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * Huella perceptual de un archivo de imagen. También la usan las
     * correcciones aprendidas para reconocer la misma captura recomprimida.
     *
     * @return array{average_hash:string,difference_hash:string}|null
     */
    public function fingerprints(string $path): ?array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $binary = @file_get_contents($path);
        $source = is_string($binary) ? @imagecreatefromstring($binary) : false;

        if ($source === false) {
            return null;
        }

        $content = $this->contentSquare($source);

        $fingerprints = [
            'average_hash' => $this->averageHash($content),
            'difference_hash' => $this->differenceHash($content),
        ];
        unset($content);
        // En PHP 8.5 GdImage se libera como objeto; imagedestroy() está
        // deprecado y no aporta nada. Soltar la referencia mantiene acotada
        // la memoria al construir miles de huellas.
        unset($source);

        return $fingerprints;
    }

    /**
     * Miniatura RGB de 16×16 del contenido recortado, para ORDENAR candidatos.
     *
     * El hash de 8×8 en blanco y negro acerca bien, pero no separa dos
     * variantes casi iguales (una polea de otra, un color de otro): en capturas
     * reales de la web, la foto correcta y su vecina quedaban a distancias
     * parecidas. Esta firma conserva el color y más detalle, y se calcula al
     * consultar sólo para los pocos candidatos que ya pasó el hash.
     *
     * @return list<int>|null 768 valores (16×16×3), o null si no se pudo leer
     */
    public function fineSignature(string $path): ?array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $binary = @file_get_contents($path);
        $source = is_string($binary) ? @imagecreatefromstring($binary) : false;

        if ($source === false) {
            return null;
        }

        $thumb = $this->thumbnail($this->contentSquare($source), 16, 16);
        $values = [];

        for ($y = 0; $y < 16; $y++) {
            for ($x = 0; $x < 16; $x++) {
                $color = imagecolorat($thumb, $x, $y);
                array_push($values, ($color >> 16) & 0xff, ($color >> 8) & 0xff, $color & 0xff);
            }
        }

        return $values;
    }

    /** Diferencia media absoluta entre dos firmas finas: 0 = idénticas, 255 = opuestas. */
    public static function fineDistance(array $left, array $right): float
    {
        $sum = 0;

        foreach ($left as $index => $value) {
            $sum += abs($value - $right[$index]);
        }

        return $sum / max(1, count($left));
    }

    /**
     * Sólo el CONTENIDO de la imagen, centrado en un cuadrado blanco.
     *
     * Una captura de la web trae márgenes blancos y el borde gris de la tarjeta
     * (en una captura real: 2 px de rgb 216,216,216); la foto del catálogo es
     * la pieza sola. Con la imagen entera esas dos no se parecen: en una
     * prueba con 200 fotos del catálogo, 0 de 200 capturas con marco se
     * reconocían. Recortar al contenido —y hacerlo igual al indexar y al
     * consultar— vuelve comparable lo que realmente es la misma foto.
     *
     * Contenido = lo más oscuro que rgb 200 en algún canal. Un renglón o
     * columna cuenta sólo con 2 o más puntos, para que un píxel suelto o el
     * ruido del JPEG no estire el recorte.
     */
    private function contentSquare(\GdImage $source): \GdImage
    {
        $scale = min(1.0, 192 / max(1, max(imagesx($source), imagesy($source))));
        $width = max(1, (int) round(imagesx($source) * $scale));
        $height = max(1, (int) round(imagesy($source) * $scale));
        $work = $this->thumbnail($source, $width, $height);

        $rows = array_fill(0, $height, 0);
        $columns = array_fill(0, $width, 0);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($work, $x, $y);

                if (min(($color >> 16) & 0xff, ($color >> 8) & 0xff, $color & 0xff) < 200) {
                    $rows[$y]++;
                    $columns[$x]++;
                }
            }
        }

        $yRange = $this->range($rows);
        $xRange = $this->range($columns);

        // Imagen en blanco o sin forma reconocible: se usa entera.
        if ($yRange === null || $xRange === null) {
            return $work;
        }

        [$x0, $x1] = $xRange;
        [$y0, $y1] = $yRange;
        $side = max($x1 - $x0 + 1, $y1 - $y0 + 1);
        $padding = (int) ceil($side * 0.04);
        $canvasSide = $side + 2 * $padding;

        $canvas = imagecreatetruecolor($canvasSide, $canvasSide);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy(
            $canvas,
            $work,
            (int) (($canvasSide - ($x1 - $x0 + 1)) / 2),
            (int) (($canvasSide - ($y1 - $y0 + 1)) / 2),
            $x0,
            $y0,
            $x1 - $x0 + 1,
            $y1 - $y0 + 1,
        );

        return $canvas;
    }

    /**
     * Primer y último índice con 2 o más puntos de contenido.
     *
     * @param  list<int>  $counts
     * @return array{0:int,1:int}|null
     */
    private function range(array $counts): ?array
    {
        $first = null;
        $last = null;

        foreach ($counts as $index => $count) {
            if ($count >= 2) {
                $first ??= $index;
                $last = $index;
            }
        }

        return $first === null ? null : [$first, $last];
    }

    private function averageHash(\GdImage $source): string
    {
        $thumb = $this->thumbnail($source, 8, 8);

        $values = $this->luminances($thumb, 8, 8);
        $average = array_sum($values) / count($values);
        $bits = '';

        foreach ($values as $value) {
            $bits .= $value >= $average ? '1' : '0';
        }

        $hash = $this->bitsToHex($bits);
        unset($thumb);

        return $hash;
    }

    private function differenceHash(\GdImage $source): string
    {
        $thumb = $this->thumbnail($source, 9, 8);

        $bits = '';

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $bits .= $this->luminance($thumb, $x, $y) >= $this->luminance($thumb, $x + 1, $y) ? '1' : '0';
            }
        }

        $hash = $this->bitsToHex($bits);
        unset($thumb);

        return $hash;
    }

    private function thumbnail(\GdImage $source, int $width, int $height): \GdImage
    {
        $thumb = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
        imagealphablending($thumb, true);

        imagecopyresampled(
            $thumb,
            $source,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($source),
            imagesy($source),
        );

        return $thumb;
    }

    /** @return list<float> */
    private function luminances(\GdImage $image, int $width, int $height): array
    {
        $values = [];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $values[] = $this->luminance($image, $x, $y);
            }
        }

        return $values;
    }

    private function luminance(\GdImage $image, int $x, int $y): float
    {
        $color = imagecolorat($image, $x, $y);
        $red = ($color >> 16) & 0xff;
        $green = ($color >> 8) & 0xff;
        $blue = $color & 0xff;

        return (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);
    }

    private function bitsToHex(string $bits): string
    {
        $hex = '';

        foreach (str_split($bits, 4) as $nibble) {
            $hex .= dechex(bindec($nibble));
        }

        return $hex;
    }

    private function validHash(mixed $hash): bool
    {
        return is_string($hash) && preg_match('/^[a-f0-9]{16}$/', $hash) === 1;
    }

    private function indexPath(): string
    {
        return storage_path(self::INDEX_PATH);
    }

    public static function hamming(string $left, string $right): int
    {
        $distance = 0;

        for ($index = 0; $index < strlen($left); $index++) {
            $distance += substr_count(decbin(hexdec($left[$index]) ^ hexdec($right[$index])), '1');
        }

        return $distance;
    }
}
