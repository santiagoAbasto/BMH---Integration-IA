<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Imagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * Los tres logos del sitio, guardados como filas de `imagenes` por sector.
 *
 * Se mantuvieron los sectores históricos porque otras partes ya los leen:
 * `logo` es también el favicon del front y el logo del login, y `logo2` el del
 * sidebar del admin.
 *
 *  - header_transparente → sector `logo`   (Home, sobre el hero)
 *  - header_blanco       → sector `logo-header-blanco` (Nosotros y el resto).
 *                          Si nunca se subió, cae en el transparente, que es
 *                          lo que el sitio mostraba antes en todas las páginas.
 *  - footer              → sector `logo2`
 */
final class LogosSitio
{
    public const HEADER_TRANSPARENTE = 'header_transparente';

    public const HEADER_BLANCO = 'header_blanco';

    public const FOOTER = 'footer';

    private const SECTORES = [
        self::HEADER_TRANSPARENTE => 'logo',
        self::HEADER_BLANCO => 'logo-header-blanco',
        self::FOOTER => 'logo2',
    ];

    private const RESPALDO = [
        self::HEADER_BLANCO => self::HEADER_TRANSPARENTE,
    ];

    /** @var array<string, string>|null sector => path, cargado una sola vez */
    private ?array $paths = null;

    private string $directorio;

    public function __construct(?string $directorio = null)
    {
        $this->directorio = $directorio ?? public_path('imagenes');
    }

    public function url(string $clave): string
    {
        $path = $this->path($clave);

        return $path === null ? '' : asset('imagenes/'.$path);
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

        if ($anterior && ! Imagen::query()->where('path', $anterior)->exists()) {
            File::delete($this->directorio.DIRECTORY_SEPARATOR.$anterior);
        }

        $this->paths = null;
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
