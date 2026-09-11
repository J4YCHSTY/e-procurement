<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanda tangan digital karyawan.
     *
     * Yang disimpan di kolom ini cuma NAMA BERKAS-nya, bukan gambarnya.
     * Gambarnya ada di storage/app/private/signatures/ dalam keadaan
     * TERENKRIPSI - lihat App\Support\Signature\SignatureStorage. Direktori
     * itu di luar public/, jadi web server memang tidak punya jalan ke sana.
     */
    public function up(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('can_manage_users');
            $table->timestamp('signature_uploaded_at')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'signature_uploaded_at']);
        });
    }
};
