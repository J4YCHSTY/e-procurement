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
            'approvalHistory' => $this->approvalHistoryFor($user),
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
            // Procurement bukan approval berjenjang, tapi tahap eksekusi
            // administratif setelah semua approval (head/it/finance) lolos.
            'procurement' => RequestStatus::Approved->value,
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

        // Head cuma boleh approve pengajuan dari departemennya sendiri.
        // Role lain (it/finance/procurement) tetap lintas departemen karena
        // mereka tim terpusat, bukan per-departemen.
        $scopeToOwnDepartment = function ($query) use ($user) {
            $query->whereHas('user', fn ($q) => $q->where('departement_id', $user->departement_id));
        };

        $pendingHardware = HardwareRequest::where('status', $statusToReview)
            ->when($user->role === 'head', $scopeToOwnDepartment)
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
            ->when($user->role === 'head', $scopeToOwnDepartment)
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

    /**
     * Kumpulan pengajuan yang SUDAH pernah diproses (approve/reject/tandai
     * selesai) oleh user ini di tahapnya - buat tab "Riwayat Approval".
     *
     * Beda sama pendingApprovalsFor(): di situ nyari yang STATUSNYA SAMA
     * DENGAN tahap user (masih nunggu dia). Di sini nyari yang tahap user
     * ini sudah PASTI dilewati - baik itu disetujui maju (status sekarang
     * ada di tahap lebih lanjut), atau ditolak PAS di tahap dia sendiri
     * (rejected_at_stage == tahap dia) ATAU ditolak belakangan di tahap
     * sesudahnya (yang berarti tahap dia sendiri sempat menyetujui duluan).
     *
     * Ini bisa dihitung murni dari RequestStatus::order() tanpa perlu tau
     * "siapa yang approve tahap sebelumnya", karena alur approval-nya
     * linear (Head -> IT -> Finance) - jadi begitu status sekarang udah
     * lewat urutan tahap seseorang, otomatis tahap dia pasti udah
     * disetujui duluan.
     */
    private function approvalHistoryFor(User $user)
    {
        // Procurement bukan approval berjenjang - riwayatnya cuma pengajuan
        // yang udah dia tandai selesai (status COMPLETED).
        if ($user->role === 'procurement') {
            return $this->mapApprovalHistory(
                HardwareRequest::where('status', RequestStatus::Completed->value)->with('user')->latest()->get(),
                SoftwareRequest::where('status', RequestStatus::Completed->value)->with('user')->latest()->get(),
                null
            );
        }

        $statusToReview = $this->statusToReviewFor($user);

        if ($statusToReview === null) {
            return [];
        }

        $roleStage = RequestStatus::from($statusToReview);

        // Status (bukan REJECTED) yang urutannya udah lewat tahap user ini -
        // berarti disetujui maju oleh tahap ini.
        $passedForwardStatuses = collect(RequestStatus::cases())
            ->filter(fn (RequestStatus $status) => $status !== RequestStatus::Rejected && $status->order() > $roleStage->order())
            ->map(fn (RequestStatus $status) => $status->value)
            ->values()
            ->all();

        // Tahap-tahap approval berjenjang (Head/IT/Finance) yang urutannya
        // >= tahap user ini - dipakai buat cocokin rejected_at_stage.
        $reachableRejectionStages = collect([
            RequestStatus::WaitingHeadApproval,
            RequestStatus::WaitingItApproval,
            RequestStatus::WaitingFinanceApproval,
        ])
            ->filter(fn (RequestStatus $status) => $status->order() >= $roleStage->order())
            ->map(fn (RequestStatus $status) => $status->value)
            ->values()
            ->all();

        $filterAlreadyPassedThisStage = function ($query) use ($passedForwardStatuses, $reachableRejectionStages) {
            $query->where(function ($q) use ($passedForwardStatuses, $reachableRejectionStages) {
                $q->whereIn('status', $passedForwardStatuses)
                    ->orWhere(function ($q2) use ($reachableRejectionStages) {
                        $q2->where('status', RequestStatus::Rejected->value)
                            ->whereIn('rejected_at_stage', $reachableRejectionStages);
                    });
            });
        };

        // Head cuma lihat riwayat dari departemennya sendiri, sama seperti
        // pendingApprovalsFor().
        $scopeToOwnDepartment = function ($query) use ($user) {
            $query->whereHas('user', fn ($q) => $q->where('departement_id', $user->departement_id));
        };

        $hardware = HardwareRequest::where($filterAlreadyPassedThisStage)
            ->when($user->role === 'head', $scopeToOwnDepartment)
            ->with(['user', 'rejectedBy'])
            ->latest()
            ->get();

        $software = SoftwareRequest::where($filterAlreadyPassedThisStage)
            ->when($user->role === 'head', $scopeToOwnDepartment)
            ->with(['user', 'rejectedBy'])
            ->latest()
            ->get();

        return $this->mapApprovalHistory($hardware, $software, $roleStage);
    }

    /**
     * Gabungin hardware + software history jadi satu array flat buat
     * dikirim ke frontend, sekalian nentuin 'your_decision' (approved/
     * rejected/completed) dari sudut pandang tahap user ini.
     */
    private function mapApprovalHistory($hardwareItems, $softwareItems, ?RequestStatus $roleStage)
    {
        $decisionFor = function ($item) use ($roleStage) {
            if ($roleStage === null) {
                // Procurement: satu-satunya aksi mereka adalah "tandai selesai".
                return 'completed';
            }

            if ($item->status === RequestStatus::Rejected->value) {
                // Ditolak PAS di tahap dia sendiri vs ditolak belakangan di
                // tahap sesudahnya (tahap dia sendiri berarti udah setuju duluan).
                return $item->rejected_at_stage === $roleStage->value ? 'rejected' : 'approved';
            }

            return 'approved';
        };

        $mapHardware = fn ($item) => [
            'id' => $item->id,
            'type' => 'Hardware',
            'request_date' => $item->request_date,
            'requester_name' => $item->user?->name,
            'title' => $item->hardware_type,
            'detail' => $item->hardware_recommendation,
            'justification' => $item->justification,
            'status' => $item->status,
            'your_decision' => $decisionFor($item),
            'rejected_by_name' => $item->rejectedBy?->name,
            'rejected_at' => $item->rejected_at,
        ];

        $mapSoftware = fn ($item) => [
            'id' => $item->id,
            'type' => 'Software',
            'request_date' => $item->request_date,
            'requester_name' => $item->user?->name,
            'title' => $item->software_name,
            'detail' => $item->license_count.' lisensi, '.$item->duration_months.' bulan',
            'justification' => $item->justification,
            'status' => $item->status,
            'your_decision' => $decisionFor($item),
            'rejected_by_name' => $item->rejectedBy?->name,
            'rejected_at' => $item->rejected_at,
        ];

        return $hardwareItems->map($mapHardware)->concat($softwareItems->map($mapSoftware))->values();
    }
}
