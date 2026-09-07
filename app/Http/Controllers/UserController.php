<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Admin;
use App\Models\Pedido;
use App\Models\Comprador;
use App\Models\Categoria;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Mail\CompraMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Mail as MailAviso;

class UserController extends Controller
{

    public function cliente_datos(Request $request){
        /*
         * SEGURIDAD: el cliente se resuelve desde la sesión, no desde el query
         * string.
         *
         * Antes esto era `User::find($request->id)`, así que `/mis-datos?id=4453`
         * mostraba la ficha de OTRO cliente (nombre, DNI, dirección, teléfono,
         * margen de reventa) a cualquiera con sesión iniciada. Y sin `?id` el
         * find devolvía null y la vista rompía con un 500.
         */
        $usuario = Auth::guard('web')->user();

        abort_if($usuario === null, 403);
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];

        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/localidades?orden=nombre&max=1000');
        $datos = json_decode($contenido, true);
        $localidades = $datos['localidades'];

        $referencia = array();
        $nombres = array();
        foreach($provincias as $provincia){
            array_push($referencia, $provincia['id']);
            array_push($nombres, $provincia['nombre']);
        }

        $zonaclientes = true;

        $categorias = Categoria::orderBy('orden')->orderBy('nombre')->get();
        $margenes = $usuario->margenesReventa()->get()->keyBy('categoria_id');

        return view('frontend/cliente-datos', compact('usuario', 'provincias', 'nombres', 'referencia', 'localidades', 'zonaclientes', 'categorias', 'margenes'));
    }
    
    
    public function reventaCliente(Request $request){
        $usuario = User::find(Auth::guard('web')->user()->id);
        $usuario->reventa = $request->reventa;

        $usuario->save();

        return redirect()->route('productos.clientes');



    }

    /**
     * Guarda el margen de reventa del cliente: el general (users.reventa) y las
     * excepciones por categoría.
     *  - Input vacío  => usa el general (herencia, sin fila específica).
     *  - Input en 0   => se le asigna el valor del general de forma explícita
     *                    (queda grabado con ese valor y se muestra en el input).
     *  - Otro número  => esa excepción específica.
     */
    public function margenesReventa(Request $request){
        $usuario = Auth::guard('web')->user();
        abort_if($usuario === null, 403);

        // Margen general
        $usuario->reventa = $request->input('incrementoReventa', 0);
        $usuario->save();
        $general = round(floatval($usuario->reventa), 2);

        // Márgenes por categoría
        $categorias = (array) $request->input('margen_categoria', []);
        $existentes = $usuario->margenesReventa()->get()->keyBy('categoria_id');
        $ahora = now();
        $aInsertar = [];

        foreach ($categorias as $catId => $valor) {
            $valor = trim((string) $valor);
            $esVacio = $valor === '' || ! is_numeric($valor);
            $porcentaje = $esVacio ? null : round(floatval($valor), 2);

            // Vacío => usar el general (herencia, sin fila específica).
            if ($esVacio) {
                if (isset($existentes[$catId])) {
                    $existentes[$catId]->delete();
                }
                continue;
            }

            // 0 => se le asigna el valor del general de forma explícita.
            if ($porcentaje == 0) {
                $porcentaje = $general;
            }

            if (isset($existentes[$catId])) {
                $fila = $existentes[$catId];
                if ((float) $fila->porcentaje !== $porcentaje) {
                    $fila->porcentaje = $porcentaje;
                    $fila->save();
                }
            } else {
                $aInsertar[] = [
                    'user_id' => $usuario->id,
                    'categoria_id' => $catId,
                    'porcentaje' => $porcentaje,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }

        if (! empty($aInsertar)) {
            DB::table('margenes_reventa')->insert($aInsertar);
        }

        return redirect()->route('cliente.datos')->with('success', 'Márgenes de reventa actualizados');
    }

    public function updateDate(Request $request){
        /*
         * SEGURIDAD: igual que arriba. Antes era
         * `User::find($request->cliente_id)`, con el id llegando de un campo
         * oculto del formulario: cualquier cliente podía MODIFICAR el email, la
         * dirección, el teléfono y el margen de reventa de otro cambiando ese
         * valor. Ahora sólo puede editar su propia ficha.
         */
        $usuario = Auth::guard('web')->user();

        abort_if($usuario === null, 403);

        $usuario->direccion = $request->direccionEntrega;
        $usuario->localidad = $request->localidadEntregar;
        $usuario->celular = $request->telefono;
        $usuario->email = $request->mail;
        $usuario->transporte = $request->transporte;

        $usuario->save();

        $zonaclientes = true;

        return view('frontend/cliente-datos', compact('usuario', 'zonaclientes'));



    }

    public function cliente_historial(Request $request){
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];
        $historial = Pedido::where('cliente_id', $request->id)->orderBy('id', 'DESC')->get();
        $zonaclientes = true;
        $ventana = 'historial-nav';
        return view('frontend/cliente-historial', compact('historial', 'provincias', 'zonaclientes', 'ventana'));
    }

    public function form_registro(){

        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];

        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/localidades?provincia=02&orden=nombre&max=1000');
        $datos = json_decode($contenido, true);
        $localidades = $datos['localidades'];

        return view('frontend/form-registro', compact('provincias', 'localidades'));
    }

    public function vendedor_clientes(){
        $zonaclientes = true;
        $clientes = User::where('rol', 'cliente')->where('vendedor_id', Auth::guard('web')->user()->id)->get();
        $usuarios = User::where('rol', 'cliente')->whereNull('vendedor_id')->get();
        $ventana = 'clientes-nav';
        return view('frontend/clientes-vendedor', compact('clientes', 'usuarios', 'zonaclientes', 'ventana'));
    }

    public function vendedor_clientes_asociar(Request $request){
        foreach($request->clientes as $cliente_id){
            $cliente = User::find($cliente_id);
            $cliente->vendedor_id = $request->id;
            $cliente->save();
        }
        return redirect()->back();
    }

    public function vendedor_clientes_desasociar(Request $request){
        $cliente = User::find($request->id);
        $cliente->vendedor_id = null;
        $cliente->save();
        return redirect()->back();
    }

    public function vendedor_cliente(Request $request){
        $cliente = User::find($request->id);
        $pedidos = Pedido::where('cliente_id', $request->id)->orderBy('id', 'DESC')->get();
        $zonaclientes = true;
        $ventana = 'clientes-nav';
        return view('frontend/cliente-vendedor', compact('cliente', 'pedidos', 'zonaclientes', 'ventana'));
    }

    public function vendedor_pedido_datos(Request $request){
        $pedido = Pedido::find($request->id);
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];
        $zonaclientes = true;
        $ventana = 'clientes-nav';
        return view('frontend/cliente-pedido', compact('pedido', 'provincias', 'zonaclientes', 'ventana'));
    }

    public function usuarios(){
        $editar_usuarios = Admin::where('rol', '!=', 'cliente')->get();
        return view('backend/dash-usuarios', compact('editar_usuarios'));
    }

    public function nuevoUsuario(){
        return view('backend/dash-nuevo-usuario');
    }

    public function update(Request $request): RedirectResponse { 

        $usuario = Admin::find($request->id);

        if($request->username != $usuario->username){
            $request->validate([
                'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
            ]);
        }

        if($request->email != $usuario->email){
            $request->validate([
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class]
            ]);
        }
        
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        
        $usuario->username = $request->username;
        $usuario->email = $request->email;
        $usuario->rol = $request->rol;
        $usuario->password = $request->password;
        $usuario->save();

        return redirect()->back()->with('success', 'Usuario actualizado');
    }

    public function delete(Request $request){

        $usuarios = Admin::all();
       
        if(count($usuarios) == 1){
            return redirect()->back()->with('warning', 'No puede eliminar todos los usuarios');
        } else {
            $usuario = Admin::find($request->id);
            $rol = $usuario->rol;
            $usuario->delete();

            return redirect()->back()->with('success', ucfirst($rol).' eliminado');
        }
        
    }

    public function cliente_delete(Request $request){

        $cliente = User::find($request->id);
        $username = $cliente->username;
        $cliente->delete();

        
        return redirect()->back()->with('success', ucfirst($username).' eliminado');
        
        
    }

    public function clientes(){
        $clientes = User::where('rol', 'cliente')->orderBy('id', 'DESC')->paginate(20);
        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];

        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/localidades?orden=nombre&max=1000');
        $datos = json_decode($contenido, true);
        $localidades = $datos['localidades'];

        $referencia = array();
        $nombres = array();
        foreach($provincias as $provincia){
            array_push($referencia, $provincia['id']);
            array_push($nombres, $provincia['nombre']);
        }

        return view('backend/dash-clientes', compact('clientes', 'provincias', 'nombres', 'referencia', 'localidades'));
    }
    
    public function vendedores(){
        $vendedores = User::where('rol', 'vendedor')->orderBy('id', 'DESC')->get();

        return view('backend/dash-vendedores', compact('vendedores'));
    }

    public function cliente_update(Request $request){
        $usuario = User::find($request->id);
        //return $request;

        if(isset($request->username) && $request->username != $usuario->username){
            $request->validate([
                'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
            ]);
            $usuario->username = $request->username;
        }

        if($request->email != $usuario->email){
            $request->validate([
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class]
            ]);
        }
        
        if($request->check != null){
            $request->validate([
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
            $usuario->password = $request->password;
        }
        

        
        
        $usuario->email = $request->email;
        $usuario->rol = 'cliente';
        if(isset($request->descuento)){$usuario->descuento = $request->descuento;}

        $usuario->direccion = $request->direccion;
        $usuario->localidad = $request->localidad;
        $usuario->provincia = $request->provincia;
        $usuario->dni = $request->dni;
        $usuario->cp = $request->cp;
        $usuario->celular = $request->celular;
        $usuario->name = $request->nombre;

        $usuario->save();

        return redirect()->back()->with('success', 'Cliente actualizado');
    }

    public function vendedor_update(Request $request){
        $usuario = User::find($request->id);
        //return $request;

        if(isset($request->username) && $request->username != $usuario->username){
            $request->validate([
                'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
            ]);
            $usuario->username = $request->username;
        }

        if($request->email != $usuario->email){
            $request->validate([
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class]
            ]);
        }
        
        if(isset($request->password)){
            $request->validate([
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
            $usuario->password = $request->password;
        }
        
        $usuario->email = $request->email;
        $usuario->name = $request->nombre;

        $usuario->save();

        return redirect()->back()->with('success', 'Vendedor actualizado');
    }

    public function vendedor_delete(Request $request){
        $vendedor = User::find($request->id);
        $vendedor->delete();
        return redirect()->back()->with('success', 'Vendedor eliminado');
    }

    public function cliente_buscar(Request $request){
        $busqueda = $request->valor;
        $clientes = User::where('name', 'LIKE', '%'.$busqueda.'%')
        ->orWhere('username', 'LIKE', '%'.$busqueda.'%')
        ->orWhere('email', 'LIKE', '%'.$busqueda.'%')
        ->orWhere('dni', 'LIKE', '%'.$busqueda.'%')
        ->orWhere('username', 'LIKE', '%'.$busqueda.'%')
        ->orWhere('cp', 'LIKE', '%'.$busqueda.'%')
        ->orWhere('id', 'LIKE', '%'.$busqueda.'%')
        ->where('rol', 'cliente')
        ->orderBy('id', 'DESC')->paginate(20);

        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/provincias?orden=nombre');
        $datos = json_decode($contenido, true);
        $provincias = $datos['provincias'];

        $contenido = file_get_contents('https://apis.datos.gob.ar/georef/api/localidades?orden=nombre&max=1000');
        $datos = json_decode($contenido, true);
        $localidades = $datos['localidades'];

        $referencia = array();
        $nombres = array();
        foreach($provincias as $provincia){
            array_push($referencia, $provincia['id']);
            array_push($nombres, $provincia['nombre']);
        }

        return view('backend/dash-clientes-listado', compact('clientes', 'provincias', 'nombres', 'referencia', 'localidades'));
    }

    public function cliente_habilitar(Request $request){
        $cliente = User::find($request->id);
        $cliente->habilitado = !$cliente->habilitado;
        $cliente->save();
        if($cliente->habilitado){
            try {
                $content = MailAviso::find(1)->habilitado;
                $subject = MailAviso::find(1)->habilitado_titulo;
                $email = new CompraMail($subject, $content, null);
                Mail::to($cliente->email)->send($email);
            } catch (Exception $e) {
                echo 'Se ha producido una excepción: ' . $e->getMessage();
            }
        }
    }



}
