<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Dimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'diametro',
        'largo',
        'altura_cuadrado',
        'altura_cabeza',
        'diametro_cabeza',
        'producto_id',
        'precio',
        'unidad'
    ];

    protected $table = 'dimensiones';

    public function producto(){
        return $this->belongsTo('App\Models\Producto');
    }

    public function pedidos(){
        return $this->belongsToMany('App\Models\Pedido');
    }

    public function precio_unitario_format(){
        $precio = number_format($this->precio, 2, ',','.');
        return $precio;
    }

    public function precio_paquete_format(){
        $precio = number_format(($this->precio * $this->unidad), 2, ',','.');
        return $precio;
    }

    public function precio_descontado_format($desc_cliente, $desc_categoria, $desc_producto){
        $precio = number_format((($this->precio * $this->unidad) * (1 - ($desc_cliente / 100)) * (1 - ($desc_categoria / 100)) * (1 - ($desc_producto / 100))), 2, ',','.');
        return $precio;
    }

    public function precio_unitario_descontado(){
        $precio = $this->precio * (1 - (Auth::guard('web')->user()->descuento / 100)) * (1 - ($this->producto()->first()->categoria()->first()->descuento / 100)) * (1 - (0 / 100));
        return $precio;
    }

    public function subtotal_format($qty){
        $subtotal = number_format($this->precio * $qty, 2 ,',','.');
        return $subtotal;
    }

    public function subtotal_descontado_format($qty){
        $subtotal = number_format($this->precio_unitario_descontado() * $qty, 2 ,',','.');
        return $subtotal;
    }

    public function descuentos_format($qty){
        $descuentos = number_format(($this->precio * $qty) - ($this->precio_unitario_descontado() * $qty), 2, ',', '.');
        return $descuentos;
    }
}
