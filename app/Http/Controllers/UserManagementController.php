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
 * User Management - khusus akun yang punya can_manage_users = true (lepas
 * dari role approval-nya apa). Semua otorisasi dicek lewat UserPolicy
 * (lihat app/Policies/UserPolicy.php), bukan dicek manual di sini, biar
 * konsisten sama pola RequestPolicy yang udah dipakai di ApprovalController.
 */
class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->input('search', ''));

        $users = User::with('departement')
            // Pencarian dikerjain di database, BUKAN di frontend. Kalau
            // difilter di React, yang kesaring cuma 15 baris punya halaman
            // yang lagi kebuka - user bakal ngira datanya nggak ada padahal
            // ada di halaman lain. withQueryString() di bawah yang bikin
            // kata kuncinya kebawa waktu pindah halaman.
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'departements' => Departement::orderBy('name')->get(['id', 'name']),
            // Daftar perusahaan dikirim dari sini (sumbernya App\Models\User)
            // supaya frontend nggak nyimpen salinan daftarnya sendiri.
            'entities' => User::entityOptions(),
            'roles' => User::roleOptions(),
            'filters' => ['search' => $search],
            // Dihitung dari seluruh tabel, bukan dari halaman yang lagi
            // kebuka, biar angkanya tetap benar walau lagi difilter.
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
                'managers' => User::where('can_manage_users', true)->count(),
            ],
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

        // Nggak boleh ganti role ATAU cabut akses can_manage_users akunnya
        // sendiri lewat sini - kalau cuma ada satu akun yang bisa manage
        // user terus dia cabut aksesnya sendiri, nggak ada lagi yang bisa
        // buka User Management buat ngembaliinnya (self-lockout).
        if ($user->id === Auth::id()) {
            if ($validated['role'] !== $user->role) {
                return back()->withErrors([
                    'role' => 'Kamu tidak bisa mengubah role akun kamu sendiri. Minta rekan yang lain buat ubah ini.',
                ]);
            }

            if ($validated['can_manage_users'] !== $user->can_manage_users) {
                return back()->withErrors([
                    'can_manage_users' => 'Kamu tidak bisa mengubah akses Manajemen User punya akun kamu sendiri. Minta rekan yang lain buat ubah ini.',
                ]);
            }
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
        // Perusahaan divalidasi ke daftar resmi (User::ENTITIES), bukan lagi
        // teks bebas. Nilai lama punya user yang lagi diedit ikut diizinkan:
        // kalau ada akun hasil impor lama yang perusahaannya di luar daftar,
        // admin tetap bisa ngedit nama/jabatannya tanpa dipaksa benerin
        // kolom perusahaan dulu.
        $allowedEntities = array_keys(User::ENTITIES);

        if ($user?->entity && ! in_array($user->entity, $allowedEntities, true)) {
            $allowedEntities[] = $user->entity;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('master_employees', 'email')->ignore($user?->id),
            ],
            'entity' => ['nullable', 'string', 'max:255', Rule::in($allowedEntities)],
            'position' => ['nullable', 'string', 'max:255'],
            'departement_id' => ['nullable', 'exists:departements,id'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'can_manage_users' => ['boolean'],
        ]);

        // Dibaca terpisah lewat $request->boolean() (bukan cuma ngandelin
        // validate() di atas) biar kolomnya SELALU ada di array hasil,
        // default false kalau nggak dikirim sama sekali dari request-nya -
        // jadi nggak ada celah "checkbox nggak kecentang jadi field ilang".
        $validated['can_manage_users'] = $request->boolean('can_manage_users');

        return $validated;
    }
}
