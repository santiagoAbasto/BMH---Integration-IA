<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uso extends Model
{
    use HasFactory;

    protected $table = 'usos';

    public function productos(){
        return $this->belongsToMany('App\Models\Producto');
    }
}
