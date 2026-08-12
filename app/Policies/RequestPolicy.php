<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\User;

/**
 * Policy ini dipakai bareng buat HardwareRequest & SoftwareRequest (didaftarkan
 * di AppServiceProvider), karena keduanya punya struktur approval yang sama
 * persis: kolom 'status' dan 'user_id'.
 *
 * Aturan intinya sederhana: seorang user cuma boleh approve/reject sebuah
 * pengajuan kalau (1) role dia cocok sama tahap approval saat ini, DAN
 * (2) pengajuan itu memang lagi ada di tahap tersebut.
 */
class RequestPolicy
{
    public function approveAsHead(User $user, $request): bool
    {
        return $user->role === 'head'
            && $request->status === RequestStatus::WaitingHeadApproval->value;
    }

    public function approveAsIT(User $user, $request): bool
    {
        return $user->role === 'it'
            && $request->status === RequestStatus::WaitingItApproval->value;
    }

    public function approveAsFinance(User $user, $request): bool
    {
        return $user->role === 'finance'
            && $request->status === RequestStatus::WaitingFinanceApproval->value;
    }

    /**
     * Entry point umum: "apakah user ini boleh approve pengajuan ini,
     * di tahap manapun dia sekarang berada?" Berguna nanti buat satu
     * endpoint approve yang generic, nggak perlu tau route per-role.
     */
    public function approve(User $user, $request): bool
    {
        return match ($request->status) {
            RequestStatus::WaitingHeadApproval->value => $this->approveAsHead($user, $request),
            RequestStatus::WaitingItApproval->value => $this->approveAsIT($user, $request),
            RequestStatus::WaitingFinanceApproval->value => $this->approveAsFinance($user, $request),
            default => false, // udah APPROVED atau REJECTED, nggak bisa diapa-apain lagi
        };
    }

    /**
     * Reject pakai aturan yang sama kayak approve: siapapun yang berhak
     * approve di tahap ini, berhak juga nolak di tahap yang sama.
     */
    public function reject(User $user, $request): bool
    {
        return $this->approve($user, $request);
    }
}
