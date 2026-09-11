<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferensi barang: tangkapan layar produk yang diunggah pemohon.
     *
     * Sengaja gambar, BUKAN tautan marketplace - tautan gampang mati begitu
     * produknya dihapus penjual, sedangkan gambarnya tetap terbaca setahun
     * kemudian waktu berkasnya dibuka lagi buat arsip.
     *
     * Yang disimpan di kolom ini cuma nama berkasnya; gambarnya ada di
     * storage/app/private/request-preferences/ dan hanya bisa dibuka lewat
     * route ber-otorisasi (lihat RequestPolicy::view).
     */
    public function up(): void
    {
        Schema::table('hardware_requests', function (Blueprint $table) {
            $table->string('preference_image_path')->nullable()->after('hardware_recommendation');
        });

        Schema::table('software_requests', function (Blueprint $table) {
            $table->string('preference_image_path')->nullable()->after('software_name');
        });
    }

    public function down(): void
    {
        Schema::table('hardware_requests', function (Blueprint $table) {
            $table->dropColumn('preference_image_path');
        });

        Schema::table('software_requests', function (Blueprint $table) {
            $table->dropColumn('preference_image_path');
        });
    }
};
