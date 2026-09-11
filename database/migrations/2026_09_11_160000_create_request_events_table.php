<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak perjalanan sebuah pengajuan: satu baris untuk setiap perubahan status.
 *
 * Sebelum ini kolom `status` cuma menyimpan kondisi TERKINI. Begitu pengajuan
 * maju, informasi "siapa menyetujui, kapan" hilang - yang tersisa cuma tebakan
 * dari urutan tahap. Buat linimasa di halaman detail itu tidak cukup, dan buat
 * dokumen yang dicetak nanti jelas tidak cukup: Form Request harus memuat
 * tanggal persetujuan tiap penyetuju, dan BAST harus memuat tanggal serah
 * terimanya.
 *
 * Tabelnya polymorphic karena hardware_requests dan software_requests adalah
 * dua tabel terpisah dengan alur yang sama persis - satu tabel jejak untuk
 * keduanya lebih jujur daripada dua tabel kembar.
 *
 * Baris di sini bersifat APPEND-ONLY: tidak pernah diubah atau dihapus, karena
 * inilah yang jadi bukti alur kalau isi dokumennya dipertanyakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_events', function (Blueprint $table) {
            $table->id();
            $table->morphs('requestable');

            // Boleh kosong: kejadian yang dipicu sistem (bukan orang) tetap
            // perlu tercatat, dan akun pelakunya bisa saja dihapus belakangan.
            $table->foreignId('actor_id')->nullable()->constrained('master_employees')->nullOnDelete();

            // Kosong berarti kejadian pertama - pengajuannya baru dibuat.
            $table->string('from_status')->nullable();
            $table->string('to_status');

            // Catatan bebas dari pelaku, misal "PO sudah terbit, estimasi
            // barang tiba minggu depan" atau alasan penolakan.
            $table->text('note')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Linimasa selalu dibaca per-pengajuan dan urut waktu.
            $table->index(['requestable_type', 'requestable_id', 'created_at'], 'request_events_timeline_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_events');
    }
};
