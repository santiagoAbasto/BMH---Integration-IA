<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    /** Renglones ya calculados (la vista los pide varias veces). @var \Illuminate\Support\Collection|null */
    private $lineasCache = null;

    public function archivo_xml(){
        return $this->hasOne('App\Models\ArchivoXml');
    }

    /** Descuento del cliente guardado en el pedido (el que tenía cuando lo hizo). */
    public function descuentoCliente(): float
    {
        return (float) ($this->descuento_cliente ?? 0);
    }

    /**
     * Renglones del pedido como se hizo: precio de lista y descuentos del
     * momento de la compra (guardados en pedido_producto), no los de hoy ni
     * los de quien está mirando el pedido. Antes se usaba el descuento del
     * usuario logueado: desde el admin (sin cliente logueado) el subtotal y
     * el IVA salían sin el descuento del cliente y no cerraban con el total.
     *
     * @return \Illuminate\Support\Collection<int, object{producto: Producto, precio_lista: float, descuento_producto: float, descuento_cliente: float, precio_neto: float, cantidad: int, subtotal: float}>
     */
    public function lineas(): \Illuminate\Support\Collection
    {
        if ($this->lineasCache !== null) {
            return $this->lineasCache;
        }

        $descuentoCliente = $this->descuentoCliente();

        return $this->lineasCache = $this->productos()->get()->map(function (Producto $producto) use ($descuentoCliente) {
            $precioLista = (float) $producto->pivot->precio_unitario;
            if ($precioLista <= 0) {
                $precioLista = (float) ($producto->precio() ?? 0); // pedidos sin el precio guardado
            }
            $descuentoProducto = (float) ($producto->pivot->descuento_producto ?? 0);
            $cantidad = (int) ($producto->pivot->cantidad ?? 0);
            $precioNeto = $precioLista * (1 - $descuentoProducto / 100) * (1 - $descuentoCliente / 100);

            return (object) [
                'producto' => $producto,
                'precio_lista' => $precioLista,
                'descuento_producto' => $descuentoProducto,
                'descuento_cliente' => $descuentoCliente,
                'precio_neto' => $precioNeto,
                'cantidad' => $cantidad,
                'subtotal' => $precioNeto * $cantidad,
            ];
        });
    }

    public function subtotal(): float
    {
        return (float) $this->lineas()->sum('subtotal');
    }

    public function porcentajeIva(): float
    {
        return (float) (Impuesto::find(1)?->porcentaje ?? 21);
    }

    public function iva(): float
    {
        // Como el carrito: sobre el subtotal ya redondeado.
        return round(round($this->subtotal(), 2) * $this->porcentajeIva() / 100, 2);
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
