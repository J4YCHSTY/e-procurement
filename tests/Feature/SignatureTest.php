<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Signature\SignatureStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seluruh test di kelas ini memproses gambar sungguhan. Kalau GD belum
        // aktif, hasilnya bukan "fitur rusak" tapi "lingkungannya belum siap" -
        // jadi dilewati dengan pesan yang jelas, bukan ditandai gagal.
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped(
                'Ekstensi GD belum aktif. Hapus tanda ; di depan "extension=gd" pada php.ini, lalu ulangi.'
            );
        }
    }

    private function makeUser(string $email = 'orang@office.com'): User
    {
        return User::create([
            'name' => 'Orang Uji',
            'email' => $email,
            'password' => Hash::make('password'),
            'entity' => 'PT VISINEMA PICTURES',
            'position' => 'Staff',
            'departement_id' => null,
            'role' => 'user',
            'can_manage_users' => false,
            'is_active' => true,
        ]);
    }

    /**
     * PNG transparan kecil (2x2) sebagai data URL, meniru keluaran kanvas.
     */
    private function samplePngDataUrl(): string
    {
        $image = imagecreatetruecolor(6, 4);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagesetpixel($image, 2, 2, imagecolorallocatealpha($image, 17, 24, 39, 0));

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($binary);
    }

    // --- Akses ---

    public function test_tamu_tidak_bisa_menyentuh_endpoint_tanda_tangan(): void
    {
        $this->get(route('signature.show'))->assertRedirect(route('login'));
        $this->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()])
            ->assertRedirect(route('login'));
        $this->delete(route('signature.destroy'))->assertRedirect(route('login'));
    }

    public function test_user_tanpa_tanda_tangan_dapat_404(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('signature.show'))
            ->assertNotFound();
    }

    // --- Simpan ---

    public function test_user_bisa_menyimpan_tanda_tangan(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()])
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->signature_path);
        $this->assertNotNull($user->signature_uploaded_at);
        $this->assertTrue($user->has_signature);
        $this->assertTrue(Storage::disk('local')->exists($user->signature_path));
    }

    /**
     * Ini inti pengamanannya: yang tersimpan di disk BUKAN gambar. Siapa pun
     * yang menyalin folder storage atau mencuri backup cuma dapat blob acak.
     */
    public function test_berkas_di_disk_terenkripsi_bukan_png(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()]);

        $user->refresh();
        $raw = Storage::disk('local')->get($user->signature_path);

        // Bukan PNG: tidak diawali magic number PNG, dan GD menolak membacanya.
        $this->assertStringStartsNotWith("\x89PNG", $raw);
        $this->assertFalse(@imagecreatefromstring($raw));

        // Tapi lewat SignatureStorage tetap bisa dikembalikan jadi PNG utuh.
        $decrypted = app(SignatureStorage::class)->get($user);
        $this->assertStringStartsWith("\x89PNG", $decrypted);
        $this->assertNotFalse(@imagecreatefromstring($decrypted));
    }

    public function test_nama_berkas_tanda_tangan_tidak_ikut_ke_frontend(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()]);

        $serialized = $user->refresh()->toArray();

        $this->assertArrayNotHasKey('signature_path', $serialized);
        $this->assertTrue($serialized['has_signature']);
    }

    public function test_tanda_tangan_lama_dihapus_saat_diganti(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()]);
        $firstPath = $user->refresh()->signature_path;

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()]);
        $secondPath = $user->refresh()->signature_path;

        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFalse(Storage::disk('local')->exists($firstPath));
        $this->assertTrue(Storage::disk('local')->exists($secondPath));
    }

    public function test_data_yang_bukan_png_ditolak(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => 'bukan-data-url'])
            ->assertSessionHasErrors('signature');

        $this->assertNull($user->refresh()->signature_path);
    }

    public function test_data_url_png_yang_isinya_bukan_gambar_ditolak(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), [
                'signature' => 'data:image/png;base64,'.base64_encode('ini cuma teks biasa'),
            ])
            ->assertSessionHasErrors('signature');

        $this->assertNull($user->refresh()->signature_path);
    }

    // --- Tampilkan ---

    public function test_user_bisa_melihat_tanda_tangannya_sendiri(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()]);

        $response = $this->actingAs($user)->get(route('signature.show'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');

        // Yang penting bukan susunan persisnya (Symfony menormalkan urutannya),
        // tapi gambarnya tidak boleh nyangkut di cache browser atau proxy.
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);

        $this->assertStringStartsWith("\x89PNG", $response->getContent());
    }

    /**
     * Tidak ada endpoint yang menerima ID user, jadi tanda tangan orang lain
     * memang tidak bisa diminta - yang keluar selalu punya diri sendiri.
     */
    public function test_endpoint_selalu_mengembalikan_tanda_tangan_milik_sendiri(): void
    {
        $pemilik = $this->makeUser('pemilik@office.com');
        $penyusup = $this->makeUser('penyusup@office.com');

        $this->actingAs($pemilik)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()]);

        // Penyusup belum punya tanda tangan sendiri -> 404, bukan gambar milik pemilik.
        $this->actingAs($penyusup)
            ->get(route('signature.show'))
            ->assertNotFound();
    }

    // --- Hapus ---

    public function test_user_bisa_menghapus_tanda_tangannya(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('signature.store'), ['signature' => $this->samplePngDataUrl()]);
        $path = $user->refresh()->signature_path;

        $this->actingAs($user)->delete(route('signature.destroy'))->assertRedirect();

        $user->refresh();

        $this->assertNull($user->signature_path);
        $this->assertNull($user->signature_uploaded_at);
        $this->assertFalse($user->has_signature);
        $this->assertFalse(Storage::disk('local')->exists($path));
    }
}
