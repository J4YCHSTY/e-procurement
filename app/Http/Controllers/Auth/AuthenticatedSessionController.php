<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * Dulu di sini ada "simulasi SSO" (cukup cek email terdaftar, password
     * nggak pernah dicek). Sekarang dikembalikan ke alur standar Laravel:
     * LoginRequest::authenticate() yang beneran manggil Auth::attempt()
     * (plus rate-limiting bawaan biar nggak bisa di-brute-force). Ini perlu
     * supaya fitur ganti password sendiri (Profile) dan reset password lewat
     * email jadi ada gunanya - sebelum ini, ganti password nggak ngaruh
     * apa-apa ke proses login.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
