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
        $user = User::create([
            'name' => ucfirst($role).' Dept'.$departementId,
            'email' => $email,
            'password' => bcrypt('password'),
            'entity' => 'PT VISINEMA PICTURES',
            'position' => ucfirst($role),
            'departement_id' => $departementId,
            'role' => $role,
        ]);

        // Sejak tanda tangan digital jadi syarat mengirim pengajuan, akun uji
        // di sini dianggap sudah punya. Yang dicek RequestController cuma
        // kolomnya terisi atau tidak, jadi berkasnya sendiri tidak perlu ada -
        // pembuatan tanda tangan yang sebenarnya diuji di SignatureTest.
        $user->forceFill([
            'signature_path' => 'signatures/uji-'.$user->id.'.sig',
            'signature_uploaded_at' => now(),
        ])->save();

        return $user;
    }

    private function makeUserWithoutSignature(string $role, int $departementId, string $email): User
    {
        $user = $this->makeUser($role, $departementId, $email);

        $user->forceFill(['signature_path' => null, 'signature_uploaded_at' => null])->save();

        return $user;
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
        $this->assertEquals(RequestStatus::WaitingHeadItApproval->value, $request->fresh()->status);
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

    // --- Head of IT: menilai kelayakan teknis, lintas departemen ---

    public function test_head_of_it_can_approve_request_regardless_of_department(): void
    {
        $this->seedDepartements();
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadItApproval);

        $response = $this->actingAs($itHead)->post(route('request.hardware.approve', $request->id));

        $response->assertRedirect();
        $this->assertEquals(RequestStatus::OnExternalProcess->value, $request->fresh()->status);
    }

    public function test_head_of_it_cannot_approve_request_still_awaiting_department_head(): void
    {
        $this->seedDepartements();
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $response = $this->actingAs($itHead)->post(route('request.hardware.approve', $request->id));

        $response->assertForbidden();
        $this->assertEquals(RequestStatus::WaitingHeadApproval->value, $request->fresh()->status);
    }

    // --- IT Admin: pencatatan administratif, bukan keputusan approval ---

    /**
     * Tahap IT Admin punya isian wajib (catatan pengiriman, nomor seri), dan
     * itu dikerjakan lewat halaman detail. Endpoint approval di dashboard
     * sengaja menolaknya - kalau tidak, pengajuan bisa dimajukan sampai BAST
     * tanpa nomor seri barangnya pernah terisi.
     */
    public function test_it_admin_cannot_advance_fulfillment_through_approval_endpoint(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::OnExternalProcess);

        $response = $this->actingAs($itAdmin)->post(route('request.hardware.approve', $request->id));

        $response->assertSessionHas('error');
        $this->assertEquals(RequestStatus::OnExternalProcess->value, $request->fresh()->status);
    }

    /**
     * Di tahap ini berkasnya sudah jalan di procurement/finance di luar sistem.
     * Kalau di sana batal, itu pembatalan - bukan penolakan approval. Dua hal
     * itu sengaja tidak dicampur supaya riwayatnya tidak berbunyi seolah tim IT
     * yang menolak, padahal keputusannya datang dari luar sistem.
     */
    public function test_it_admin_cannot_reject_externally_processed_request(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::OnExternalProcess);

        $response = $this->actingAs($itAdmin)->post(route('request.hardware.reject', $request->id));

        $response->assertForbidden();
        $this->assertEquals(RequestStatus::OnExternalProcess->value, $request->fresh()->status);
    }

    public function test_it_admin_cannot_touch_request_still_awaiting_head_of_it(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $requester = $this->makeUser('user', 2, 'user-b@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadItApproval);

        $response = $this->actingAs($itAdmin)->post(route('request.hardware.approve', $request->id));

        $response->assertForbidden();
        $this->assertEquals(RequestStatus::WaitingHeadItApproval->value, $request->fresh()->status);
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

    public function test_head_approval_history_includes_item_rejected_later_by_head_of_it(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($itHead)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($head)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $history = collect($response->json('props.approvalHistory'));
        $entry = $history->firstWhere('id', $request->id);

        $this->assertNotNull($entry, 'Head tetap harus lihat pengajuan ini di riwayatnya walau belakangan ditolak Head of IT.');
        $this->assertEquals('approved', $entry['your_decision'], 'Head yang menyetujui, bukan yang menolak - jadi keputusan Head tetap "approved".');
        $this->assertEquals($itHead->name, $entry['rejected_by_name']);
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

    // --- Riwayat Approval: Head of IT ---

    public function test_head_of_it_approval_history_excludes_item_rejected_by_head_first(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($itHead)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $historyIds = collect($response->json('props.approvalHistory'))->pluck('id')->all();

        $this->assertNotContains($request->id, $historyIds, 'Pengajuan yang ditolak Head sebelum sampai ke Head of IT tidak boleh muncul di riwayat Head of IT.');
    }

    public function test_head_of_it_approval_history_includes_item_it_rejected_itself(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($itHead)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($itHead)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $history = collect($response->json('props.approvalHistory'));
        $entry = $history->firstWhere('id', $request->id);

        $this->assertNotNull($entry);
        $this->assertEquals('rejected', $entry['your_decision']);
    }

    // --- Riwayat Approval: IT Admin ---

    public function test_it_admin_approval_history_excludes_item_rejected_before_reaching_it_admin(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $request = $this->makeHardwareRequest($requester, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($itHead)->post(route('request.hardware.reject', $request->id));

        $response = $this->actingAs($itAdmin)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $historyIds = collect($response->json('props.approvalHistory'))->pluck('id')->all();

        $this->assertNotContains($request->id, $historyIds, 'Pengajuan yang ditolak Head of IT sebelum sampai ke IT Admin tidak boleh muncul di riwayatnya.');
    }

    /**
     * Selama masih di salah satu tahap IT Admin, pengajuan ada di ANTREAN-nya.
     * Dia baru pindah ke riwayat setelah lewat tahap terakhir IT Admin -
     * kalau tidak, satu pengajuan muncul di dua tempat sekaligus.
     */
    public function test_it_admin_queue_and_history_do_not_overlap(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $requester = $this->makeUser('user', 1, 'user-a@test.com');
        $onTheWay = $this->makeHardwareRequest($requester, RequestStatus::ItemOnTheWay);
        $awaitingBast = $this->makeHardwareRequest($requester, RequestStatus::WaitingBastSignature);

        $response = $this->actingAs($itAdmin)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('dashboard'));

        $pendingIds = collect($response->json('props.pendingApprovals'))->pluck('id')->all();
        $historyIds = collect($response->json('props.approvalHistory'))->pluck('id')->all();

        $this->assertContains($onTheWay->id, $pendingIds);
        $this->assertNotContains($onTheWay->id, $historyIds);

        $this->assertNotContains($awaitingBast->id, $pendingIds, 'Yang menunggu tanda tangan pemohon bukan lagi urusan IT Admin.');
        $this->assertContains($awaitingBast->id, $historyIds);
    }

    // --- Tanda tangan digital jadi syarat mengirim pengajuan ---

    public function test_pengajuan_ditolak_kalau_pemohon_belum_punya_tanda_tangan(): void
    {
        $this->seedDepartements();
        $user = $this->makeUserWithoutSignature('user', 1, 'tanpa-ttd@test.com');

        $response = $this->actingAs($user)->post(route('request.hardware.store'), [
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'l1',
            'justification' => 'Butuh laptop baru',
            'digital_signature' => true,
        ]);

        $response->assertSessionHasErrors('digital_signature');
        $this->assertDatabaseCount('hardware_requests', 0);
    }

    public function test_pengajuan_lolos_kalau_pemohon_sudah_punya_tanda_tangan(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'punya-ttd@test.com');

        $response = $this->actingAs($user)->post(route('request.hardware.store'), [
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'l1',
            'justification' => 'Butuh laptop baru',
            'digital_signature' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('hardware_requests', 1);
    }

    // --- Perangkat di luar standar wajib disertai namanya ---

    public function test_nama_perangkat_wajib_kalau_memilih_di_luar_standar(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-custom@test.com');

        $response = $this->actingAs($user)->post(route('request.hardware.store'), [
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'custom',
            'custom_hardware_name' => '',
            'justification' => 'Butuh spesifikasi khusus',
            'digital_signature' => true,
        ]);

        $response->assertSessionHasErrors('custom_hardware_name');
        $this->assertDatabaseCount('hardware_requests', 0);
    }

    public function test_nama_perangkat_tersimpan_saat_memilih_di_luar_standar(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-custom@test.com');

        $this->actingAs($user)->post(route('request.hardware.store'), [
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'custom',
            'custom_hardware_name' => 'MacBook Pro 14 M3 16GB/512GB',
            'preference_image' => \Illuminate\Http\UploadedFile::fake()->image('produk.png', 400, 300),
            'justification' => 'Butuh spesifikasi khusus',
            'digital_signature' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            'MacBook Pro 14 M3 16GB/512GB',
            HardwareRequest::firstOrFail()->custom_hardware_name
        );
    }

    public function test_nama_perangkat_tidak_wajib_untuk_perangkat_standar(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-standar@test.com');

        $this->actingAs($user)->post(route('request.hardware.store'), [
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'l1',
            'justification' => 'Laptop lama sudah lambat',
            'digital_signature' => true,
        ])->assertSessionHasNoErrors();

        $this->assertNull(HardwareRequest::firstOrFail()->custom_hardware_name);
    }

    // --- Menyetujui menuntut tanda tangan, menolak tidak ---

    public function test_penyetuju_tanpa_tanda_tangan_tidak_bisa_menyetujui(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $head = $this->makeUserWithoutSignature('head', 1, 'head-tanpa-ttd@test.com');
        $request = $this->makeHardwareRequest($pemohon, RequestStatus::WaitingHeadApproval);

        $response = $this->actingAs($head)
            ->post(route('request.hardware.approve', $request->id));

        $response->assertSessionHas('error');
        $this->assertSame(
            RequestStatus::WaitingHeadApproval->value,
            $request->fresh()->status
        );
    }

    public function test_penyetuju_dengan_tanda_tangan_bisa_menyetujui(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $head = $this->makeUser('head', 1, 'head-punya-ttd@test.com');
        $request = $this->makeHardwareRequest($pemohon, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)
            ->post(route('request.hardware.approve', $request->id))
            ->assertSessionHas('success');

        $this->assertSame(
            RequestStatus::WaitingHeadItApproval->value,
            $request->fresh()->status
        );
    }

    /**
     * Menolak menghentikan alur - tidak ada dokumen yang ditandatangani, jadi
     * tidak masuk akal menahannya sampai penyetuju membuat tanda tangan.
     */
    public function test_penyetuju_tanpa_tanda_tangan_tetap_bisa_menolak(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $head = $this->makeUserWithoutSignature('head', 1, 'head-tanpa-ttd@test.com');
        $request = $this->makeHardwareRequest($pemohon, RequestStatus::WaitingHeadApproval);

        $this->actingAs($head)
            ->post(route('request.hardware.reject', $request->id))
            ->assertSessionHas('success');

        $this->assertSame(RequestStatus::Rejected->value, $request->fresh()->status);
    }
}
