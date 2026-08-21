<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCaracteristica extends Model
{
    protected $table = 'producto_caracteristica';
    
    protected $fillable = [
        'producto_id',
        'caracteristica_id', 
        'valor'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function caracteristica()
    {
        return $this->belongsTo(Caracteristica::class, 'caracteristica_id');
    }
}