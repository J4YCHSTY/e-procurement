<?php

namespace App\Policies;

use App\Models\User;

/**
 * Semua kemampuan User Management cuma boleh dilakukan role 'it'. Dipisah
 * per method (bukan satu method umum) karena ada 2 aturan tambahan di luar
 * "harus IT" yang cuma berlaku buat aksi tertentu (lihat toggleActive &
 * updateProfile) - biar IT nggak bisa nge-lockout diri sendiri.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->role === 'it';
    }

    public function create(User $actor): bool
    {
        return $actor->role === 'it';
    }

    public function updateProfile(User $actor, User $target): bool
    {
        return $actor->role === 'it';
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->role === 'it';
    }

    /**
     * IT nggak boleh nonaktifin akun dia sendiri - kalau cuma ada satu akun
     * IT dan dia nonaktifin dirinya sendiri, nggak ada lagi yang bisa masuk
     * ke User Management buat ngaktifin balik (self-lockout).
     */
    public function toggleActive(User $actor, User $target): bool
    {
        return $actor->role === 'it' && $actor->id !== $target->id;
    }
}
