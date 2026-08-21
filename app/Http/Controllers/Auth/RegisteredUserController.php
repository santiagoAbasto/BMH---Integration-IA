<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
                } catch (Exception $e) {
                    echo 'Se ha producido una excepción: ' . $e->getMessage();
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
