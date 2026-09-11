<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function approveHardware(HardwareRequest $hardwareRequest): RedirectResponse
    {
        return $this->handleApprove($hardwareRequest);
    }

    public function rejectHardware(HardwareRequest $hardwareRequest): RedirectResponse
    {
        return $this->handleReject($hardwareRequest);
    }

    public function approveSoftware(SoftwareRequest $softwareRequest): RedirectResponse
    {
        return $this->handleApprove($softwareRequest);
    }

    public function rejectSoftware(SoftwareRequest $softwareRequest): RedirectResponse
    {
        return $this->handleReject($softwareRequest);
    }

    /**
     * Logic approve yang sama persis dipakai buat hardware & software,
     * makanya ditaruh di satu tempat. $request di sini maksudnya instance
     * model (HardwareRequest/SoftwareRequest), bukan HTTP request.
     */
    private function handleApprove(HardwareRequest|SoftwareRequest $request): RedirectResponse
    {
        // authorize() otomatis manggil RequestPolicy::approve($user, $request).
        // Kalau nggak lolos, Laravel otomatis balikin 403 - nggak perlu dicek manual.
        $this->authorize('approve', $request);

        // Menyetujui berarti tanda tangan penyetuju ikut menempel di form yang
        // dicetak nanti - jadi tanda tangannya harus sudah ada dulu.
        //
        // Sengaja TIDAK ditaruh di RequestPolicy: policy menjawab "siapa yang
        // berwenang", sedangkan ini soal "syaratnya sudah lengkap atau belum".
        // Mencampur keduanya bikin penyetuju yang sah dapat 403 tanpa
        // penjelasan, padahal yang kurang cuma satu langkah yang bisa dia
        // selesaikan sendiri.
        if (! Auth::user()->has_signature) {
            return redirect()->back()->with(
                'error',
                'Kamu belum punya tanda tangan digital. Buat dulu lewat kotak di atas daftar approval (cukup sekali), baru pengajuannya bisa diproses.'
            );
        }

        $currentStatus = RequestStatus::from($request->status);

        // Endpoint ini cuma buat KEPUTUSAN (setuju / tolak). Tahap sesudahnya
        // punya isian yang wajib - catatan pengiriman, nomor seri - dan itu
        // dikerjakan lewat FulfillmentController. Tanpa penjaga ini, siapapun
        // yang menembak POST ke sini bisa memajukan pengajuan sampai BAST
        // tanpa pernah mengisi nomor seri barangnya.
        if (! $currentStatus->isDecisionStage()) {
            return redirect()->back()->with(
                'error',
                'Tahap ini diproses lewat halaman detail pengajuan, bukan dari daftar approval.'
            );
        }

        $request->status = $currentStatus->next()->value;
        $request->save();

        $request->recordEvent($request->status, Auth::user(), $currentStatus->value);

        return redirect()->back()->with('success', 'Pengajuan berhasil disetujui.');
    }

    /**
     * Menolak sengaja TIDAK menuntut tanda tangan: penolakan menghentikan
     * alur, tidak ada dokumen yang ditandatangani. Menahannya cuma akan
     * memaksa orang membuat tanda tangan untuk sesuatu yang tidak dipakai.
     */
    private function handleReject(HardwareRequest|SoftwareRequest $request): RedirectResponse
    {
        $this->authorize('reject', $request);

        // Snapshot tahap approval tempat dia ditolak SEBELUM status ditimpa
        // jadi REJECTED - dipakai buat riwayat approval per role (lihat
        // DashboardController::approvalHistoryFor()) biar bisa dibedain
        // "ditolak sama Kepala Departemen" vs "ditolak sama Head of IT".
        $stageBeforeRejection = $request->status;
        $request->rejected_at_stage = $stageBeforeRejection;
        $request->rejected_by_id = Auth::id();
        $request->rejected_at = now();
        $request->status = RequestStatus::Rejected->value;
        $request->save();

        $request->recordEvent(
            RequestStatus::Rejected->value,
            Auth::user(),
            $stageBeforeRejection,
        );

        return redirect()->back()->with('success', 'Pengajuan ditolak.');
    }
}
