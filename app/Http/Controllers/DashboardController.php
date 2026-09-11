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
            'canApprove' => $this->statusesToReviewFor($user) !== [],
        ]);
    }

    /**
     * Status mana saja yang jadi jatah user ini, berdasarkan role-nya.
     * Array kosong artinya role ini nggak punya jatah sama sekali (role 'user').
     *
     * IT Admin memegang LEBIH DARI SATU status - dia mengawal berkasnya dari
     * saat diserahkan ke procurement sampai barangnya sampai. Itu sebabnya
     * method ini mengembalikan array, bukan satu nilai.
     *
     * @return array<int, string>
     */
    private function statusesToReviewFor(User $user): array
    {
        return match ($user->role) {
            'head' => [RequestStatus::WaitingHeadApproval->value],
            'it_head' => [RequestStatus::WaitingHeadItApproval->value],
            'it' => array_map(
                fn (RequestStatus $status) => $status->value,
                RequestStatus::itAdminStages(),
            ),
            default => [],
        };
    }

    /**
     * Tahap TERAKHIR yang jadi tanggung jawab user ini.
     *
     * Dipakai buat memisahkan "masih jadi urusan dia" dari "sudah lewat dia"
     * di riwayat approval. Harus tahap terakhir, bukan yang pertama: kalau
     * pakai yang pertama, pengajuan yang masih ada di tahap kedua IT Admin
     * akan muncul di antrean DAN di riwayat sekaligus.
     */
    private function lastStageFor(User $user): ?RequestStatus
    {
        $statuses = $this->statusesToReviewFor($user);

        if ($statuses === []) {
            return null;
        }

        return RequestStatus::from(end($statuses));
    }

    /**
     * Kumpulan pengajuan (hardware + software digabung) yang lagi nunggu
     * approval dari user ini di tahap yang jadi jatahnya.
     */
    private function pendingApprovalsFor(User $user)
    {
        $statusesToReview = $this->statusesToReviewFor($user);

        if ($statusesToReview === []) {
            return [];
        }

        // Head cuma boleh approve pengajuan dari departemennya sendiri.
        // Role lain (it_head/it) tetap lintas departemen karena mereka tim
        // terpusat, bukan per-departemen.
        $scopeToOwnDepartment = function ($query) use ($user) {
            $query->whereHas('user', fn ($q) => $q->where('departement_id', $user->departement_id));
        };

        $pendingHardware = HardwareRequest::whereIn('status', $statusesToReview)
            ->when($user->role === 'head', $scopeToOwnDepartment)
            ->with('user')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'type' => 'Hardware',
                'request_date' => $item->request_date,
                'requester_name' => $item->user?->name,
                'title' => $item->hardware_type,
                'detail' => $item->hardware_recommendation === 'custom'
                    ? $item->custom_hardware_name
                    : $item->hardware_recommendation,
                'justification' => $item->justification,
                // URL-nya cuma dikirim kalau lampirannya memang ada; gambarnya
                // sendiri tetap lewat route ber-otorisasi, bukan folder publik.
                'preference_image_url' => $item->preference_image_path
                    ? route('request.hardware.preference-image', $item->id)
                    : null,
                'detail_url' => route('request.show', ['type' => 'hardware', 'id' => $item->id]),
                'status' => $item->status,
            ]);

        $pendingSoftware = SoftwareRequest::whereIn('status', $statusesToReview)
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
                'preference_image_url' => $item->preference_image_path
                    ? route('request.software.preference-image', $item->id)
                    : null,
                'detail_url' => route('request.show', ['type' => 'software', 'id' => $item->id]),
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
     * "siapa yang approve tahap sebelumnya", karena alurnya linear
     * (Head -> Head of IT -> IT Admin) - jadi begitu status sekarang udah
     * lewat urutan tahap seseorang, otomatis tahap dia pasti udah
     * disetujui duluan.
     */
    private function approvalHistoryFor(User $user)
    {
        $roleStage = $this->lastStageFor($user);

        if ($roleStage === null) {
            return [];
        }

        // Status (bukan REJECTED) yang urutannya udah lewat tahap user ini -
        // berarti disetujui maju oleh tahap ini.
        $passedForwardStatuses = collect(RequestStatus::cases())
            ->filter(fn (RequestStatus $status) => $status !== RequestStatus::Rejected && $status->order() > $roleStage->order())
            ->map(fn (RequestStatus $status) => $status->value)
            ->values()
            ->all();

        // Tahap-tahap yang berupa keputusan (Head / Head of IT) yang urutannya
        // >= tahap user ini - dipakai buat cocokin rejected_at_stage.
        $reachableRejectionStages = collect(RequestStatus::decisionStages())
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
            if ($roleStage === null || in_array($roleStage, RequestStatus::itAdminStages(), true)) {
                // IT Admin tidak menyetujui apa-apa - dia mencatat perjalanan
                // barangnya, jadi label riwayatnya juga beda.
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
            'detail' => $item->hardware_recommendation === 'custom'
                    ? $item->custom_hardware_name
                    : $item->hardware_recommendation,
            'justification' => $item->justification,
            'preference_image_url' => $item->preference_image_path
                ? route('request.hardware.preference-image', $item->id)
                : null,
            'detail_url' => route('request.show', ['type' => 'hardware', 'id' => $item->id]),
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
            'preference_image_url' => $item->preference_image_path
                ? route('request.software.preference-image', $item->id)
                : null,
            'detail_url' => route('request.show', ['type' => 'software', 'id' => $item->id]),
            'status' => $item->status,
            'your_decision' => $decisionFor($item),
            'rejected_by_name' => $item->rejectedBy?->name,
            'rejected_at' => $item->rejected_at,
        ];

        return $hardwareItems->map($mapHardware)->concat($softwareItems->map($mapSoftware))->values();
    }
}
