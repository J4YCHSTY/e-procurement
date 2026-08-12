<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        $hardwareHistory = HardwareRequest::where('user_id', $user->id)->latest()->get();
        $softwareHistory = SoftwareRequest::where('user_id', $user->id)->latest()->get();

        return Inertia::render('Dashboard', [
            'hardwareHistory' => $hardwareHistory,
            'softwareHistory' => $softwareHistory,
            'pendingApprovals' => $this->pendingApprovalsFor($user),
            // Dipakai frontend buat nentuin nampilin tab approval atau nggak.
            // Ganti isManager yang lama (nebak dari string position) jadi
            // beneran ngecek: role user ini punya jatah approval di tahap manapun?
            'canApprove' => $this->statusToReviewFor($user) !== null,
        ]);
    }

    /**
     * Status mana yang jadi jatah approval user ini, berdasarkan role-nya.
     * null artinya role ini nggak punya jatah approval sama sekali (role 'user').
     */
    private function statusToReviewFor(User $user): ?string
    {
        return match ($user->role) {
            'head' => RequestStatus::WaitingHeadApproval->value,
            'it' => RequestStatus::WaitingItApproval->value,
            'finance' => RequestStatus::WaitingFinanceApproval->value,
            default => null,
        };
    }

    /**
     * Kumpulan pengajuan (hardware + software digabung) yang lagi nunggu
     * approval dari user ini di tahap yang jadi jatahnya.
     */
    private function pendingApprovalsFor(User $user)
    {
        $statusToReview = $this->statusToReviewFor($user);

        if ($statusToReview === null) {
            return [];
        }

        $pendingHardware = HardwareRequest::where('status', $statusToReview)
            ->with('user')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'type' => 'Hardware',
                'request_date' => $item->request_date,
                'requester_name' => $item->user?->name,
                'title' => $item->hardware_type,
                'detail' => $item->hardware_recommendation,
                'justification' => $item->justification,
                'status' => $item->status,
            ]);

        $pendingSoftware = SoftwareRequest::where('status', $statusToReview)
            ->with('user')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'type' => 'Software',
                'request_date' => $item->request_date,
                'requester_name' => $item->user?->name,
                'title' => $item->software_name,
                'detail' => $item->license_count.' lisensi, '.$item->duration_months.' bulan',
                'justification' => $item->justification,
                'status' => $item->status,
            ]);

        return $pendingHardware->concat($pendingSoftware)->values();
    }
}
