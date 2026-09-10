<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caracteristica extends Model
{
    use HasFactory;

    protected $table = 'caracteristicas';

    protected $fillable = [
        'orden',
        'nombre',
    ];

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_caracteristica');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_caracteristica', 'caracteristica_id', 'producto_id')
            ->withPivot('valor', 'created_at', 'deleted_at');
    }

    /**
     * Filtro del buscador del dashboard. Sin término devuelve la query intacta.
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return $query;
        }

        // Se escapan los comodines para que un '%' tipeado no traiga todo.
        $patron = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $termino).'%';

        return $query->where('nombre', 'like', $patron);
    }

    /** Criterios de orden del listado del dashboard (query string `ordenar`). */
    public const ORDEN_CAMPO = 'orden';

    public const ORDEN_AZ = 'az';

    public const ORDEN_ZA = 'za';

    public const CRITERIOS_DE_ORDEN = [self::ORDEN_CAMPO, self::ORDEN_AZ, self::ORDEN_ZA];

    /**
     * Orden del listado del dashboard.
     *
     * Por campo `orden` va ascendente, como en el resto del proyecto, y las
     * filas sin orden cargado van al final: hoy son todas, y sin eso el listado
     * quedaba en orden de inserción. En todos los criterios se desempata por
     * nombre e id para que la paginación sea estable.
     */
    public function scopeOrdenadoPor(Builder $query, string $criterio): Builder
    {
        return match ($criterio) {
            self::ORDEN_AZ => $query->orderBy('nombre')->orderBy('id'),
            self::ORDEN_ZA => $query->orderByDesc('nombre')->orderByDesc('id'),
            default => $query
                ->orderByRaw("COALESCE(orden, '') = ''")
                ->orderBy('orden')
                ->orderBy('nombre')
                ->orderBy('id'),
        };
    }

    /**
     * ¿Existe otra característica con este nombre? Excluye la propia al editar.
     */
    public static function nombreOcupado(string $nombre, ?int $exceptoId = null): bool
    {
        return static::query()
            ->where('nombre', $nombre)
            ->when($exceptoId !== null, fn (Builder $q) => $q->whereKeyNot($exceptoId))
            ->exists();
    }
}
