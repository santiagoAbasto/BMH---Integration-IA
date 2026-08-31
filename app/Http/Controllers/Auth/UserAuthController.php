<?php

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserAuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(LoginRequest $request)
    {
        // Autenticación
        $request->authenticate('web');
    
        // Determina si el campo es 'email' o 'username'
        $inputType = $request->filled('email') ? 'email' : 'username';
        $credentials = [
            $inputType => $request->input($inputType),
            'password' => $request->input('password'),
        ];
    
        // Verifica si el checkbox 'remember' está marcado
        $remember = $request->filled('remember'); // 'remember' es el nombre del checkbox en el formulario
    
        // Intenta autenticar al usuario, pasando 'remember' como segundo parámetro
        if (Auth::guard('web')->attempt($credentials, $remember)) {
            // Si la autenticación es exitosa, redirige a la página de productos
            session()->put('anuncio_pendiente', true);
            return route('productos.home');
        }
    
        // Si la autenticación falla, redirige de nuevo al formulario de login
        return redirect()->back();
    }
    

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {

        Auth::guard('web')->logout();

        // $request->session()->invalidate();

        // $request->session()->regenerateToken();

        return redirect()->back();

    }
}
