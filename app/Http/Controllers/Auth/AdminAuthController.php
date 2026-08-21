<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginAdminRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
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
    public function login(LoginAdminRequest $request)
    {
        $request->authenticate('admin');
        
        // $request->session()->regenerate();
        $credentials = $request->only('username', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {

            return redirect('/dashboard');
            // return redirect()->intended(RouteServiceProvider::ADMIN);
        }


        return redirect('/login/admin');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        // $request->session()->invalidate();

        // $request->session()->regenerateToken();

        return redirect('/login/admin');
    }
}
