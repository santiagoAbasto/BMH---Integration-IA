<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;
use App\Models\Dimension;

class Carrito extends Model
{
    use HasFactory;

    protected $table = 'carrito_informacion';

    public static function subtotal_format()
    {
        $subtotal = 0;

        foreach (Cart::content() as $item) {
            $producto = Producto::find($item->id);
            $descuento = Auth::guard('web')->user()->descuento;
            $precioConDescuento = $producto->precio_unitario_descontado();


         
            
            $subtotal += $precioConDescuento * $item->qty;

        }

        return number_format($subtotal, 2, ',', '.');
    }

    public static function iva()
    {

        $iva = Impuesto::find(1)->porcentaje;
        $subtotal = self::subtotal_format();
        $subtotal = str_replace(',', '.', str_replace('.', '', $subtotal));
        // $descuentoCliente = self::descuentoCliente();
        // $descuentoCliente = str_replace(',', '.', str_replace('.', '', $descuentoCliente));
        // $subtotal = $subtotal + $subtotal;


        $ivaDescuento = ($subtotal * $iva) / 100;


        return number_format($ivaDescuento, 2, ',', '.');
    }


    public static function descuentoCliente()
    {
        $subtotal = self::subtotal_format();
        $subtotal = str_replace(',', '.', str_replace('.', '', $subtotal));
        $descuento = Auth::guard('web')->user()->descuento;

        $descuentoCliente = $subtotal * ($descuento / 100);


        return number_format($descuentoCliente, 2, ',', '.');
    }


    public static function bonificacion()
    {

        $subtotal = self::subtotal_format();
        $subtotal = str_replace(',', '.', str_replace('.', '', $subtotal));

        $bonificacion = Bonificacion::where('desde', '<=', $subtotal)
            ->where('hasta', '>=', $subtotal)
            ->first();

        return $bonificacion ? $bonificacion->porcentaje : 0;
    }
    public static function total_bonificacion()
    {
        $bonificacion = self::bonificacion();
        $subtotal = self::subtotal_format();
        $subtotal = str_replace(',', '.', str_replace('.', '', $subtotal));

        if ($bonificacion) {
            if ($bonificacion == 0) {
                return number_format(0, 2, ',', '.');
            }
            $descuento = ($subtotal * $bonificacion) / 100;
            return number_format($descuento, 2, ',', '.');
        } else {
            return number_format(0, 2, ',', '.');
        }
    }

    public static function total_format()
    {
        $subtotal = self::subtotal_format();
        $iva = self::iva();
        // $descuentoCliente = self::descuentoCliente();



        $subtotal = str_replace(',', '.', str_replace('.', '', $subtotal));
        $iva = str_replace(',', '.', str_replace('.', '', $iva));
        // $descuentoCliente = str_replace(',', '.', str_replace('.', '', $descuentoCliente));

        $total = $subtotal + $iva;

        return number_format($total, 2, ',', '.');
    }

    public static function obtenerMensajeEnvio($tipoEnvio)
    {
        $carrito = self::find(1);
        switch ($tipoEnvio) {
            case 'Retiro cliente':
                return $carrito->info_retiro;
            case 'Reparto Iptsa':
                return $carrito->info_empresa;
            case 'A Convenir':
                return $carrito->info_convenir;
            default:
                return '';
        }
    }
}
