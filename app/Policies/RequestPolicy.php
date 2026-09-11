<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Models\User;

/**
 * Policy ini dipakai bareng buat HardwareRequest & SoftwareRequest (didaftarkan
 * di AppServiceProvider), karena keduanya punya struktur alur yang sama persis:
 * kolom 'status' dan 'user_id'.
 *
 * Aturan intinya sederhana: seorang user cuma boleh memproses sebuah pengajuan
 * kalau (1) role dia cocok sama tahap saat ini, DAN (2) pengajuan itu memang
 * lagi ada di tahap tersebut.
 *
 * Rantai perannya:
 *   head    -> kepala departemen pemohon        (WAITING_FOR_HEAD_APPROVAL)
 *   it_head -> Head of IT, lintas departemen    (WAITING_FOR_HEAD_IT_APPROVAL)
 *   it      -> IT Admin, lintas departemen      (ON_EXTERNAL_PROCESS,
 *                                                 ITEM_ON_THE_WAY)
 *   pemohon -> menutup alurnya sendiri           (WAITING_FOR_BAST_SIGNATURE)
 */
class RequestPolicy
{
    /**
     * Siapa yang boleh melihat isi sebuah pengajuan - termasuk lampiran
     * preferensi barangnya. Bukan cuma pemohonnya: para penyetuju di rantai
     * juga perlu melihat barang apa persisnya yang diminta, justru itu gunanya
     * lampiran itu ada.
     */
    public function view(User $user, $request): bool
    {
        if ($user->id === $request->user_id) {
            return true;
        }

        if ($user->role === 'head') {
            return $user->departement_id === $request->user?->departement_id;
        }

        return in_array($user->role, ['it_head', 'it'], true);
    }

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

    /**
     * Head of IT menilai kelayakan teknis, dan itu berlaku buat pengajuan dari
     * departemen manapun - beda sama kepala departemen yang cuma mengurus
     * anak buahnya sendiri. Makanya di sini nggak ada pengecekan departemen.
     */
    public function approveAsHeadIT(User $user, $request): bool
    {
        return $user->role === 'it_head'
            && $request->status === RequestStatus::WaitingHeadItApproval->value;
    }

    /**
     * Tahap IT Admin itu pencatatan administratif (mencatat perjalanan barang
     * sampai BAST terbit), bukan keputusan approval berjenjang kayak
     * head/it_head. Makanya cuma dicek role & statusnya, dan nggak ada aturan
     * reject yang nempel ke tahap ini (lihat reject()).
     */
    public function approveAsITAdmin(User $user, $request): bool
    {
        if ($user->role !== 'it') {
            return false;
        }

        $status = RequestStatus::tryFrom($request->status);

        return $status !== null && in_array($status, RequestStatus::itAdminStages(), true);
    }

    /**
     * Menandatangani BAST itu hak PEMOHON, dan cuma pemohon.
     *
     * Sengaja tidak diberikan ke IT Admin sekalipun dia yang menyerahkan
     * barangnya: seluruh gunanya BAST adalah bukti bahwa penerima mengakui
     * barangnya sudah diterima sesuai. Kalau pihak yang menyerahkan bisa
     * menandatangani atas nama penerima, dokumennya kehilangan seluruh makna.
     */
    public function signBast(User $user, $request): bool
    {
        return $user->id === $request->user_id
            && $request->status === RequestStatus::WaitingBastSignature->value;
    }

    /**
     * Entry point umum: "apakah user ini boleh memproses pengajuan ini,
     * di tahap manapun dia sekarang berada?"
     */
    public function approve(User $user, $request): bool
    {
        return match ($request->status) {
            RequestStatus::WaitingHeadApproval->value => $this->approveAsHead($user, $request),
            RequestStatus::WaitingHeadItApproval->value => $this->approveAsHeadIT($user, $request),
            RequestStatus::OnExternalProcess->value,
            RequestStatus::ItemOnTheWay->value => $this->approveAsITAdmin($user, $request),
            // WAITING_FOR_BAST_SIGNATURE sengaja TIDAK ada di sini: yang
            // memajukannya bukan penyetuju, tapi pemohon lewat signBast().
            default => false, // udah COMPLETED atau REJECTED, nggak bisa diapa-apain lagi
        };
    }

    /**
     * Reject pakai aturan yang sama kayak approve: siapapun yang berhak
     * approve di tahap ini, berhak juga nolak di tahap yang sama.
     *
     * Kecuali ON_EXTERNAL_PROCESS. Di tahap itu berkasnya sudah dilempar ke
     * procurement/finance di luar sistem, jadi kalau di sana batal, itu
     * pembatalan - bukan penolakan approval. Dua hal itu sengaja nggak
     * dicampur: kalau IT Admin bisa menekan "Tolak" di sini, riwayatnya jadi
     * berbunyi seolah tim IT yang menolak, padahal keputusannya datang dari
     * luar sistem dan alasannya nggak tercatat di sini.
     */
    public function reject(User $user, $request): bool
    {
        $status = RequestStatus::tryFrom($request->status);

        if ($status === null || ! $status->isDecisionStage()) {
            return false;
        }

        return $this->approve($user, $request);
    }
}
