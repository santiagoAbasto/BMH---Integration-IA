<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Subcategoria;

class Caracteristica extends Model
{
    use HasFactory;
    
  


    protected $table = 'caracteristicas';

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_caracteristica');
    }
    
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_caracteristica', 'caracteristica_id', 'producto_id')
                    ->withPivot('valor', 'created_at', 'deleted_at');
    }

    
}
