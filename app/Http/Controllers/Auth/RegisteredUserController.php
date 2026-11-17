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
                // Registro público: además del anti-bots (honeypot, token de tiempo y rate limit de la ruta), cada
                // campo tiene que tener la forma de un dato real. Un bot que pase el filtro igual choca acá: los de
                // spam mandan HTML con enlaces en el usuario y basura como «14rp2f» en el DNI o el celular.
                $this->validarRegistroPublico($request);
                break;

            case 'vendedor':
                $request->validate([
                    'nombre' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ]);
                break;

            case 'clienteadm':
                // Cliente cargado desde el panel (dash-nuevo-usuario): no lleva email, el formulario no lo pide.
                // Sin este caso caía en el de los usuarios del panel, que exige email, y no se podía crear.
                $request->merge(['username' => mb_strtolower(trim((string) $request->username))]);
                $request->validate([
                    'username' => ['required', 'string', 'lowercase', 'max:255', 'unique:'.User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                    'descuento' => ['nullable', 'integer', 'min:0', 'max:100'],
                ]);
                break;

            default:
                $request->validate([
                    'name' => ['nullable', 'string', 'max:255'],
                    // La tabla admins exige un email único (sin él, el alta fallaba con un error 500).
                    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Admin::class],
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
                    // Un SMTP caído no frena el alta (antes este catch no atrapaba nada: faltaba el use, y el
                    // cliente veía un error 500 aunque la cuenta ya estaba creada).
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
                    'rol' =>  'cliente',
                    'password' => Hash::make($request->password),
                    'habilitado' => true
                    ]);
                // `descuento` no es asignable en masa (User::$fillable): pasado a create() se perdía sin avisar.
                $user->descuento = (int) ($request->descuento ?? 0);
                $user->save();

                return redirect()->back()->with('success', 'Cliente creado');

            default:
                // Usuarios del panel (dash-nuevo-usuario): sólo estos dos roles.
                abort_unless(in_array($request->rol, ['usuario', 'administrador'], true), 422);
                // El panel muestra este nombre abajo a la izquierda: si no se carga uno, el usuario (antes el form
                // mandaba siempre «alberto» en un campo oculto).
                $user = Admin::create([
                    'name' => filled($request->name) ? $request->name : $request->username,
                    'username' => $request->username,
                    'email' => $request->email,
                    'rol' => $request->rol,
                    'password' => Hash::make($request->password),
                ]);
                break;
        }

        

        // event(new Registered($user));

        // return redirect(RouteServiceProvider::HOME);
        return redirect()->back()->with('success', 'Usuario creado');
    }

    /**
     * Normaliza y valida el registro público de clientes. Se guarda lo normalizado: el DNI/CUIT y el celular sólo con
     * sus dígitos, el usuario en minúsculas y el CP en mayúsculas.
     */
    private function validarRegistroPublico(Request $request): void
    {
        $texto = fn (string $campo) => is_string($request->input($campo)) ? trim(preg_replace('/\s+/u', ' ', $request->input($campo))) : $request->input($campo);
        $digitos = fn (string $campo) => is_string($request->input($campo)) ? preg_replace('/[\s.\-\/()]/', '', $request->input($campo)) : $request->input($campo);

        $request->merge([
            'nombre' => $texto('nombre'),
            'username' => is_string($request->username) ? mb_strtolower(trim($request->username)) : $request->username,
            'email' => is_string($request->email) ? mb_strtolower(trim($request->email)) : $request->email,
            'dni' => $digitos('dni'),
            'celular' => $digitos('celular'),
            'direccion' => $texto('direccion'),
            'localidad' => $texto('localidad'),
            'provincia' => $texto('provincia'),
            'cp' => is_string($request->cp) ? mb_strtoupper(str_replace(' ', '', $request->cp)) : $request->cp,
        ]);

        $sinEnlaces = new \App\Rules\SinEnlaces();

        $request->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:120', $sinEnlaces],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9][a-z0-9._-]*$/', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'email' => ['required', 'string', 'email:rfc', 'max:120', $sinEnlaces],
            'dni' => ['required', 'string', 'regex:/^\d{7,11}$/'],
            'celular' => ['required', 'string', 'regex:/^\+?\d{8,15}$/'],
            'direccion' => ['required', 'string', 'min:3', 'max:160', $sinEnlaces],
            // Provincia: el id de georef que manda el select (dos dígitos).
            'provincia' => ['required', 'string', 'regex:/^\d{2}$/'],
            'localidad' => ['required', 'string', 'max:120', $sinEnlaces],
            // CP de 4 dígitos o CPA (C1425ABC).
            'cp' => ['required', 'string', 'regex:/^[A-Z]?\d{4}([A-Z]{3})?$/'],
        ], [
            'required' => 'Completá este campo.',
            'username.regex' => 'Usá sólo letras minúsculas, números, punto, guion o guion bajo (sin espacios).',
            'username.min' => 'El nombre de usuario tiene que tener al menos 3 caracteres.',
            'username.max' => 'El nombre de usuario puede tener hasta 40 caracteres.',
            'username.unique' => 'Ese nombre de usuario ya está en uso.',
            'email.email' => 'Revisá el email: no parece válido.',
            'dni.regex' => 'Ingresá el DNI (7 u 8 números) o el CUIT (11 números).',
            'celular.regex' => 'Ingresá el celular con código de área, sólo números (por ejemplo 1144822609).',
            'provincia.regex' => 'Elegí una provincia de la lista.',
            'cp.regex' => 'Ingresá el código postal de 4 números (o el CPA, como C1425ABC).',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'max' => 'Este campo es demasiado largo.',
            'min' => 'Este campo es demasiado corto.',
        ]);
    }
}
