<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama perangkat yang diajukan waktu pemohon memilih "Ajukan Perangkat
     * Lain (di luar standar)".
     *
     * Tanpa kolom ini, yang tercatat cuma nilai 'custom' - procurement tidak
     * punya nama barang untuk dicari quotation-nya. Form kertas di kantor
     * memang punya isian ini, jadi form digitalnya perlu menyamai.
     */
    public function up(): void
    {
        Schema::table('hardware_requests', function (Blueprint $table) {
            $table->string('custom_hardware_name')->nullable()->after('hardware_recommendation');
        });
    }

    public function down(): void
    {
        Schema::table('hardware_requests', function (Blueprint $table) {
            $table->dropColumn('custom_hardware_name');
        });
    }
};
