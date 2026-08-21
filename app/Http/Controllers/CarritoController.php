<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Carrito;
use App\Models\Producto;
use App\Models\CodigoPostal;
use App\Models\ZonaPostal;
use App\Models\Pedido;
use App\Models\DimensionPedido;
use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\XmlController;
use App\Mail\CompraMail;
use App\Mail\PedidoClienteMail;
use App\Mail\PedidoEmpresaMail;
use App\Models\Bonificacion;
use Illuminate\Support\Facades\Mail;
use App\Models\Contacto;
use App\Models\Comprador;
use App\Models\Newsletter;
use App\Models\Dimension;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\PedidoProducto;
use Exception;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

class CarritoController extends Controller
{

    public function carrito_publico(Request $request)
    {
        $cart = Cart::content();
        $informacion = Carrito::find(1);
        $aviso = isset($request->aviso) ? $request->aviso : false;
        $ventana = 'carrito-nav';
        $bonificaciones = Bonificacion::orderBy('orden')->get();
        return view('frontend/carrito-publico', compact('cart', 'informacion', 'aviso', 'ventana', 'bonificaciones'));
    }

    public function carrito(Request $request)
    {
        $zonaclientes = true;
        $cart = Cart::content();
        $informacion = Carrito::find(1);
        $aviso = isset($request->aviso) ? $request->aviso : false;
        $ventana = 'carrito-nav';


        if (Auth::guard('web')->user()->rol == 'vendedor') {
            $clientes = User::where('rol', 'cliente')->where('vendedor_id', Auth::guard('web')->user()->id)->get();
        } else {
            $clientes = null;
        }
        $bonificaciones = Bonificacion::orderBy('orden')->get();

        return view('frontend/carrito', compact('cart', 'zonaclientes', 'informacion', 'aviso', 'ventana', 'clientes', 'bonificaciones'));
    }

    public function pedido(Request $request)
    {
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];
        if (Auth::guard('web')->check()) {
            $prov = Auth::guard('web')->user()->provincia;
            $loc = Auth::guard('web')->user()->localidad;
        } else {
            $prov = '02';
            $loc = '';
        }
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/localidades?provincia=' . $prov . '&orden=nombre&max=1000');
        $datos = json_decode($contenido, true);
        $localidades = $datos['localidades'];
        $datos = $request;
        $informacion = Carrito::find(1);
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        } else {
            $user = null;
        }
        return view('frontend/pedido', compact('datos', 'provincias', 'localidades', 'user', 'informacion', 'loc'));
    }

    public function carrito_bonificaciones(Request $request)
    {

        $request->validate([
            'idBonificacion' => 'required|exists:bonificaciones,id',
            'desde' => 'nullable|numeric|min:0',
            'hasta' => 'nullable|numeric|min:0',
            'porcentaje' => 'nullable|numeric|min:0',
        ]);


        $bonificacion = Bonificacion::find($request->idBonificacion);
        $bonificacion->desde = $request->desde;
        $bonificacion->hasta = $request->hasta;
        $bonificacion->porcentaje = $request->porcentaje;

        $bonificacion->save();

        return redirect()->back()->with('success', 'Bonificacion actualizada');
    }

  

    public function agregar_carrito(Request $request)
    {
        $producto = Producto::find($request->producto_id);

        Cart::add($producto->id, $producto->nombre, $request->qty, $request->precio);
        return response()->json(Cart::content());
    }

    public function carrito_sumar(Request $request)
    {
        $item = Cart::get($request->item_id);
    
        $qty = $item->qty + $request->qty;
    
        Cart::update($request->item_id, $qty);
    
        $cart = Cart::content();
    
        if ($request->seccion == 'public') {
            return response()->json([
                'view' => view('frontend/components/carrito-desplegado', compact('cart'))->render(),
                'cart' => $cart
            ]);
        } else {
            return response()->json([
                'view' => view('frontend/components/carrito-productos', compact('cart'))->render(),
                'cart' => $cart
            ]);
        }
    }

    public function carrito_quitar(Request $request)
    {
        $item = Cart::get($request->item_id);
        $qty = $item->qty - $request->qty;
    
        Cart::update($request->item_id, $qty);
        $cart = Cart::content();
    
        if ($request->seccion == 'public') {
            return response()->json([
                'view' => view('frontend/components/carrito-desplegado', compact('cart'))->render(),
                'cart' => $cart
            ]);
        } else {
            return response()->json([
                'view' => view('frontend/components/carrito-productos', compact('cart'))->render(),
                'cart' => $cart
            ]);
        }
    }

    public function carrito_remover(Request $request)
    {
        Cart::remove($request->item_id);
        $cart = Cart::content();
    
        if ($request->seccion == 'public') {
            return response()->json([
                'view' => view('frontend/components/carrito-desplegado', compact('cart'))->render(),
                'cart' => $cart
            ]);
        } else {
            return response()->json([
                'view' => view('frontend/components/carrito-productos', compact('cart'))->render(),
                'cart' => $cart
            ]);
        }
    }

    public function repetir_pedido(Request $request)
    {
        $pedido = Pedido::find($request->id);
        foreach ($pedido->productos()->get() as $producto) {
            Cart::add($producto->id, $producto->nombre, $producto->pivot->cantidad, $producto->precio_final());
        }
    }

    public function actualizar_pedido(Request $request)
    {
        if ($request->seccion == 'public') {
            return view('frontend/components/carrito-total');
        } else {
            return view('frontend/components/carrito-pedido', ['informacion' => Carrito::find(1)]);
        }
    }

    public function actualizar_total_pedido(Request $request)
    {
        return view('frontend/components/carrito-detalle', ['descuento' => $request->descuento]);
    }

    public function actualizar_subtotal(Request $request)
    {
        $producto = Producto::find($request->id);

        // $cantidad = $producto->precio * $request->cantidad;
        $montoDescuento = ($producto->precio() * $producto->descuento) / 100;
        $subtotal = $producto->precio() - $montoDescuento;

        if (Auth::guard('web')->user()->descuento > 0) {
            $montoDescuentoCliente = ($subtotal * Auth::guard('web')->user()->descuento) / 100;
            $subtotal = $subtotal - $montoDescuentoCliente;
        }

        $total = $subtotal * $request->cantidad;

        return response()->json([
            'total' => number_format($total, 2, ',', '.'),
        ]);
    }

    public function tipo_envio(Request $request)
    {
        $request->session()->put('tipo_envio', $request->tipo);
    }

    public function realizar_pedido(Request $request)
    {

        if ($request->seccion == 'public') {
            $user = $request;
        } else {
            $user = Auth::guard('web')->user();
        }

        $pedido = new Pedido();
        date_default_timezone_set('America/Buenos_Aires');
        $pedido->fecha = date('d-m-Y H:i');
        $pedido->nombre = $user->name ?? $user->username;
        if ($user->dni) {
            $pedido->dni = $user->dni;
        } else {
            $pedido->dni = 'no tiene';
        }

        if ($user->email) {
            $pedido->mail = $user->email;
        } else {
            $pedido->mail = 'no tiene';
        }

        if ($user->provincia) {
            $pedido->provincia = $user->provincia;
        } else {
            $pedido->provincia = 'no tiene';
        }

        if ($user->localidad) {
            $pedido->localidad = $user->localidad;
        } else {
            $pedido->localidad = 'no tiene';
        }

        if ($user->direccion) {
            $pedido->direccion = $user->direccion;
        } else {
            $pedido->direccion = 'no tiene';
        }

        if ($user->celular) {
            $pedido->celular = $user->celular;
        } else {
            $pedido->celular = 'no tiene';
        }


        if ($user->cp) {
            $pedido->cp = $user->cp;
        } else {
            $pedido->cp = 'no tiene';
        }






        $pedido->estado = 'Esperando pago';
        $pedido->cliente_id = Auth::guard('web')->check() ? Auth::guard('web')->user()->id : null;
        $pedido->total_pedido = Carrito::total_format();
        $pedido->notas = isset($request->mensaje) ? $request->mensaje : null;
        $pedido->bonificacion = Carrito::bonificacion();

        // $pedido->tipo_envio = $request->seccion == 'public' ? $request->tipo_envio : '-';
        // $pedido->tipo_pago = $request->tipo_pago;
        // $pedido->tipo_envio = $request->tipo_envio;
        $pedido->descuento_cliente = Auth::guard('web')->check() ? Auth::guard('web')->user()->descuento : '0';
        // $pedido->descuento_pago = $request->tipo_pago == 'Efectivo o transferencia' ? Carrito::find(1)->descuento_efectivo : '0';
        // $pedido->vendedor=$user->vendedor_id == null ? null : User::find($user->vendedor_id)->name;
        if (isset($request->archivo)) {
            $pedido->archivo = true;
        }
        $pedido->save();

        // Asociar productos
        foreach (Cart::content()  as $item) {
            $producto = Producto::find($item->id);

            $relacion = new PedidoProducto();
            $relacion->pedido_id = $pedido->id;
            $relacion->producto_id = $item->id;
            $relacion->cantidad = $item->qty;
            if ($producto->descuento) {
                $relacion->descuento_producto = $producto->descuento;
            } else {
                $relacion->descuento_producto = 0;
            }


            if ($producto->precio) {
                $relacion->precio_unitario = $producto->precio();
            } else {
                $relacion->precio_unitario = 0;
            }

            if ($producto->price) {
                $relacion->precio_descontado = $item->price;
            } else {
                $relacion->precio_descontado = 0;
            }

            $relacion->save();
        }


        $mensajeEnvio = Carrito::obtenerMensajeEnvio($pedido->tipo_envio);
        
        Cart::destroy();

        try {
            $email = new PedidoClienteMail($pedido, $mensajeEnvio);
            Mail::to($pedido->mail)->send($email);

            $contacto = Contacto::find(1);
            $mail_empresa = $contacto->mail;
            $email = new PedidoEmpresaMail($pedido, $request->archivo, $mensajeEnvio);
            Mail::to($mail_empresa)->send($email);
        } catch (Exception $e) {
            echo 'Se ha producido una excepción: ' . $e->getMessage();
        }

        //Limpiar carrito
        // Cart::destroy();

        return redirect()->route('carrito', ['aviso' => true]);
    }

    public function informacion()
    {
        $informacion = Carrito::find(1);
        return view('backend/dash-carrito-informacion', compact('informacion'));
    }

    public function carrito_info_update(Request $request)
    {
        $informacion = Carrito::find(1);
        $informacion->info = $request->info;
        $informacion->pedido = $request->pedido;
        $informacion->pedido_titulo = $request->pedido_titulo;
        if (isset($request->info_efectivo) && $request->info_efectivo > 0) {
            $informacion->info_efectivo = $request->info_efectivo;
        } else {
            $informacion->info_efectivo = null;
        }
        if (isset($request->info_mp) && $request->info_mp > 0) {
            $informacion->info_mp = $request->info_mp;
        } else {
            $informacion->info_mp = null;
        }
        if (isset($request->info_retiro) && $request->info_retiro > 0) {
            $informacion->info_retiro = $request->info_retiro;
        } else {
            $informacion->info_retiro = null;
        }
        if (isset($request->info_convenir) && $request->info_convenir > 0) {
            $informacion->info_convenir = $request->info_convenir;
        } else {
            $informacion->info_convenir = null;
        }
        if (isset($request->info_empresa) && $request->info_empresa > 0) {
            $informacion->info_empresa = $request->info_empresa;
        } else {
            $informacion->info_empresa = null;
        }

        $informacion->descuento_efectivo = $request->descuento_efectivo;
        
        $informacion->save();

        return redirect()->back()->with('success', 'Información del carrito actualizada');
    }
}
