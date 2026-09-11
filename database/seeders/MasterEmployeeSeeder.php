<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterEmployeeSeeder extends Seeder
{
    /**
     * Akun dummy buat mencoba alur dari sisi tiap peran.
     *
     * Password semuanya diambil dari .env (DEFAULT_USER_PASSWORD) - lihat
     * App\Models\User::defaultPassword(). Kalau database sudah pernah di-seed
     * sebelum password default diganti, jalankan
     * `php artisan users:reset-default-password` supaya akun lama ikut
     * ter-update tanpa perlu migrate:fresh.
     *
     * PENTING soal role: satu akun cuma punya SATU role. Head of IT dan IT
     * Admin karena itu harus dua akun berbeda, dan sejak rantai approval
     * dirapikan keduanya punya role sendiri-sendiri: 'it_head' buat Head of IT,
     * 'it' buat IT Admin. Sebelumnya Head of IT dipaksa memakai role 'head' -
     * akibatnya dia cuma jadi kepala departemen IT dan sama sekali tidak
     * memegang tahap penilaian teknis, yang justru tugas utamanya.
     *
     * Finance dan procurement sengaja tidak punya role khusus lagi. Pekerjaan
     * mereka (quotation, PO, pembayaran) ada di luar sistem ini, jadi akunnya
     * dibuat sebagai pemohon biasa - mereka tetap karyawan yang butuh laptop.
     *
     * Peta akun (departemen: 1=IT, 2=Finance, 3=Procurement, 4=HR, 5=Operations):
     *
     *   headit@office.com       Head of IT          role it_head, dept IT
     *   it@office.com           IT Admin            role it, dept IT
     *   head@office.com         Head Operations     role head, dept Operations
     *   finance@office.com      Pemohon (Finance)   role user, dept Finance
     *   procurement@office.com  Pemohon (Procure)   role user, dept Procurement
     *   user.vp@office.com      Pemohon (Ops)       role user, dept Operations
     *   user.vki@office.com     Pemohon (HR)        role user, dept HR
     *   user.bdi@office.com     Pemohon (Finance)   role user, dept Finance
     *
     * Departemen IT sengaja tidak diberi akun ber-role 'head'. Kepalanya memang
     * Head of IT itu sendiri, dan ApprovalChain membaca keadaan itu: pengajuan
     * dari orang IT langsung masuk ke tahap Head of IT, tidak lewat tahap
     * kepala departemen dulu - biar orang yang sama tidak menyetujui dua kali.
     *
     * Buat mencoba alur lengkap tanpa membuka browser sama sekali, pakai
     * `php artisan flow:simulate` - datanya di-rollback otomatis.
     */
    public function run(): void
    {
        $employees = [
            // --- Sisi IT: sengaja dua akun, lihat catatan soal role di atas ---
            ['name' => 'Andy (Head of IT)', 'email' => 'headit@office.com', 'entity' => 'PT VISINEMA PICTURES', 'position' => 'VP of Information Technology', 'departement_id' => 1, 'role' => 'it_head', 'can_manage_users' => true],
            ['name' => 'Admin IT Pusat', 'email' => 'it@office.com', 'entity' => 'PT VISINEMA PICTURES', 'position' => 'IT Admin', 'departement_id' => 1, 'role' => 'it', 'can_manage_users' => true],

            // --- Kepala departemen di luar IT ---
            ['name' => 'Bapak Head Ops', 'email' => 'head@office.com', 'entity' => 'PT VISINEMA PICTURES', 'position' => 'Head of Operations', 'departement_id' => 5, 'role' => 'head'],

            // --- Finance & procurement: pemohon biasa, prosesnya di luar sistem ---
            ['name' => 'Staff Finance', 'email' => 'finance@office.com', 'entity' => 'PT VISINEMA PICTURES', 'position' => 'Finance AP', 'departement_id' => 2, 'role' => 'user'],
            ['name' => 'Staff Procurement', 'email' => 'procurement@office.com', 'entity' => 'PT VISINEMA PICTURES', 'position' => 'Purchasing Officer', 'departement_id' => 3, 'role' => 'user'],

            // --- Pemohon biasa, satu per perusahaan ---
            ['name' => 'Karyawan VP', 'email' => 'user.vp@office.com', 'entity' => 'PT VISINEMA PICTURES', 'position' => 'Staff Ops', 'departement_id' => 5, 'role' => 'user'],
            ['name' => 'Karyawan VKI', 'email' => 'user.vki@office.com', 'entity' => 'PT VISINEMA KONTEN INDONESIA', 'position' => 'Staff HR', 'departement_id' => 4, 'role' => 'user'],
            ['name' => 'Karyawan BDI', 'email' => 'user.bdi@office.com', 'entity' => 'PT BIOSKOP DIGITAL INDONESIA', 'position' => 'Staff Admin', 'departement_id' => 2, 'role' => 'user'],
        ];

        foreach ($employees as $employee) {
            User::create(array_merge($employee, [
                'password' => Hash::make(User::defaultPassword()),
                'email_verified_at' => now(),
            ]));
        }
    }
}
