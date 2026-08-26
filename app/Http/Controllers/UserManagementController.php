<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * User Management - khusus role 'it'. Semua otorisasi dicek lewat
 * UserPolicy (lihat app/Policies/UserPolicy.php), bukan dicek manual di
 * sini, biar konsisten sama pola RequestPolicy yang udah dipakai di
 * ApprovalController.
 */
class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('departement')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'departements' => Departement::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $this->validatedProfile($request);

        User::create([
            ...$validated,
            'password' => Hash::make(User::defaultPassword()),
            'email_verified_at' => now(),
        ]);

        return back()->with('success', 'Akun baru berhasil dibuat. Password default: '.User::defaultPassword());
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('updateProfile', $user);

        $validated = $this->validatedProfile($request, $user);

        // IT nggak boleh ganti role akunnya sendiri lewat sini - kalau cuma
        // ada satu akun IT dan rolenya kepencet ganti ke 'user', nggak ada
        // lagi yang bisa buka User Management buat ngembaliinnya.
        if ($user->id === Auth::id() && $validated['role'] !== $user->role) {
            return back()->withErrors([
                'role' => 'Kamu tidak bisa mengubah role akun kamu sendiri. Minta IT lain buat ubah ini.',
            ]);
        }

        $user->update($validated);

        return back()->with('success', 'Profil user berhasil diperbarui.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $user->update(['password' => Hash::make(User::defaultPassword())]);

        return back()->with('success', "Password {$user->name} sudah direset ke default (".User::defaultPassword().').');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        $message = $user->is_active
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan.";

        return back()->with('success', $message);
    }

    /**
     * Validasi data profil yang dipakai bareng buat store() & update().
     * $user null berarti lagi create (email unique nggak perlu ngecualiin
     * ID siapa-siapa).
     */
    private function validatedProfile(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('master_employees', 'email')->ignore($user?->id),
            ],
            'entity' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'departement_id' => ['nullable', 'exists:departements,id'],
            'role' => ['required', Rule::in(['user', 'head', 'it', 'finance', 'procurement'])],
        ]);
    }
}
