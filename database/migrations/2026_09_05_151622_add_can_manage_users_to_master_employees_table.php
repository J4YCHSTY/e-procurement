<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelum ini, akses menu Manajemen User nempel jadi satu sama role 'it'
 * (role yang sama yang juga dipakai buat approve pengajuan di tahap IT).
 * Ternyata di lapangan itu kekakuan yang nggak perlu: ada orang yang cuma
 * perlu ngurus akun karyawan (bikin/edit/reset password) TANPA ikut approve
 * pengajuan hardware/software sama sekali - kolom ini misahin dua hal itu.
 *
 * 'role' tetap ngatur alur approval berjenjang (head/it/finance/procurement)
 * seperti biasa. 'can_manage_users' ngatur SIAPAPUN (apapun role-nya,
 * termasuk 'user') boleh buka menu Manajemen User atau nggak - lihat
 * App\Policies\UserPolicy yang udah diupdate buat baca kolom ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->boolean('can_manage_users')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->dropColumn('can_manage_users');
        });
    }
};
