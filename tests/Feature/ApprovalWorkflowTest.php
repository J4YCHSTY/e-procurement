<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Departement;
use App\Models\HardwareRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, int $departementId, string $email): User
    {
        return User::create([
            'name' => ucfirst($role).' Dept'.$departementId,
            'email' => $email,
            'password' => bcrypt('password'),
            'entity' => 'Kantor Pusat',
            'position' => ucfirst($role),
            'departement_id' => $departementId,
            'role' => $role,
        ]);
    }

    private function makeHardwareRequest(User $requester, RequestStatus $status): HardwareRequest
    {
        return HardwareRequest::create([
            'user_id' => $requester->id,
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'l1',
            'justification' => 'Butuh laptop baru buat kerja',
            'digital_signature' => true,
            'status' => $status->value,
        ]);
    }

    private function seedDepartements(): void
    {
        Departement::forceCreate(['id' => 1, 'name' => 'Departemen A']);
        Departement::forceCreate(['id' => 2, 'name' => 'Departemen B']);
    }

    // --- Head: harus difilter per departemen ---

    public function test_head_can_approve_request_from_own_department(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $response = $this->actingAs($head)->post(route('request.hardware.approve', $request->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(RequestStatus::WaitingItApproval->value, $request->fresh()->status);
    }

    public function test_head_cannot_approve_request_from_other_department(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $response = $this->actingAs($head)->post(route('request.hardware.approve', $request->id));

        $response->assertForbidden();
        $this->assertEquals(RequestStatus::WaitingHeadApproval->value, $request->fresh()->status);
    }

    public function test_head_cannot_reject_request_from_other_department(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $response = $this->actingAs($head)->post(route('request.hardware.reject', $request->id));

        $response->assertForbidden();
        $this->assertEquals(RequestStatus::WaitingHeadApproval->value, $request->fresh()->status);
    }

    public function test_dashboard_pending_approvals_for_head_excludes_other_department(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $ownDeptRequester = $this->makeUser('user', 1, 'user-a@test.com');
        $otherDeptRequester = $this->makeUser('user', 2, 'user-b@test.com');
        $ownReq = $this->makeHardwareRequest($ownDeptRequester, RequestStatus::WaitingHeadApproval);
        $otherReq = $this->makeHardwareRequest($otherDeptRequester, RequestStatus::WaitingHeadApproval);

        $response = $this->actingAs($head)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $response->assertOk();
        $pendingIds = collect($response->json('props.pendingApprovals'))->pluck('id')->all();

        $this->assertContains($ownReq->id, $pendingIds);
        $this->assertNotContains($otherReq->id, $pendingIds);
    }

    // --- IT & Finance: tetap lintas departemen (gak berubah) ---

    public function test_it_can_approve_request_regardless_of_department(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingItApproval);

        $response = $this->actingAs($it)->post(route('request.hardware.approve', $request->id));

        $response->assertRedirect();
        $this->assertEquals(RequestStatus::WaitingFinanceApproval->value, $request->fresh()->status);
    }

    public function test_finance_approval_moves_status_to_approved(): void
    {
        $this->seedDepartements();
        $finance = $this->makeUser('finance', 1, 'finance@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingFinanceApproval);

        $response = $this->actingAs($finance)->post(route('request.hardware.approve', $request->id));

        $response->assertRedirect();
        $this->assertEquals(RequestStatus::Approved->value, $request->fresh()->status);
    }

    // --- Procurement: eksekusi administratif, bukan approval ---

    public function test_procurement_can_mark_approved_request_as_completed(): void
    {
        $this->seedDepartements();
        $procurement = $this->makeUser('procurement', 1, 'procurement@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::Approved);

        $response = $this->actingAs($procurement)->post(route('request.hardware.approve', $request->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals(RequestStatus::Completed->value, $request->fresh()->status);
    }

    public function test_procurement_cannot_reject_approved_request(): void
    {
        $this->seedDepartements();
        $procurement = $this->makeUser('procurement', 1, 'procurement@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::Approved);

        $response = $this->actingAs($procurement)->post(route('request.hardware.reject', $request->id));

        $response->assertForbidden();
        $this->assertEquals(RequestStatus::Approved->value, $request->fresh()->status);
    }

    public function test_procurement_cannot_touch_request_still_awaiting_earlier_approval(): void
    {
        $this->seedDepartements();
        $procurement = $this->makeUser('procurement', 1, 'procurement@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingFinanceApproval);

        $response = $this->actingAs($procurement)->post(route('request.hardware.approve', $request->id));

        $response->assertForbidden();
        $this->assertEquals(RequestStatus::WaitingFinanceApproval->value, $request->fresh()->status);
    }

    // --- Flash message beneran nyampe ke Inertia props ---

    public function test_flash_success_message_is_shared_to_inertia_after_approve(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));

        $response = $this->actingAs($head)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertEquals('Pengajuan berhasil disetujui.', $response->json('props.flash.success'));
    }

    // --- Validasi form software ---

    public function test_store_software_request_requires_software_type(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($user)->post(route('request.software.store'), [
            'request_date' => now()->toDateString(),
            'software_name' => 'Adobe Photoshop',
            'software_type' => '',
            'software_usage' => 'individu',
            'license_count' => 1,
            'duration_months' => 12,
            'estimated_cost' => 500000,
            'justification' => 'Perlu buat desain',
            'digital_signature' => true,
        ]);

        $response->assertSessionHasErrors('software_type');
        $this->assertDatabaseCount('software_requests', 0);
    }

    public function test_store_software_request_license_type_does_not_require_duration(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($user)->post(route('request.software.store'), [
            'request_date' => now()->toDateString(),
            'software_name' => 'Windows Server',
            'software_type' => 'License',
            'software_usage' => 'team',
            'license_count' => 5,
            'duration_months' => '',
            'estimated_cost' => 10000000,
            'justification' => 'Perlu buat server baru',
            'digital_signature' => true,
        ]);

        $response->assertSessionDoesntHaveErrors('duration_months');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('software_requests', [
            'software_name' => 'Windows Server',
            'duration_months' => null,
        ]);
    }

    public function test_store_software_request_requires_duration_when_not_license(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($user)->post(route('request.software.store'), [
            'request_date' => now()->toDateString(),
            'software_name' => 'Zoom',
            'software_type' => 'Subscription',
            'software_usage' => 'team',
            'license_count' => 10,
            'duration_months' => '',
            'estimated_cost' => 2000000,
            'justification' => 'Perlu buat meeting',
            'digital_signature' => true,
        ]);

        $response->assertSessionHasErrors('duration_months');
        $this->assertDatabaseCount('software_requests', 0);
    }

    public function test_store_software_request_rejects_invalid_software_usage(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($user)->post(route('request.software.store'), [
            'request_date' => now()->toDateString(),
            'software_name' => 'Zoom',
            'software_type' => 'Subscription',
            'software_usage' => 'departemen-seluruh-perusahaan',
            'license_count' => 10,
            'duration_months' => 12,
            'estimated_cost' => 2000000,
            'justification' => 'Perlu buat meeting',
            'digital_signature' => true,
        ]);

        $response->assertSessionHasErrors('software_usage');
        $this->assertDatabaseCount('software_requests', 0);
    }

    public function test_store_software_request_succeeds_with_valid_data(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($user)->post(route('request.software.store'), [
            'request_date' => now()->toDateString(),
            'software_name' => 'Zoom',
            'software_type' => 'Subscription',
            'software_usage' => 'team',
            'license_count' => 10,
            'duration_months' => 12,
            'estimated_cost' => 2000000,
            'justification' => 'Perlu buat meeting',
            'digital_signature' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('software_requests', [
            'software_name' => 'Zoom',
            'software_usage' => 'team',
            'status' => RequestStatus::WaitingHeadApproval->value,
        ]);
    }

    // --- Jejak audit penolakan (rejected_by_id / rejected_at / rejected_at_stage) ---

    public function test_reject_records_rejecting_actor_and_stage_snapshot(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.reject', $request->id));

        $fresh = $request->fresh();
        $this->assertEquals(RequestStatus::Rejected->value, $fresh->status);
        $this->assertEquals($head->id, $fresh->rejected_by_id);
        $this->assertNotNull($fresh->rejected_at);
        $this->assertEquals(RequestStatus::WaitingHeadApproval->value, $fresh->rejected_at_stage);
    }

    // --- Riwayat Approval: Head ---

    public function test_head_approval_history_includes_forward_approved_item_from_own_department(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));

        $response = $this->actingAs($head)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $history = collect($response->json('props.approvalHistory'));
        $entry = $history->firstWhere('id', $request->id);

        $this->assertNotNull($entry, 'Pengajuan yang sudah di-approve Head harus muncul di riwayat approval Head.');
        $this->assertEquals('approved', $entry['your_decision']);
    }

    public function test_head_approval_history_includes_item_rejected_later_by_it(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $it = $this->makeUser('it', 1, 'it@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($it)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($head)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $history = collect($response->json('props.approvalHistory'));
        $entry = $history->firstWhere('id', $request->id);

        $this->assertNotNull($entry, 'Head tetap harus lihat pengajuan ini di riwayatnya walau belakangan ditolak IT.');
        $this->assertEquals('approved', $entry['your_decision'], 'Head yang menyetujui, bukan yang menolak - jadi keputusan Head tetap "approved".');
        $this->assertEquals($it->name, $entry['rejected_by_name']);
    }

    public function test_head_approval_history_excludes_other_department(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $head2 = $this->makeUser('head', 2, 'head2@test.com');
        $requesterOtherDept = $this->makeUser('user', 2, 'user-b@test.com');
        $requestOtherDept = $this->makeHardwareRequest($requesterOtherDept, RequestStatus::WaitingHeadApproval);

        // Disetujui oleh head departemen lain, supaya statusnya "lewat tahap head".
        $this->actingAs($head2)->post(route('request.hardware.approve', $requestOtherDept->id));

        $response = $this->actingAs($head)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $historyIds = collect($response->json('props.approvalHistory'))->pluck('id')->all();

        $this->assertNotContains($requestOtherDept->id, $historyIds);
    }

    // --- Riwayat Approval: IT ---

    public function test_it_approval_history_excludes_item_rejected_by_head_before_reaching_it(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $it = $this->makeUser('it', 1, 'it@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($it)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $historyIds = collect($response->json('props.approvalHistory'))->pluck('id')->all();

        $this->assertNotContains($request->id, $historyIds, 'Pengajuan yang ditolak Head sebelum sampai ke IT tidak boleh muncul di riwayat IT.');
    }

    public function test_it_approval_history_includes_item_it_rejected_itself(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $it = $this->makeUser('it', 1, 'it@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($it)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($it)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $history = collect($response->json('props.approvalHistory'));
        $entry = $history->firstWhere('id', $request->id);

        $this->assertNotNull($entry);
        $this->assertEquals('rejected', $entry['your_decision']);
    }

    // --- Riwayat Approval: Finance ---

    public function test_finance_approval_history_excludes_item_rejected_by_it_before_reaching_finance(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $it = $this->makeUser('it', 1, 'it@test.com');
        $finance = $this->makeUser('finance', 1, 'finance@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($it)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($finance)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $historyIds = collect($response->json('props.approvalHistory'))->pluck('id')->all();

        $this->assertNotContains($request->id, $historyIds, 'Pengajuan yang ditolak IT sebelum sampai ke Finance tidak boleh muncul di riwayat Finance.');
    }

    public function test_finance_approval_history_includes_forward_approved_item(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $it = $this->makeUser('it', 1, 'it@test.com');
        $finance = $this->makeUser('finance', 1, 'finance@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($it)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($finance)->post(route('request.hardware.approve', $request->id));

        $response = $this->actingAs($finance)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $history = collect($response->json('props.approvalHistory'));
        $entry = $history->firstWhere('id', $request->id);

        $this->assertNotNull($entry);
        $this->assertEquals('approved', $entry['your_decision']);
        $this->assertEquals(RequestStatus::Approved->value, $entry['status']);
    }

    // --- Riwayat Approval: Procurement ---

    public function test_procurement_approval_history_only_includes_completed_items(): void
    {
        $this->seedDepartements();
        $procurement = $this->makeUser('procurement', 1, 'procurement@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::Approved);

        $beforeResponse = $this->actingAs($procurement)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));
        $beforeIds = collect($beforeResponse->json('props.approvalHistory'))->pluck('id')->all();
        $this->assertNotContains($request->id, $beforeIds, 'Yang baru APPROVED (belum ditandai selesai) belum boleh masuk riwayat procurement.');

        $this->actingAs($procurement)->post(route('request.hardware.approve', $request->id));

        $afterResponse = $this->actingAs($procurement)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));
        $history = collect($afterResponse->json('props.approvalHistory'));
        $entry = $history->firstWhere('id', $request->id);

        $this->assertNotNull($entry);
        $this->assertEquals('completed', $entry['your_decision']);
    }
}
