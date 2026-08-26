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
            && $request->status === RequestStatus::WaitingHeadApproval->value
            // Head cuma boleh approve pengajuan dari departemennya sendiri.
            // Ini pengaman di level otorisasi, bukan cuma di query dashboard -
            // jadi walau ada yang nembak POST langsung ke endpoint approve
            // pakai ID pengajuan departemen lain, tetap ketolak di sini.
            && $user->departement_id === $request->user->departement_id;
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
     * Tahap procurement itu eksekusi administratif (nandain barang/lisensi
     * udah diserahkan ke pemohon), bukan keputusan approval berjenjang kayak
     * head/it/finance. Makanya cuma dicek role & statusnya APPROVED,
     * gak ada aturan reject yang nempel ke tahap ini (lihat method reject()).
     */
    public function approveAsProcurement(User $user, $request): bool
    {
        return $user->role === 'procurement'
            && $request->status === RequestStatus::Approved->value;
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
            RequestStatus::Approved->value => $this->approveAsProcurement($user, $request),
            default => false, // udah COMPLETED atau REJECTED, nggak bisa diapa-apain lagi
        };
    }

    /**
     * Reject pakai aturan yang sama kayak approve: siapapun yang berhak
     * approve di tahap ini, berhak juga nolak di tahap yang sama.
     *
     * Kecuali status APPROVED: itu tahap eksekusi administratif procurement,
     * bukan keputusan, jadi gak boleh direject sama sekali - cuma bisa
     * ditandai selesai lewat approve().
     */
    public function reject(User $user, $request): bool
    {
        if ($request->status === RequestStatus::Approved->value) {
            return false;
        }

        return $this->approve($user, $request);
    }
}
