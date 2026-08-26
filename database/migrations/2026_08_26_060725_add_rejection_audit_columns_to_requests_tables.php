<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nambahin jejak audit buat penolakan pengajuan (hardware & software).
 *
 * Sebelum ini, kolom 'status' cuma nyimpen kondisi TERKINI ('REJECTED'),
 * tanpa nyatet siapa yang nolak dan pengajuan itu lagi di tahap approval
 * mana pas ditolak. Akibatnya nggak bisa dibedain "ditolak sama Head" vs
 * "ditolak sama IT" vs "ditolak sama Finance" - padahal ini penting buat
 * fitur riwayat approval per role (lihat DashboardController::approvalHistoryFor).
 *
 * - rejected_by_id   : siapa (user) yang klik reject.
 * - rejected_at      : kapan ditolaknya.
 * - rejected_at_stage: pengajuan lagi nunggu approval tahap apa pas ditolak
 *                      (snapshot dari RequestStatus, misal 'WAITING_FOR_IT_APPROVAL').
 *                      Disimpan terpisah dari status final 'REJECTED' supaya
 *                      histori "ditolak di tahap mana" tetap kebaca walau
 *                      status akhirnya udah REJECTED.
 *
 * Catatan: rejected_by_id SENGAJA nggak dipasangin foreign key constraint
 * (->constrained()). Nambah FK lewat ALTER TABLE ke tabel yang udah ada itu
 * di SQLite ditangani Laravel dengan cara rebuild seluruh tabel (bikin tabel
 * sementara, pindahin data, drop, rename) - dan ini kebukti bikin masalah pas
 * dites lewat RefreshDatabase + koneksi SQLite ':memory:' (tabel lain ikut
 * "hilang" pas migration jalan). user_id di kolom lain juga nunjukin project
 * ini pun sebenernya berelasi ke tabel 'master_employees', bukan 'users',
 * jadi FK ke 'users' di sini pun bakal salah target. Karena cuma dipakai
 * buat baca riwayat (bukan integritas relasional yang kritikal), kolom polos
 * tanpa constraint sudah cukup aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['hardware_requests', 'software_requests'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('rejected_by_id')->nullable()->after('status');
                $table->timestamp('rejected_at')->nullable()->after('rejected_by_id');
                $table->string('rejected_at_stage')->nullable()->after('rejected_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['hardware_requests', 'software_requests'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['rejected_by_id', 'rejected_at', 'rejected_at_stage']);
            });
        }
    }
};
