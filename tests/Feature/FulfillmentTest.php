<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Departement;
use App\Models\HardwareRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap sesudah semua persetujuan: perjalanan barang sampai BAST
 * ditandatangani pemohon.
 *
 * Yang dijaga di sini bukan cuma "statusnya berubah", tapi dua hal yang kalau
 * jebol membuat dokumennya tidak ada artinya: nomor seri harus terisi sebelum
 * BAST terbit, dan BAST cuma boleh ditandatangani oleh penerimanya sendiri.
 */
class FulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private function seedDepartements(): void
    {
        Departement::forceCreate(['id' => 1, 'name' => 'IT']);
        Departement::forceCreate(['id' => 2, 'name' => 'Operations']);
    }

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

        $user->forceFill([
            'signature_path' => 'signatures/uji-'.$user->id.'.sig',
            'signature_uploaded_at' => now(),
        ])->save();

        return $user;
    }

    private function makeRequest(User $requester, RequestStatus $status): HardwareRequest
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

    private function url(string $name, HardwareRequest $request): string
    {
        return route($name, ['type' => 'hardware', 'id' => $request->id]);
    }

    // --- Barang dikirim ---

    public function test_it_admin_bisa_menandai_barang_dikirim_beserta_catatannya(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::OnExternalProcess);

        $this->actingAs($itAdmin)
            ->post($this->url('request.mark-on-the-way', $request), [
                'note' => 'PO sudah terbit, estimasi tiba minggu depan.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(RequestStatus::ItemOnTheWay->value, $request->fresh()->status);
        $this->assertSame(
            'PO sudah terbit, estimasi tiba minggu depan.',
            $request->events()->latest('id')->first()->note
        );
    }

    public function test_selain_it_admin_tidak_bisa_menandai_barang_dikirim(): void
    {
        $this->seedDepartements();
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::OnExternalProcess);

        $this->actingAs($itHead)
            ->post($this->url('request.mark-on-the-way', $request))
            ->assertForbidden();

        $this->assertSame(RequestStatus::OnExternalProcess->value, $request->fresh()->status);
    }

    // --- Serah terima & terbitnya BAST ---

    public function test_serah_terima_wajib_menyertakan_nomor_seri(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::ItemOnTheWay);

        $this->actingAs($itAdmin)
            ->post($this->url('request.mark-handed-over', $request), ['item_identifier' => ''])
            ->assertSessionHasErrors('item_identifier');

        $this->assertSame(
            RequestStatus::ItemOnTheWay->value,
            $request->fresh()->status,
            'Tanpa nomor seri, BAST tidak boleh terbit - dokumennya jadi tidak bisa membuktikan unit mana yang diserahkan.'
        );
    }

    public function test_serah_terima_menerbitkan_bast_dan_mencatat_penyerahnya(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::ItemOnTheWay);

        $this->actingAs($itAdmin)
            ->post($this->url('request.mark-handed-over', $request), [
                'item_identifier' => 'C02ZK1ZTLVDL',
            ])
            ->assertSessionHas('success');

        $fresh = $request->fresh();
        $this->assertSame(RequestStatus::WaitingBastSignature->value, $fresh->status);
        $this->assertSame('C02ZK1ZTLVDL', $fresh->item_identifier);
        $this->assertSame($itAdmin->id, $fresh->handed_over_by_id);
        $this->assertNotNull($fresh->handed_over_at);
    }

    public function test_serah_terima_ditolak_kalau_barangnya_belum_dikirim(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::OnExternalProcess);

        $this->actingAs($itAdmin)
            ->post($this->url('request.mark-handed-over', $request), [
                'item_identifier' => 'C02ZK1ZTLVDL',
            ])
            ->assertSessionHas('error');

        $this->assertSame(RequestStatus::OnExternalProcess->value, $request->fresh()->status);
    }

    // --- Tanda tangan BAST ---

    public function test_pemohon_menandatangani_bast_dan_pengajuan_langsung_selesai(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::WaitingBastSignature);

        $this->actingAs($pemohon)
            ->post($this->url('request.sign-bast', $request))
            ->assertSessionHas('success');

        $fresh = $request->fresh();
        $this->assertSame(RequestStatus::Completed->value, $fresh->status);
        $this->assertNotNull($fresh->bast_signed_at);
    }

    /**
     * Ini pengaman paling penting di seluruh tahap serah terima. Kalau pihak
     * yang menyerahkan bisa menandatangani atas nama penerima, BAST-nya
     * kehilangan seluruh maknanya sebagai bukti.
     */
    public function test_it_admin_tidak_bisa_menandatangani_bast_atas_nama_pemohon(): void
    {
        $this->seedDepartements();
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::WaitingBastSignature);

        $this->actingAs($itAdmin)
            ->post($this->url('request.sign-bast', $request))
            ->assertForbidden();

        $this->assertSame(RequestStatus::WaitingBastSignature->value, $request->fresh()->status);
    }

    public function test_pemohon_lain_tidak_bisa_menandatangani_bast_orang(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $orangLain = $this->makeUser('user', 2, 'orang-lain@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::WaitingBastSignature);

        $this->actingAs($orangLain)
            ->post($this->url('request.sign-bast', $request))
            ->assertForbidden();
    }

    public function test_pemohon_tanpa_tanda_tangan_belum_bisa_menandatangani_bast(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $pemohon->forceFill(['signature_path' => null, 'signature_uploaded_at' => null])->save();
        $request = $this->makeRequest($pemohon, RequestStatus::WaitingBastSignature);

        $this->actingAs($pemohon)
            ->post($this->url('request.sign-bast', $request))
            ->assertSessionHas('error');

        $this->assertSame(RequestStatus::WaitingBastSignature->value, $request->fresh()->status);
    }

    // --- Linimasa & halaman detail ---

    public function test_setiap_perubahan_status_tercatat_di_linimasa(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 2, 'head@test.com');
        $itHead = $this->makeUser('it_head', 1, 'ithead@test.com');
        $itAdmin = $this->makeUser('it', 1, 'itadmin@test.com');
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');

        $this->actingAs($pemohon)->post(route('request.hardware.store'), [
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'l1',
            'justification' => 'Laptop lama sudah lambat',
            'digital_signature' => true,
        ])->assertSessionHasNoErrors();

        $request = HardwareRequest::firstOrFail();

        $this->actingAs($head)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($itHead)->post(route('request.hardware.approve', $request->id));
        $this->actingAs($itAdmin)->post($this->url('request.mark-on-the-way', $request));
        $this->actingAs($itAdmin)->post($this->url('request.mark-handed-over', $request), [
            'item_identifier' => 'SN-123',
        ]);
        $this->actingAs($pemohon)->post($this->url('request.sign-bast', $request));

        $events = $request->fresh()->events()->get();

        $this->assertSame([
            RequestStatus::WaitingHeadApproval->value,
            RequestStatus::WaitingHeadItApproval->value,
            RequestStatus::OnExternalProcess->value,
            RequestStatus::ItemOnTheWay->value,
            RequestStatus::WaitingBastSignature->value,
            RequestStatus::Completed->value,
        ], $events->pluck('to_status')->all());

        // Kejadian pertama tidak punya status asal - pengajuannya memang baru
        // dibuat, tidak berpindah dari mana-mana.
        $this->assertNull($events->first()->from_status);
        $this->assertSame($pemohon->id, $events->first()->actor_id);
        $this->assertSame($itAdmin->id, $events[3]->actor_id);
    }

    public function test_orang_luar_tidak_bisa_membuka_halaman_detail(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $orangLain = $this->makeUser('user', 2, 'orang-lain@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::OnExternalProcess);

        $this->actingAs($orangLain)
            ->get($this->url('request.show', $request))
            ->assertForbidden();
    }

    public function test_pemohon_bisa_membuka_halaman_detailnya_sendiri(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');
        $request = $this->makeRequest($pemohon, RequestStatus::WaitingBastSignature);
        $request->update(['item_identifier' => 'SN-9', 'handed_over_at' => now()]);

        $response = $this->actingAs($pemohon)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get($this->url('request.show', $request));

        $response->assertOk();
        $this->assertTrue($response->json('props.abilities.signBast'));
        $this->assertSame('SN-9', $response->json('props.request.item_identifier'));
    }

    public function test_jenis_pengajuan_yang_tidak_dikenal_berhenti_di_router(): void
    {
        $this->seedDepartements();
        $pemohon = $this->makeUser('user', 2, 'pemohon@test.com');

        $this->actingAs($pemohon)->get('/pengajuan/karyawan/1')->assertNotFound();
    }
}
