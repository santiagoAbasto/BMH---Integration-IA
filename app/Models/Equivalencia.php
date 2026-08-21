<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equivalencia extends Model
{
    use HasFactory;

    protected $table = 'equivalencias';


    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
