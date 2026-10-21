<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Una imagen de la biblioteca: un archivo de public/imagenes con su código.
 * Ver App\Services\Imagenes\BibliotecaImagenes.
 */
class ImagenBiblioteca extends Model
{
    protected $table = 'biblioteca_imagenes';

    protected $fillable = ['codigo', 'path', 'nombre_original', 'bytes', 'ancho', 'alto'];

    protected $casts = [
        'bytes' => 'integer',
        'ancho' => 'integer',
        'alto' => 'integer',
    ];

    public function getUrlAttribute(): string
    {
        return asset('imagenes/'.$this->path);
    }

    /** Por código de imagen o nombre de archivo original. */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        $termino = trim((string) $termino);
        if ($termino === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($termino): void {
            $q->where('codigo', 'like', "%{$termino}%")
                ->orWhere('nombre_original', 'like', "%{$termino}%");
        });
    }
}
