<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivoXml extends Model
{
    use HasFactory;

    protected $table = 'xml';

    public function pedido(){
        return $this->belongsTo('App\Models\Pedido');
    }
}
