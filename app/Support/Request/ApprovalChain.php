<?php

namespace App\Support\Request;

use App\Enums\RequestStatus;
use App\Models\User;

/**
 * Nentuin pengajuan baru harus mulai dari tahap mana.
 *
 * Normalnya selalu mulai dari kepala departemen pemohon. Tapi ada satu kasus
 * yang perlu diperlakukan khusus: pengajuan yang datang dari orang IT sendiri.
 * Kepala departemen mereka ya Head of IT itu sendiri, jadi kalau alurnya
 * dipaksa seragam, orang yang sama diminta menyetujui dua kali dan tanda
 * tangan yang sama menempel dua kali di satu dokumen. Di sidang itu gampang
 * dipersoalkan keabsahannya, dan di praktiknya cuma bikin kerja dobel.
 *
 * Jadi buat departemen yang kepalanya adalah Head of IT, pengajuannya langsung
 * masuk ke tahap Head of IT - sekali setuju, langsung ke IT Admin.
 *
 * Dicek dari isi data, bukan dari ID departemen yang di-hardcode, supaya
 * tetap benar kalau nanti struktur departemennya berubah.
 */
class ApprovalChain
{
    public function startingStatusFor(User $requester): RequestStatus
    {
        if ($this->departmentIsLedByItHead($requester->departement_id)) {
            return RequestStatus::WaitingHeadItApproval;
        }

        return RequestStatus::WaitingHeadApproval;
    }

    /**
     * Sebuah departemen dianggap dikepalai Head of IT kalau di dalamnya ada
     * akun aktif ber-role it_head DAN nggak ada kepala departemen biasa.
     *
     * Syarat kedua itu penting: kalau suatu saat departemen IT punya kepala
     * sendiri yang terpisah dari Head of IT, alurnya balik normal dengan
     * sendirinya - dua orang yang berbeda, dua tahap yang berbeda.
     */
    private function departmentIsLedByItHead(?int $departementId): bool
    {
        if ($departementId === null) {
            return false;
        }

        $roles = User::query()
            ->where('departement_id', $departementId)
            ->where('is_active', true)
            ->whereIn('role', ['head', 'it_head'])
            ->pluck('role');

        return $roles->contains('it_head') && ! $roles->contains('head');
    }
}
