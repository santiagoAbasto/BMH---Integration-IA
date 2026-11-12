<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Seo\SeoPages;
use Illuminate\Database\Eloquent\Model;

/** Ajustes SEO del sitio (una fila): verificación de buscadores e imagen para compartir propia. */
final class SeoSetting extends Model
{
    /** Imagen creada para el sitio (1200 × 630). Se usa mientras no se suba una propia en el admin. */
    public const DEFAULT_IMAGE = 'img/bmh-compartir.jpg';

    /** Carpeta (dentro de public/imagenes) de la imagen subida en el admin. */
    public const DIRECTORY = 'seo';

    protected $table = 'seo_settings';

    protected $fillable = ['google_verificacion', 'bing_verificacion', 'imagen_path'];

    protected static function booted(): void
    {
        self::saved(fn () => SeoPages::forget());
        self::deleted(fn () => SeoPages::forget());
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([]);
    }

    /** URL absoluta de la imagen para compartir: la subida en el admin o la del sitio. */
    public function imageUrl(): string
    {
        return $this->imagen_path && is_file(public_path('imagenes/'.$this->imagen_path))
            ? asset('imagenes/'.$this->imagen_path)
            : asset(self::DEFAULT_IMAGE);
    }
}
