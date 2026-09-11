<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pendaftaran mandiri sengaja DIMATIKAN di sistem ini (route-nya dikomentari
 * di routes/auth.php). Akun karyawan dibuat oleh pemegang akses Manajemen
 * User, atau diimpor massal lewat `php artisan employees:import` - bukan
 * dibuat sendiri oleh orang luar.
 *
 * Test ini dulu bawaan Laravel Breeze yang menguji kebalikannya. Sekarang
 * diubah jadi penjaga keputusan itu: kalau suatu saat route register tidak
 * sengaja dihidupkan lagi, test ini yang berteriak.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_pendaftaran_mandiri_tidak_tersedia(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_orang_luar_tidak_bisa_mendaftarkan_akun_sendiri(): void
    {
        $response = $this->post('/register', [
            'name' => 'Orang Luar',
            'email' => 'orangluar@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
        $this->assertDatabaseMissing('master_employees', [
            'email' => 'orangluar@example.com',
        ]);
    }
}
