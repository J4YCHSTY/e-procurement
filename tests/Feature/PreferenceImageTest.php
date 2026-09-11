<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\HardwareRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lampiran preferensi barang: tangkapan layar produk yang diunggah pemohon.
 *
 * Yang diuji di sini bukan cuma "berkasnya tersimpan", tapi juga siapa yang
 * boleh membukanya - karena berkasnya sengaja ditaruh di direktori privat dan
 * hanya disajikan lewat route ber-otorisasi.
 */
class PreferenceImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped(
                'Ekstensi GD belum aktif. Hapus tanda ; di depan "extension=gd" pada php.ini, lalu ulangi.'
            );
        }

        Departement::forceCreate(['id' => 1, 'name' => 'Departemen A']);
        Departement::forceCreate(['id' => 2, 'name' => 'Departemen B']);
    }

    private function makeUser(string $role, int $departementId, string $email): User
    {
        $user = User::create([
            'name' => ucfirst($role).'-'.$email,
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'request_date' => now()->toDateString(),
            'hardware_type' => 'laptop',
            'hardware_recommendation' => 'l1',
            'justification' => 'Butuh laptop baru buat kerja',
            'digital_signature' => true,
        ], $overrides);
    }

    // --- Unggah ---

    public function test_gambar_preferensi_tersimpan_di_direktori_privat(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');

        $this->actingAs($pemohon)
            ->post(route('request.hardware.store'), $this->payload([
                'preference_image' => UploadedFile::fake()->image('produk.png', 600, 400),
            ]))
            ->assertSessionHasNoErrors();

        $request = HardwareRequest::firstOrFail();

        $this->assertNotNull($request->preference_image_path);
        $this->assertStringStartsWith('request-preferences/', $request->preference_image_path);
        $this->assertTrue(Storage::disk('local')->exists($request->preference_image_path));

        // Disimpan sebagai PNG hasil encode ulang, bukan berkas mentah pemohon.
        $stored = Storage::disk('local')->get($request->preference_image_path);
        $this->assertStringStartsWith("\x89PNG", $stored);
    }

    public function test_lampiran_boleh_kosong_untuk_perangkat_standar(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');

        $this->actingAs($pemohon)
            ->post(route('request.hardware.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertNull(HardwareRequest::firstOrFail()->preference_image_path);
    }

    /**
     * Perangkat di luar standar tidak punya spesifikasi baku yang bisa jadi
     * acuan procurement, jadi gambarnya yang wajib.
     */
    public function test_lampiran_wajib_kalau_perangkatnya_di_luar_standar(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');

        $this->actingAs($pemohon)
            ->post(route('request.hardware.store'), $this->payload([
                'hardware_recommendation' => 'custom',
            ]))
            ->assertSessionHasErrors('preference_image');

        $this->assertDatabaseCount('hardware_requests', 0);
    }

    public function test_berkas_yang_bukan_gambar_ditolak(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');

        $this->actingAs($pemohon)
            ->post(route('request.hardware.store'), $this->payload([
                'preference_image' => UploadedFile::fake()->create('dokumen.pdf', 40, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('preference_image');

        $this->assertDatabaseCount('hardware_requests', 0);
    }

    // --- Siapa yang boleh membuka ---

    private function createRequestWithImage(User $pemohon): HardwareRequest
    {
        $this->actingAs($pemohon)->post(route('request.hardware.store'), $this->payload([
            'preference_image' => UploadedFile::fake()->image('produk.png', 600, 400),
        ]));

        return HardwareRequest::firstOrFail();
    }

    public function test_pemohon_bisa_membuka_lampirannya_sendiri(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $request = $this->createRequestWithImage($pemohon);

        $response = $this->actingAs($pemohon)
            ->get(route('request.hardware.preference-image', $request->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_head_departemen_yang_sama_bisa_membuka_lampiran(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $head = $this->makeUser('head', 1, 'head@test.com');
        $request = $this->createRequestWithImage($pemohon);

        $this->actingAs($head)
            ->get(route('request.hardware.preference-image', $request->id))
            ->assertOk();
    }

    public function test_head_departemen_lain_tidak_bisa_membuka_lampiran(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $headLain = $this->makeUser('head', 2, 'head-lain@test.com');
        $request = $this->createRequestWithImage($pemohon);

        $this->actingAs($headLain)
            ->get(route('request.hardware.preference-image', $request->id))
            ->assertForbidden();
    }

    public function test_karyawan_lain_tidak_bisa_membuka_lampiran(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $orangLain = $this->makeUser('user', 1, 'orang-lain@test.com');
        $request = $this->createRequestWithImage($pemohon);

        $this->actingAs($orangLain)
            ->get(route('request.hardware.preference-image', $request->id))
            ->assertForbidden();
    }

    public function test_tamu_tidak_bisa_membuka_lampiran(): void
    {
        $pemohon = $this->makeUser('user', 1, 'pemohon@test.com');
        $request = $this->createRequestWithImage($pemohon);

        $this->post(route('logout'));

        $this->get(route('request.hardware.preference-image', $request->id))
            ->assertRedirect(route('login'));
    }
}
