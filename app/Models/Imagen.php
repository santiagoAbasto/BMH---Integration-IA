<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    use HasFactory;

    protected $table = 'imagenes';

    public function top(){
        return strstr($this->posicion, ',', true);
    }

    public function left(){
        return substr(strstr($this->posicion, ','),1);
    }
}
