<?php

namespace App\Models;

use App\View\Composers\MenuProductosComposer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Categoria extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // El menú de «Productos» del header muestra las categorías desde caché.
        $limpiarMenu = fn () => Cache::forget(MenuProductosComposer::CACHE);
        static::saved($limpiarMenu);
        static::deleted($limpiarMenu);
    }

    protected $table = 'categorias';

    /**
     * Las características que usan los productos de la categoría: la ficha que se muestra en el sitio y
     * los filtros del catálogo. Ordenadas por el `orden` de la característica.
     */
    public function caracteristicas(): BelongsToMany
    {
        return $this->belongsToMany(Caracteristica::class, 'caracteristica_categoria')
            ->withTimestamps()
            ->ordenadas();
    }

    public function subcategorias(){
        return Subcategoria::where('categoria_id', $this->id)->get();
    }

    public function productos(){
        return $this->hasMany('App\Models\Producto');
    }

}
