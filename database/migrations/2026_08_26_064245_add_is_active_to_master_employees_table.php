<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nambah kolom status aktif/nonaktif buat fitur User Management (IT).
 *
 * Sengaja NONAKTIFKAN, bukan HAPUS PERMANEN, karena hardware_requests dan
 * software_requests punya FK ke master_employees dengan onDelete('cascade') -
 * kalau akunnya beneran dihapus, semua riwayat pengajuan & approval milik
 * orang itu ikut lenyap. Nonaktifkan cukup blokir login doang, data histori
 * tetap aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
