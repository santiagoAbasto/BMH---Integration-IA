<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    public function archivo_xml(){
        return $this->hasOne('App\Models\ArchivoXml');
    }

    public function subtotal(){
        $productos = $this->productos()->get();
        $subtotal = 0;
    foreach($productos as $producto) {
    $descuentoProducto = $producto->descuento ?? 0;
    $descuentoCliente = Auth::guard('web')->user()->descuento ?? 0;

    $precioDescuentoProducto = $producto->precio() * (1 - ($descuentoProducto / 100));
    $precioConDescuentoCliente = $precioDescuentoProducto * (1 - ($descuentoCliente / 100));

    $subtotal += $precioConDescuentoCliente * $producto->pivot->cantidad;
}

        // return $subtotal * ( 1 - ($this->descuento_cliente / 100)) * ( 1 - ($this->descuento_pago / 100));
        return $subtotal;

    }

    public function bonificacion(){
        $productos = $this->productos()->get();
        $subtotal = 0;
        foreach($productos as $producto){
            $subtotal = $subtotal + ($producto->pivot->precio_descontado * $producto->pivot->cantidad);
        }

        $bonificacion = Bonificacion::where('desde', '<=', $subtotal)
        ->where('hasta', '>=', $subtotal)
        ->first();

        return $bonificacion ? $bonificacion->porcentaje : 0;
    }

    public function total_bonificacion(){
        $productos = $this->productos()->get();
        $subtotal = 0;
        foreach($productos as $producto){
            $subtotal = $subtotal + ($producto->pivot->precio_descontado * $producto->pivot->cantidad);
        }

        $bonificacion = Bonificacion::where('desde', '<=', $subtotal)
        ->where('hasta', '>=', $subtotal)
        ->first();

        if($bonificacion){
            if($bonificacion->porcentaje == 0){
                return number_format(0, 2 ,',','.');
            }
            $descuento = ($subtotal * $bonificacion->porcentaje) / 100;
            return number_format($descuento, 2 ,',','.');
            
        }else{
            return number_format(0, 2 ,',','.');
        }
            
            
            



    }

    public function subtotal_format(){
        return number_format($this->subtotal(), 2, ',', '.');
    }

    public function productos(){
        return $this->belongsToMany('App\Models\Producto')->withPivot('id', 'cantidad', 'descuento_producto', 'precio_unitario', 'precio_descontado');
    }
}
