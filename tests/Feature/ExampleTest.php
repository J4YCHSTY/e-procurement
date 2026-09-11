<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tests\TestCase;

/**
 * Halaman root tidak punya landing page sendiri - dia cuma pengalih:
 * tamu diarahkan ke login, yang sudah masuk diarahkan ke dashboard
 * (lihat routes/web.php).
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_user_yang_sudah_login_diarahkan_ke_dashboard(): void
    {
        $user = User::create([
            'name' => 'Orang Uji',
            'email' => 'orang.uji@office.com',
            'password' => Hash::make('password'),
            'entity' => 'PT VISINEMA PICTURES',
            'position' => 'Staff',
            'departement_id' => null,
            'role' => 'user',
            'can_manage_users' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }
}
