<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MasterEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Password default semua akun dummy ini diambil dari .env
     * (DEFAULT_USER_PASSWORD) - lihat App\Models\User::defaultPassword().
     * Bisa dipakai buat login pertama kali, nanti user ganti sendiri lewat
     * halaman Profil. Kalau database kamu udah pernah di-seed sebelum
     * password default ini diganti, jalanin `php artisan users:reset-default-password`
     * biar akun yang udah ada ikut ke-update tanpa perlu migrate:fresh.
     */
    public function run(): void
    {
        $employees = [
            ['name' => 'Admin IT Pusat', 'email' => 'it@office.com', 'entity' => 'Kantor Pusat', 'position' => 'IT Manager', 'departement_id' => 1, 'role' => 'it'],
            ['name' => 'Bapak Head Ops', 'email' => 'head@office.com', 'entity' => 'Kantor Pusat', 'position' => 'Head of Operations', 'departement_id' => 5, 'role' => 'head'],
            ['name' => 'Staff Procurement', 'email' => 'procurement@office.com', 'entity' => 'Kantor Pusat', 'position' => 'Purchasing Officer', 'departement_id' => 3, 'role' => 'procurement'],
            ['name' => 'Staff Finance', 'email' => 'finance@office.com', 'entity' => 'Kantor Pusat', 'position' => 'Finance AP', 'departement_id' => 2, 'role' => 'finance'],
            ['name' => 'Karyawan VP', 'email' => 'user.vp@office.com', 'entity' => 'VP', 'position' => 'Staff Ops', 'departement_id' => 5, 'role' => 'user'],
            ['name' => 'Karyawan VKI', 'email' => 'user.vki@office.com', 'entity' => 'VKI', 'position' => 'Staff HR', 'departement_id' => 4, 'role' => 'user'],
            ['name' => 'Karyawan BO', 'email' => 'user.bo@office.com', 'entity' => 'BO', 'position' => 'Staff Admin', 'departement_id' => 2, 'role' => 'user'],
        ];

        foreach ($employees as $emp) {
            User::create(array_merge($emp, [
                'password' => Hash::make(User::defaultPassword()),
                'email_verified_at' => now(),
            ]));
        }
    }
}