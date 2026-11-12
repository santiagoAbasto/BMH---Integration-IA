<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureHumanFormSubmission;
use App\Models\User;
use App\Models\Admin;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Mail\CompraMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Mail as MailAviso;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {   
        // Sin sesión de administrador sólo se puede hacer el registro público de
        // clientes (el formulario de la home). Vendedores, clientes cargados por
        // BMH y usuarios del panel los crea un administrador: antes cualquiera
        // podía mandar rol=administrador y crearse un acceso al panel.
        abort_unless($request->rol === 'cliente' || Auth::guard('admin')->check(), 403);

        switch($request->rol){
            
            case 'cliente':
                $request->validate([
                    'nombre' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                    'dni' => ['required'],
                    'direccion' => ['required'],
                    'localidad' => ['required'],
                    'provincia' => ['required'],
                    'celular' => ['required'],
                    'cp' => ['required']
                ]);
                break;

            case 'vendedor':
                $request->validate([
                    'nombre' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ]);
                break;
            
            default:
                $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ]);
                break;
        }
        

        // Registro público que cayó en el campo trampa del anti-bots: se finge el alta, pero no se crea nada.
        if ($request->rol === 'cliente' && EnsureHumanFormSubmission::isTrapped($request)) {
            return redirect()->route('home', ['registro' => true]);
        }

        switch($request->rol){
            case 'cliente':
                $user = User::create([
                    'name' => $request->nombre,
                    'username' => $request->username,
                    'rol' => $request->rol,
                    'password' => Hash::make($request->password),
                    'dni' => $request->dni,
                    'direccion' => $request->direccion,
                    'localidad' => $request->localidad,
                    'provincia' => $request->provincia,
                    'cp' => $request->cp,
                    'email' => $request->email,
                    'celular' => $request->celular,
                    'habilitado' => true,
                ]);

                try {
                    $content = MailAviso::find(1)->registro;
                    $subject = MailAviso::find(1)->registro_titulo;
                    $email = new CompraMail($subject, $content, null);
                    Mail::to($user->email)->send($email);
                } catch (\Throwable $e) {
                    // Un SMTP caído no frena el alta (antes este catch no atrapaba nada: faltaba el use).
                    report($e);
                }

                // Auth::login($user);
                return redirect()->route('home', ['registro' => true]);
                break;

            case 'vendedor':
                $user = User::create([
                    'name' => $request->nombre,
                    'username' => $request->username,
                    'rol' => $request->rol,
                    'password' => Hash::make($request->password),
                    'habilitado' => true,
                ]);
                return redirect()->back()->with('success', 'Vendedor creado');
                break;
                
                
                      case 'clienteadm':


                $user = User::create([
                    'name' => $request->username,
                    'username' => $request->username,
                    'descuento' => $request->descuento,
                    'rol' =>  'cliente',
                    'password' => Hash::make($request->password),
                    'habilitado' => true
                    ]);


                break;

            default:
                // Usuarios del panel (dash-nuevo-usuario): sólo estos dos roles.
                abort_unless(in_array($request->rol, ['usuario', 'administrador'], true), 422);
                $user = Admin::create([
                    'name' => $request->name,
                    'username' => $request->username,
                    'rol' => $request->rol,
                    'password' => Hash::make($request->password),
                ]);
                break;
        }

        

        // event(new Registered($user));

        // return redirect(RouteServiceProvider::HOME);
        return redirect()->back()->with('success', 'Usuario creado');
    }
}
