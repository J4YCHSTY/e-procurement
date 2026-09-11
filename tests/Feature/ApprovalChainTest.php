<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Departement;
use App\Models\HardwareRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji tahap AWAL sebuah pengajuan.
 *
 * Normalnya selalu mulai dari kepala departemen. Yang perlu dijaga di sini
 * adalah pengecualiannya: departemen yang kepalanya Head of IT sendiri.
 * Kalau aturan ini rusak diam-diam, akibatnya baru ketahuan waktu Head of IT
 * diminta menyetujui satu pengajuan dua kali - dan tanda tangan yang sama
 * menempel dua kali di satu dokumen.
 */
class ApprovalChainTest extends TestCase
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

    private function submitHardware(User $requester): HardwareRequest
    {
        $this->actingAs($requester)->post(route('request.hardware.store'), [
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'l1',
            'justification' => 'Laptop lama sudah lambat',
            'digital_signature' => true,
        ])->assertSessionHasNoErrors();

        return HardwareRequest::latest('id')->firstOrFail();
    }

    public function test_pengajuan_biasa_mulai_dari_kepala_departemen(): void
    {
        $this->seedDepartements();
        $this->makeUser('head', 2, 'head-ops@test.com');
        $pemohon = $this->makeUser('user', 2, 'staff-ops@test.com');

        $this->assertSame(
            RequestStatus::WaitingHeadApproval->value,
            $this->submitHardware($pemohon)->status
        );
    }

    public function test_pengajuan_dari_departemen_yang_dikepalai_head_of_it_langsung_ke_tahap_head_of_it(): void
    {
        $this->seedDepartements();
        $this->makeUser('it_head', 1, 'ithead@test.com');
        $pemohon = $this->makeUser('user', 1, 'staff-it@test.com');

        $this->assertSame(
            RequestStatus::WaitingHeadItApproval->value,
            $this->submitHardware($pemohon)->status,
            'Kepala departemen orang IT ya Head of IT sendiri - tahap kepala departemen harus dilewati.'
        );
    }

    /**
     * Pemotongan tahap tadi cuma berlaku selama Head of IT memang merangkap
     * kepala departemen. Begitu departemen IT punya kepala sendiri, dua orang
     * yang berbeda mengisi dua tahap yang berbeda, dan alurnya harus kembali
     * normal dengan sendirinya - tanpa perlu ada kode yang diubah.
     */
    public function test_kalau_departemen_it_punya_kepala_sendiri_alurnya_kembali_normal(): void
    {
        $this->seedDepartements();
        $this->makeUser('it_head', 1, 'ithead@test.com');
        $this->makeUser('head', 1, 'head-it@test.com');
        $pemohon = $this->makeUser('user', 1, 'staff-it@test.com');

        $this->assertSame(
            RequestStatus::WaitingHeadApproval->value,
            $this->submitHardware($pemohon)->status
        );
    }

    /**
     * Head of IT yang sudah nonaktif tidak boleh dihitung sebagai kepala
     * departemen - kalau tidak, pengajuan orang IT mendarat di tahap yang
     * tidak ada pemegangnya dan diam di situ selamanya.
     */
    public function test_head_of_it_yang_nonaktif_tidak_memotong_tahap(): void
    {
        $this->seedDepartements();
        $this->makeUser('it_head', 1, 'ithead@test.com')
            ->forceFill(['is_active' => false])->save();
        $pemohon = $this->makeUser('user', 1, 'staff-it@test.com');

        $this->assertSame(
            RequestStatus::WaitingHeadApproval->value,
            $this->submitHardware($pemohon)->status
        );
    }
}
