<?php

namespace Tests\Feature;

use App\Models\Departement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?int $departementId, string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role).'-'.$email,
            'email' => $email,
            'password' => Hash::make('password'),
            'entity' => 'Kantor Pusat',
            'position' => ucfirst($role),
            'departement_id' => $departementId,
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    private function seedDepartements(): void
    {
        Departement::forceCreate(['id' => 1, 'name' => 'Departemen A']);
    }

    // --- Otorisasi: cuma role 'it' yang boleh akses ---

    public function test_it_can_view_user_list(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');
        $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($it)
            ->withHeaders(['X-Inertia' => 'true'])
            ->get(route('users.index'));

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('props.users.data')));
    }

    public function test_non_it_cannot_view_user_list(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');

        $response = $this->actingAs($head)->get(route('users.index'));

        $response->assertForbidden();
    }

    // --- Create ---

    public function test_it_can_create_new_user_with_default_password(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');

        $response = $this->actingAs($it)->post(route('users.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'baru@office.com',
            'entity' => 'Kantor Pusat',
            'position' => 'Staff',
            'departement_id' => 1,
            'role' => 'user',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'baru@office.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check(User::DEFAULT_PASSWORD, $newUser->password));
        $this->assertTrue($newUser->is_active);
    }

    public function test_non_it_cannot_create_user(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');

        $response = $this->actingAs($head)->post(route('users.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'baru@office.com',
            'role' => 'user',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('master_employees', ['email' => 'baru@office.com']);
    }

    public function test_create_user_requires_unique_email(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');
        $this->makeUser('user', 1, 'sudah-ada@office.com');

        $response = $this->actingAs($it)->post(route('users.store'), [
            'name' => 'Duplikat',
            'email' => 'sudah-ada@office.com',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('email');
    }

    // --- Update profil ---

    public function test_it_can_update_another_users_profile(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');
        $target = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($it)->patch(route('users.update', $target->id), [
            'name' => 'Nama Baru',
            'email' => $target->email,
            'entity' => 'Kantor Pusat',
            'position' => 'Senior Staff',
            'departement_id' => 1,
            'role' => 'head',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Nama Baru', $target->fresh()->name);
        $this->assertEquals('head', $target->fresh()->role);
    }

    public function test_it_cannot_change_own_role(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');

        $response = $this->actingAs($it)->patch(route('users.update', $it->id), [
            'name' => $it->name,
            'email' => $it->email,
            'entity' => $it->entity,
            'position' => $it->position,
            'departement_id' => 1,
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertEquals('it', $it->fresh()->role);
    }

    public function test_it_can_edit_own_profile_fields_other_than_role(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');

        $response = $this->actingAs($it)->patch(route('users.update', $it->id), [
            'name' => 'IT Admin Baru',
            'email' => $it->email,
            'entity' => $it->entity,
            'position' => 'Head of IT',
            'departement_id' => 1,
            'role' => 'it',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertEquals('IT Admin Baru', $it->fresh()->name);
    }

    // --- Reset password ---

    public function test_it_can_reset_another_users_password_to_default(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');
        $target = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($it)->post(route('users.reset-password', $target->id));

        $response->assertRedirect();
        $this->assertTrue(Hash::check(User::DEFAULT_PASSWORD, $target->fresh()->password));
    }

    public function test_non_it_cannot_reset_password(): void
    {
        $this->seedDepartements();
        $head = $this->makeUser('head', 1, 'head@test.com');
        $target = $this->makeUser('user', 1, 'user-a@test.com');
        $originalHash = $target->password;

        $response = $this->actingAs($head)->post(route('users.reset-password', $target->id));

        $response->assertForbidden();
        $this->assertEquals($originalHash, $target->fresh()->password);
    }

    // --- Nonaktifkan / aktifkan akun ---

    public function test_it_can_deactivate_another_users_account(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');
        $target = $this->makeUser('user', 1, 'user-a@test.com');

        $response = $this->actingAs($it)->post(route('users.toggle-active', $target->id));

        $response->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_it_can_reactivate_a_deactivated_account(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');
        $target = $this->makeUser('user', 1, 'user-a@test.com', ['is_active' => false]);

        $response = $this->actingAs($it)->post(route('users.toggle-active', $target->id));

        $response->assertRedirect();
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_it_cannot_deactivate_own_account(): void
    {
        $this->seedDepartements();
        $it = $this->makeUser('it', 1, 'it@test.com');

        $response = $this->actingAs($it)->post(route('users.toggle-active', $it->id));

        $response->assertForbidden();
        $this->assertTrue($it->fresh()->is_active);
    }

    // --- Login diblokir buat akun nonaktif ---

    public function test_inactive_user_cannot_login_even_with_correct_password(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-a@test.com', [
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_active_user_can_login_with_correct_password(): void
    {
        $this->seedDepartements();
        $user = $this->makeUser('user', 1, 'user-a@test.com', [
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
