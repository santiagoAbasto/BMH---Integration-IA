<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Imagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * Los logos, el favicon y la imagen por defecto de los productos, guardados
 * como filas de `imagenes` por sector.
 *
 * Se mantuvieron los sectores históricos porque otras partes ya los leen:
 * `logo` es también el logo del login, y `logo2` el del sidebar del admin.
 *
 *  - header_transparente → sector `logo`   (Home, sobre el hero)
 *  - header_blanco       → sector `logo-header-blanco` (Nosotros y el resto).
 *                          Si nunca se subió, cae en el transparente, que es
 *                          lo que el sitio mostraba antes en todas las páginas.
 *  - footer              → sector `logo2`
 *  - favicon             → sector `favicon`. Si nunca se subió, cae en el
 *                          transparente, que era el favicon del sitio.
 *  - producto_defecto    → sector `producto-defecto`. Sin respaldo: si no hay
 *                          una propia, Producto::imagenPredeterminadaUrl() usa
 *                          el SVG con el logo que arma ProductoController.
 *
 * El contenedor lo registra como scoped: se resuelve una vez por request, así
 * que las cards de un listado comparten la misma consulta.
 */
final class LogosSitio
{
    public const HEADER_TRANSPARENTE = 'header_transparente';

    public const HEADER_BLANCO = 'header_blanco';

    public const FOOTER = 'footer';

    public const FAVICON = 'favicon';

    public const PRODUCTO_DEFECTO = 'producto_defecto';

    private const SECTORES = [
        self::HEADER_TRANSPARENTE => 'logo',
        self::HEADER_BLANCO => 'logo-header-blanco',
        self::FOOTER => 'logo2',
        self::FAVICON => 'favicon',
        self::PRODUCTO_DEFECTO => 'producto-defecto',
    ];

    private const RESPALDO = [
        self::HEADER_BLANCO => self::HEADER_TRANSPARENTE,
        // Hasta ahora el favicon era el logo: mientras no se suba uno propio se
        // sigue usando, porque public_html/favicon.ico está vacío en producción.
        self::FAVICON => self::HEADER_TRANSPARENTE,
    ];

    /** @var array<string, string>|null sector => path, cargado una sola vez */
    private ?array $paths = null;

    /** @var array<string, string|null> clave => url, para no repetir el is_file por card */
    private array $urlsVerificadas = [];

    private string $directorio;

    public function __construct(?string $directorio = null)
    {
        $this->directorio = $directorio ?? public_path('imagenes');
    }

    public function url(string $clave): string
    {
        $path = $this->path($clave);

        if ($path !== null) {
            return asset('imagenes/'.$path);
        }

        return $clave === self::FAVICON ? asset('favicon.ico') : '';
    }

    /**
     * La URL del archivo propio de la clave, sin respaldos, y sólo si el
     * archivo existe en disco: una fila que apunta a un archivo borrado no
     * puede terminar en una imagen rota en todas las cards.
     */
    public function urlSiExiste(string $clave): ?string
    {
        if (array_key_exists($clave, $this->urlsVerificadas)) {
            return $this->urlsVerificadas[$clave];
        }

        $path = $this->paths()[self::SECTORES[$clave]] ?? null;
        $existe = $path !== null && is_file($this->directorio.DIRECTORY_SEPARATOR.$path);

        return $this->urlsVerificadas[$clave] = $existe ? asset('imagenes/'.$path) : null;
    }

    /** Devuelve el archivo del logo embebido para usarlo dentro de un SVG. */
    public function dataUri(string $clave): string
    {
        $path = $this->path($clave);
        if ($path === null) {
            return '';
        }

        $archivo = $this->directorio.DIRECTORY_SEPARATOR.$path;
        if (! is_file($archivo)) {
            return '';
        }

        $mime = File::mimeType($archivo) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(File::get($archivo));
    }

    /** ¿Tiene archivo propio, o está usando el de respaldo? */
    public function tienePropio(string $clave): bool
    {
        return isset($this->paths()[self::SECTORES[$clave]]);
    }

    /**
     * Guarda el archivo nuevo y recién después borra el anterior, y sólo si
     * ninguna otra fila de `imagenes` lo sigue usando.
     */
    public function reemplazar(string $clave, UploadedFile $archivo): void
    {
        $sector = self::SECTORES[$clave];

        $imagen = Imagen::query()->where('sector', $sector)->first() ?? new Imagen();
        $imagen->sector = $sector;
        $anterior = $imagen->path;

        $nombre = 'media_'.uniqid().'.'.($archivo->extension() ?: $archivo->getClientOriginalExtension());
        $archivo->move($this->directorio, $nombre);

        $imagen->path = $nombre;
        $imagen->save();

        $this->borrarSiNadieLoUsa($anterior);
        $this->olvidarPaths();
    }

    /**
     * Quita el archivo propio de la clave: la fila se borra y el archivo
     * también, salvo que otra fila de `imagenes` lo siga usando. Después de
     * esto la clave vuelve a su respaldo.
     */
    public function quitar(string $clave): void
    {
        $imagen = Imagen::query()->where('sector', self::SECTORES[$clave])->first();

        if ($imagen === null) {
            return;
        }

        $imagen->delete();

        $this->borrarSiNadieLoUsa($imagen->path);
        $this->olvidarPaths();
    }

    private function borrarSiNadieLoUsa(?string $path): void
    {
        if ($path && ! Imagen::query()->where('path', $path)->exists()) {
            File::delete($this->directorio.DIRECTORY_SEPARATOR.$path);
        }
    }

    private function olvidarPaths(): void
    {
        $this->paths = null;
        $this->urlsVerificadas = [];
    }

    private function path(string $clave): ?string
    {
        return $this->paths()[self::SECTORES[$clave]]
            ?? (isset(self::RESPALDO[$clave]) ? $this->path(self::RESPALDO[$clave]) : null);
    }

    /** @return array<string, string> */
    private function paths(): array
    {
        return $this->paths ??= Imagen::query()
            ->whereIn('sector', array_values(self::SECTORES))
            ->pluck('path', 'sector')
            ->all();
    }
}
