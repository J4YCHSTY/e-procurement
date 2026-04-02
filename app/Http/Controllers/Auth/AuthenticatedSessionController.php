<?php

namespace App\Http\Controllers\Auth;

use App\Models\MasterEmployee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
// use App\Http\Requests\Auth\LoginRequest;
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
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $master = MasterEmployee::where('email', $request->email)->first();

        if (!$master) {
            return back()->withErrors([
                'email' => 'EMAIL TIDAK TERDAFTAR PADA DATABASE',
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => $master->email],
            [
                'name' => $master->name,
                'entity' => $master->entity,
                'position' => $master->position,
                'departement_id' => $master->departement_id,
                'role' => $master->role,
                'password' => Hash::make('SSO_DUMMY_PASSWORD')
            ]
        );

        Auth::login($user);

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
