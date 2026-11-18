<?php

namespace App\Http\Controllers;

use App\Mail\PedidoClienteMail;
use App\Models\Bonificacion;
use App\Models\Carrito;
use App\Models\Contacto;
use App\Models\Pedido;
use App\Models\PedidoProducto;
use App\Models\Producto;
use App\Models\User;
use CodersFree\Shoppingcart\Facades\Cart;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
        $this->actualizarPrecios();
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
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/localidades?provincia='.$prov.'&orden=nombre&max=1000');
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
        $validated = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Publicados: como en el catálogo, todo salvo los ocultos (estado 0; NULL también se muestra).
        $producto = Producto::query()
            ->where(fn ($q) => $q->whereNull('estado')->orWhere('estado', '!=', 0))
            ->findOrFail((int) $validated['producto_id']);

        // El precio del navegador se ignora. Siempre se recalcula con la misma
        // regla que usa el resto del carrito, incluida la condición del cliente.
        Cart::add(
            $producto->id,
            $producto->nombre,
            (int) $validated['qty'],
            (float) $producto->precio_unitario_descontado(),
        );

        return response()->json(Cart::content());
    }

    /*
     * Sumar, quitar y eliminar reciben el renglón (rowId) que se ve en la
     * pantalla. Puede que ya no esté en el carrito: un doble click en el
     * tachito, u otra pestaña u otro dispositivo (el carrito del cliente es
     * el mismo en todos) que ya lo sacó. En ese caso no se falla: se responde
     * con el carrito como está, para que la pantalla se actualice.
     */

    public function carrito_sumar(Request $request)
    {
        if (Cart::content()->has($request->item_id)) {
            $item = Cart::get($request->item_id);
            Cart::update($request->item_id, $item->qty + $request->qty);
        }

        return $this->respuestaCarrito($request);
    }

    public function carrito_quitar(Request $request)
    {
        if (Cart::content()->has($request->item_id)) {
            $item = Cart::get($request->item_id);
            Cart::update($request->item_id, $item->qty - $request->qty);
        }

        return $this->respuestaCarrito($request);
    }

    public function carrito_remover(Request $request)
    {
        if (Cart::content()->has($request->item_id)) {
            Cart::remove($request->item_id);
        }

        return $this->respuestaCarrito($request);
    }

    /**
     * El precio de cada renglón como es hoy: el carrito guarda el precio del momento en que se agregó, y una oferta
     * que empieza, termina o cambia lo deja viejo.
     */
    private function actualizarPrecios(): void
    {
        foreach (Cart::content() as $item) {
            $producto = Producto::find($item->id);
            if (! $producto) {
                continue;
            }
            $precio = round((float) $producto->precio_unitario_descontado(), 2);
            if (abs((float) $item->price - $precio) >= 0.005) {
                Cart::update($item->rowId, ['price' => $precio]);
            }
        }
    }

    /** El carrito actualizado, con la vista de la sección desde donde se pidió. */
    private function respuestaCarrito(Request $request)
    {
        $cart = Cart::content();
        // La vista de la Zona de Clientes necesita un cliente logueado (sesión vencida → la pública).
        $publica = $request->seccion == 'public' || ! Auth::guard('web')->check();
        $vista = $publica ? 'frontend/components/carrito-desplegado' : 'frontend/components/carrito-productos';

        return response()->json([
            'view' => view($vista, compact('cart'))->render(),
            'cart' => $cart,
        ]);
    }

    public function repetir_pedido(Request $request)
    {
        // SEGURIDAD: sólo un pedido propio (o de un cliente del vendedor), no cualquier id.
        $pedido = Pedido::visiblesPara(Auth::guard('web')->user())->findOrFail($request->integer('id'));
        foreach ($pedido->productos()->get() as $producto) {
            Cart::add($producto->id, $producto->nombre, $producto->pivot->cantidad, (float) $producto->precio_unitario_descontado());
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
        foreach (Cart::content() as $item) {
            $producto = Producto::find($item->id);
            // Un producto que se borró mientras estaba en el carrito no corta el pedido a la mitad.
            if (! $producto) {
                continue;
            }

            $relacion = new PedidoProducto();
            $relacion->pedido_id = $pedido->id;
            $relacion->producto_id = $item->id;
            $relacion->cantidad = $item->qty;
            // Con precio de oferta, ese precio es el final: sin descuento del producto ni del cliente en el renglón.
            $conOferta = ! $producto->aplicaDescuentoCliente();
            $relacion->descuento_producto = ! $conOferta && $producto->descuento ? $producto->descuento : 0;
            if ($conOferta) {
                $relacion->descuento_cliente = 0;
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

        // Confirmación al cliente y aviso a BMH (a las cuentas de Contacto › Contenido › Avisos por email), con el
        // formato de BMH. Un SMTP caído no rompe el pedido: ya quedó guardado y se ve en el panel.
        if (filter_var($pedido->mail, FILTER_VALIDATE_EMAIL)) {
            app(\App\Services\EnvioMail::class)->enviar(fn () => Mail::to($pedido->mail)->send(new PedidoClienteMail($pedido, $mensajeEnvio)), 'confirmación de pedido al cliente', ['pedido_id' => $pedido->id]);
        }
        $archivo = $request->file('archivo');
        app(\App\Services\AvisosBmh::class)->pedidoNuevo($pedido, $archivo instanceof \Illuminate\Http\UploadedFile ? $archivo : null, $mensajeEnvio);

        //Limpiar carrito
        // Cart::destroy();

        return redirect()->route('carrito', ['aviso' => true]);
    }

    /**
     * Panel › Carrito › Información: la ventana que ve el cliente al enviar un pedido (título y mensaje).
     * El resto de las columnas de `carritos` (textos de pago y de entrega) no se editan acá.
     */
    public function informacion()
    {
        $informacion = (Carrito::query()->find(1) ?? Carrito::query()->forceCreate(['id' => 1]));

        return view('backend/dash-carrito-informacion', [
            'informacion' => $informacion,
            'mensajeTexto' => Carrito::textoPlano($informacion->pedido),
            'maximoTitulo' => self::MAXIMO_TITULO_PEDIDO,
            'maximoMensaje' => self::MAXIMO_MENSAJE_PEDIDO,
        ]);
    }

    public const MAXIMO_TITULO_PEDIDO = 90;

    public const MAXIMO_MENSAJE_PEDIDO = 500;

    public function carrito_info_update(Request $request)
    {
        $datos = $request->validate([
            'pedido_titulo' => ['required', 'string', 'max:'.self::MAXIMO_TITULO_PEDIDO],
            'pedido' => ['required', 'string', 'max:'.self::MAXIMO_MENSAJE_PEDIDO],
        ], [
            'pedido_titulo.required' => 'Escribí el título de la ventana.',
            'pedido_titulo.max' => 'El título puede tener hasta '.self::MAXIMO_TITULO_PEDIDO.' caracteres.',
            'pedido.required' => 'Escribí el mensaje para el cliente.',
            'pedido.max' => 'El mensaje puede tener hasta '.self::MAXIMO_MENSAJE_PEDIDO.' caracteres.',
        ]);

        // Sólo estas dos columnas: antes se guardaban también los textos de pago y de entrega, que ya no están en el
        // formulario, y cada vez que se tocaba «Actualizar» se borraban.
        $informacion = (Carrito::query()->find(1) ?? Carrito::query()->forceCreate(['id' => 1]));
        $informacion->pedido_titulo = trim($datos['pedido_titulo']);
        $informacion->pedido = Carrito::htmlDeTexto($datos['pedido']);
        $informacion->save();

        return redirect()->route('dashboard.informacion')->with('success', 'Mensaje del pedido actualizado');
    }
}
