<?php

namespace App\Models;

use App\Support\Seo\SeoPages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Metadatos propios de una página general (admin → Metadatos SEO), una fila por `seccion`.
 * Un campo vacío usa el valor sugerido (App\Support\Seo\SeoPages).
 */
class Metadatos extends Model
{
    use HasFactory;

    protected $table = 'metadatos';

    protected $fillable = ['seccion', 'titulo', 'keyword', 'descripcion'];

    protected static function booted(): void
    {
        self::saved(fn () => SeoPages::forget());
        self::deleted(fn () => SeoPages::forget());
    }
}
