<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcategoriaProducto extends Model
{
    use HasFactory;

    protected $table = 'subcategoria_producto';

    public function subcategoria(){
        return $this->belongsTo('App\Models\Subcategoria');
    }

    public function producto(){
        return $this->belongsTo('App\Models\Producto');
    }
}
