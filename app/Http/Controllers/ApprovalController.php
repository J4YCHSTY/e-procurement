<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use Illuminate\Http\RedirectResponse;

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

        $currentStatus = RequestStatus::from($request->status);
        $request->status = $currentStatus->next()->value;
        $request->save();

        return redirect()->back()->with('success', 'Pengajuan berhasil disetujui.');
    }

    private function handleReject(HardwareRequest|SoftwareRequest $request): RedirectResponse
    {
        $this->authorize('reject', $request);

        // Snapshot tahap approval tempat dia ditolak SEBELUM status ditimpa
        // jadi REJECTED - dipakai buat riwayat approval per role (lihat
        // DashboardController::approvalHistoryFor()) biar bisa dibedain
        // "ditolak sama Head" vs "ditolak sama IT" vs "ditolak sama Finance".
        $request->rejected_at_stage = $request->status;
        $request->rejected_by_id = auth()->id();
        $request->rejected_at = now();
        $request->status = RequestStatus::Rejected->value;
        $request->save();

        return redirect()->back()->with('success', 'Pengajuan ditolak.');
    }
}
