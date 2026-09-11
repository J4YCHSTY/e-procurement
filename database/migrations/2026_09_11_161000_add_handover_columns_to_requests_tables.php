<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data serah terima - yang nanti dicetak di BAST.
 *
 * Nomor seri dicatat waktu IT Admin menyerahkan barangnya, bukan waktu
 * pengajuan dibuat: waktu mengajukan, barangnya memang belum ada. Tanpa nomor
 * seri, BAST tidak bisa membuktikan unit MANA yang diserahkan - dan itu justru
 * hal pertama yang dicari kalau nanti ada perselisihan soal aset.
 */
return new class extends Migration
{
    private const TABLES = ['hardware_requests', 'software_requests'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Software tidak punya nomor seri fisik, tapi punya kunci
                // lisensi yang perannya persis sama - satu kolom cukup.
                $table->string('item_identifier')->nullable()->after('status');
                $table->timestamp('handed_over_at')->nullable()->after('item_identifier');
                $table->foreignId('handed_over_by_id')->nullable()->after('handed_over_at')
                    ->constrained('master_employees')->nullOnDelete();
                $table->timestamp('bast_signed_at')->nullable()->after('handed_over_by_id');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('handed_over_by_id');
                $table->dropColumn(['item_identifier', 'handed_over_at', 'bast_signed_at']);
            });
        }
    }
};
