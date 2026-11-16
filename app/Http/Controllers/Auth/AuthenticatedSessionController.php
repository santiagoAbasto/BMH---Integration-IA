<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cierre de sesión de los clientes (menú de la cuenta en el header). El ingreso es UserAuthController (user.login);
 * el login y la recuperación de contraseña de Breeze se sacaron.
 */
class AuthenticatedSessionController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Sólo se cierra el guard de clientes: si en el mismo navegador hay una sesión del admin, sigue abierta.

        if ($request->ventana == 'home') {
            return redirect()->back();
        }

        return redirect('/');
    }
}
