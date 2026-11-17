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

    /**
     * El mensaje del pedido enviado se escribe como texto (una línea en blanco separa párrafos) y se guarda como HTML
     * escapado, porque el carrito lo imprime sin escapar.
     */
    public static function htmlDeTexto(string $texto): string
    {
        $parrafos = preg_split('/\R\s*\R/u', trim(str_replace("\r\n", "\n", $texto))) ?: [];

        return collect($parrafos)
            ->map(fn (string $p) => trim($p))
            ->filter()
            ->map(fn (string $p) => '<p>'.nl2br(e($p), false).'</p>')
            ->implode('');
    }

    /** El HTML guardado (del editor anterior o de htmlDeTexto) como texto para editarlo. */
    public static function textoPlano(?string $html): string
    {
        $texto = preg_replace(['#<br\s*/?>#i', '#</(p|div|li)>#i'], ["\n", "\n\n"], (string) $html);
        $texto = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace(["/[ \t\x{00A0}]+/u", "/ *\n */", "/\n{3,}/"], [' ', "\n", "\n\n"], $texto);

        return trim((string) $texto);
    }

    public static function subtotal_format()
    {
        $subtotal = 0;

        foreach (Cart::content() as $item) {
            $producto = Producto::find($item->id);
            if (! $producto) {
                continue; // borrado del admin mientras estaba en el carrito
            }
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
