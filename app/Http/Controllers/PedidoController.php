<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\DimensionPedido;
use App\Models\Dimension;
use App\Models\User;
use App\Models\Impuesto;
use Carbon\Carbon;

class PedidoController extends Controller
{
    public function ventas() {
        $pedidos = Pedido::orderBy('id', 'DESC')->paginate(20);
    
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];
    
        $nuevosPedidos = Pedido::where('created_at', '>=', Carbon::now()->subDay())->count();
    
        return view('backend/dash-ventas', compact('pedidos', 'provincias', 'nuevosPedidos'));
    }

    public function pedido_datos(Request $request){
        $pedido = Pedido::find($request->id);
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];
        return view('backend/dash-pedido', compact('pedido', 'provincias'));
    }

    public function dash_buscar_pedido(Request $request){
        $busqueda = $request->valor;
        $pedidos = Pedido::where('id', 'like', '%'.$busqueda.'%')
            ->orWhere('estado', 'like', '%'.$busqueda.'%')
            ->orWhere('estado_orden', 'like', '%'.$busqueda.'%')
            ->orWhere('nombre', 'like', '%'.$busqueda.'%')
            ->orWhere('dni', 'like', '%'.$busqueda.'%')
            ->orWhere('fecha', 'like', '%'.$busqueda.'%')
            ->orderBy('id', 'DESC')->paginate(20);

        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];
        return view('backend/dash-ventas-listado', compact('pedidos', 'provincias'));
    }

    public function update_estado(Request $request){
        $pedido = Pedido::find($request->id);
        $pedido->estado = $request->estado;
        $pedido->save();
    }

    public function update_estado_orden(Request $request){
        $pedido = Pedido::find($request->id);
        $pedido->estado_orden = $request->estado;
        $pedido->save();

        return redirect()->back()->with('success', 'El estado de la orden ha sido modificado');
    }
    
    public function postcompra_transferencia(){
        $datos = $request->session()->get('datos', 0);
        $informacion = $request->session()->get('informacion', 0);
        return view('frontend/transferencia-bancaria', compact('datos', 'informacion'));
    }
    
    public function postcompra_general(){
        $datos = Session::get('datos');
        $informacion = Session::get('informacion');
        return view('frontend/post-compra', compact('datos', 'informacion'));
    }

    public function descuento_update(Request $request){
        $relacion = DimensionPedido::find($request->id);
        // $dimension = Dimension::find($relacion->dimension_id);
        $pedido = Pedido::find($relacion->pedido_id);

        $relacion->descuento_producto = $request->descuento;
        $relacion->precio_descontado = $relacion->precio_unitario * (1 - ($relacion->descuento_cliente / 100)) * (1 - ($relacion->descuento_categoria / 100)) * (1 - ($request->descuento / 100));
        $relacion->save();

        $total = 0;
        foreach($pedido->productos()->get() as $dimension){
            $total += $dimension->pivot->precio_descontado * $dimension->pivot->cantidad;
        }
        $total = $total * (1 + (Impuesto::find(1)->porcentaje / 100));
        $pedido->total_pedido = number_format($total, 2, ',', '.');
        $pedido->save();

        return redirect()->back()->with('success', 'Pedido actualizado');
    }

    public function delete(Request $request){
        $pedido = Pedido::find($request->id);
        $pedido->delete();
        return redirect()->back()->with('success', 'Pedido eliminado');
    }
}
