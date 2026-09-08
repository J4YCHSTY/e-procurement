<?php

namespace App\Policies;

use App\Models\User;

/**
 * Semua kemampuan User Management dicek lewat kolom 'can_manage_users',
 * BUKAN lewat 'role'. Dua hal ini sengaja dipisah: 'role' ngatur siapa yang
 * approve pengajuan di tahap mana (head/it/finance/procurement), sedangkan
 * 'can_manage_users' ngatur siapa yang boleh buka menu ini - dua orang bisa
 * punya kombinasi berbeda (ada yang cuma urus akun tanpa ikut approve apa-
 * apa, ada yang approve di tahap IT tapi nggak urus akun, dst).
 *
 * Dipisah per method (bukan satu method umum) karena ada 1 aturan tambahan
 * di luar "harus punya can_manage_users" yang cuma berlaku buat aksi
 * tertentu (lihat toggleActive) - biar orang yang bisa akses menu ini
 * nggak bisa nge-lockout diri sendiri.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can_manage_users;
    }

    public function create(User $actor): bool
    {
        return $actor->can_manage_users;
    }

    public function updateProfile(User $actor, User $target): bool
    {
        return $actor->can_manage_users;
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->can_manage_users;
    }

    /**
     * Nggak boleh nonaktifin akun sendiri - kalau cuma ada satu akun yang
     * bisa manage user dan dia nonaktifin dirinya sendiri, nggak ada lagi
     * yang bisa masuk ke User Management buat ngaktifin balik (self-lockout).
     */
    public function toggleActive(User $actor, User $target): bool
    {
        return $actor->can_manage_users && $actor->id !== $target->id;
    }
}
