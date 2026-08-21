<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleXMLElement;
use App\Models\ArchivoXml;
use App\Models\Producto;
use App\Models\Impuesto;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use CodersFree\Shoppingcart\Facades\Cart;

class XmlController extends Controller
{
    public function crear($pedido, $datos){
        date_default_timezone_set('America/Buenos_Aires');
        $fecha = date('d/m/Y');
        $total_sin_envio = $pedido->total_pedido - $pedido->costo_envio;
        $importe_descuento = $datos['subtotal_pedido'] - $total_sin_envio;
        $descuento = (($importe_descuento) * 100) / $datos['subtotal_pedido'];
        $iva = Impuesto::find(1)->porcentaje;
        $parcial = $datos['subtotal_pedido'];
        $username = isset($pedido->cliente_id) ? User::find($pedido->cliente_id)->username : null;

        $xmlString = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>

        <ORDENDEVENTA>
        
        <CABECERA>
        
        <COD_CLIENTE>$pedido->cliente_id</COD_CLIENTE>
        
        <RAZON_SOCIAL>$pedido->nombre</RAZON_SOCIAL>
        
        <CUIT>$pedido->dni</CUIT>
        
        <FECHA>$fecha</FECHA>
        
        <O_CPRA>$pedido->id</O_CPRA>
        
        <VENDEDOR></VENDEDOR>
        
        <OBRA></OBRA>
        
        <USUARIO>$username</USUARIO>
        
        <COND_VTA>0</COND_VTA>
        
        <REFERENCIA>$pedido->id</REFERENCIA>
        
        <PARCIAL>$parcial</PARCIAL>
        
        <PDESC>$descuento</PDESC>
        
        <IDESC>$importe_descuento</IDESC>
        
        <SUBTOT>$parcial</SUBTOT>
        
        <PIVA>0</PIVA>
        
        <IIVA>0</IIVA>
        
        <PIVA2>0</PIVA2>
        
        <IIVA2>0</IIVA2>
        
        <TOTALG>$total_sin_envio</TOTALG>
        
        <OPERADOR></OPERADOR>
        
        <GRB>1</GRB>
        
        <COMIS_O>0</COMIS_O>
        
        <COMIS_R>0</COMIS_R>
        
        <COMIS_P>0</COMIS_P>
        
        <MONEDA_ID>1</MONEDA_ID>
        
        <COTIZACION>1</COTIZACION>
        
        <COEF>1</COEF>
        
        <DES_ID>0</DES_ID>
        
        <MARCADA>0</MARCADA>
        
        <AUTORIZO>0</AUTORIZO>
        
        <COMENTARIO>$pedido->notas</COMENTARIO>
        
        </CABECERA>
        
        <ARTICULOS>";
        $ncons = 0;
        foreach(Cart::content() as $item){
            $ncons++;
            $producto = Producto::find($item->id);
            $total = $item->price * $item->qty;
            $xmlString = $xmlString."
            <ARTICULO>
        
            <N_CONS>$ncons</N_CONS>
            
            <COD_PROD>$producto->cod_prod</COD_PROD>
            
            <PRODUCTO>$item->name</PRODUCTO>
            
            <UNIDAD></UNIDAD>
            
            <MARCA>$producto->marca</MARCA>
            
            <CANTIDAD>$item->qty</CANTIDAD>
            
            <PRECIO>$item->price</PRECIO>
            
            <TOTAL>$total</TOTAL>
            
            <IVA>$iva</IVA>
            
            <SALIDAS></SALIDAS>
            
            <DESC_AMP></DESC_AMP>
            
            <DESC></DESC>
            
            <CODFAB>$producto->codfab</CODFAB>
            
            <AIVA>0</AIVA>
            
            <OIVA>$iva</OIVA>
            
            <MONEDA_O>1</MONEDA_O>
            
            <PRECIO_O>$item->price</PRECIO_O>
            
            <PRE_ORIG>$item->price</PRE_ORIG>
            
            <COEF>1</COEF>
            
            <MODELO>$producto->modelo</MODELO>
            
            <MARCA_C>0</MARCA_C>
            
            </ARTICULO>";
        }


        $xmlString = $xmlString."
        
        </ARTICULOS>
        
        </ORDENDEVENTA>
        ";

        $filename = 'pedido_' . $pedido->id . '.xml';
        $path = public_path('archivos/' . $filename);
        file_put_contents($path, $xmlString);

        $xml = new ArchivoXml();
        $xml->path = $filename;
        $xml->pedido_id = $pedido->id;
        $xml->save();
    }
}
