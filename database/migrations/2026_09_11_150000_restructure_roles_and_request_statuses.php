<?php

use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Merapikan batas sistem: alurnya berhenti di IT Admin.
 *
 * Sebelum ini rantainya Head -> IT -> Finance -> Procurement, padahal yang
 * didigitalkan cuma proses permintaannya. Begitu Head of IT setuju, urusannya
 * pindah ke procurement dan finance yang bekerja di luar sistem (quotation,
 * PO, pembayaran) - jadi tahap mereka nggak pantas ada di sini sebagai
 * "approval", karena sistem nggak punya data apapun buat mendukungnya.
 *
 * Migrasi ini mengerjakan tiga hal:
 *
 *  1. Kolom `role` diubah dari enum database jadi string biasa. Menambah peran
 *     it_head lewat enum berarti ALTER TABLE tiap kali daftar perannya berubah,
 *     dan di SQLite (dipakai waktu test) enum cuma jadi CHECK constraint yang
 *     merepotkan waktu diubah. Daftar peran yang sah sekarang dipegang satu
 *     tempat saja: App\Models\User::ROLES, dan divalidasi di UserManagementController.
 *  2. Akun ber-role finance & procurement diturunkan jadi user biasa. Orangnya
 *     tetap karyawan yang butuh laptop, cuma nggak punya tahap approval lagi.
 *  3. Pengajuan yang terlanjur tersimpan dengan status lama dipetakan ke status
 *     baru. Tanpa ini, RequestStatus::from() bakal melempar ValueError begitu
 *     baris lama dibuka - dan pengajuan yang sedang berjalan jadi tidak bisa
 *     diproses sama sekali.
 */
return new class extends Migration
{
    /**
     * Status lama -> status baru.
     *
     * WAITING_FOR_FINANCE_APPROVAL dan APPROVED sama-sama jatuh ke
     * ON_EXTERNAL_PROCESS: keduanya berarti approval internal sudah selesai
     * dan berkasnya sedang jalan di luar sistem. Yang membedakan keduanya dulu
     * cuma siapa yang memegang, dan pemegangnya sekarang satu: IT Admin.
     */
    private const STATUS_MAP = [
        'WAITING_FOR_IT_APPROVAL' => 'WAITING_FOR_HEAD_IT_APPROVAL',
        'WAITING_FOR_FINANCE_APPROVAL' => 'ON_EXTERNAL_PROCESS',
        'APPROVED' => 'ON_EXTERNAL_PROCESS',
    ];

    /**
     * Kebalikannya buat rollback. APPROVED sengaja tidak dipulihkan karena
     * satu nilai baru tidak bisa dipecah lagi jadi dua nilai lama - dan
     * menebak-nebak justru berisiko memundurkan pengajuan yang sudah jalan.
     */
    private const STATUS_MAP_REVERSE = [
        'WAITING_FOR_HEAD_IT_APPROVAL' => 'WAITING_FOR_IT_APPROVAL',
        'ON_EXTERNAL_PROCESS' => 'WAITING_FOR_FINANCE_APPROVAL',
    ];

    /**
     * Kolom rejected_at_stage butuh peta sendiri, TIDAK boleh ikut STATUS_MAP.
     *
     * Kolom itu menyimpan "ditolak waktu menunggu keputusan siapa", dan
     * riwayat approval per peran mencocokkannya dengan daftar tahap keputusan.
     * Kalau penolakan lama di tahap Finance dipetakan ke ON_EXTERNAL_PROCESS -
     * yang sekarang bukan tahap keputusan - pengajuannya hilang dari riwayat
     * semua orang, termasuk kepala departemen yang dulu menyetujuinya.
     *
     * Jadi penolakan lama itu digeser ke titik keputusan terakhir yang masih
     * ada, yaitu Head of IT. Siapa yang sebenarnya menolak tidak hilang:
     * namanya tetap tersimpan di rejected_by_id dan itu yang ditampilkan
     * di layar.
     */
    private const STAGE_MAP = [
        'WAITING_FOR_IT_APPROVAL' => 'WAITING_FOR_HEAD_IT_APPROVAL',
        'WAITING_FOR_FINANCE_APPROVAL' => 'WAITING_FOR_HEAD_IT_APPROVAL',
        'APPROVED' => 'WAITING_FOR_HEAD_IT_APPROVAL',
    ];

    private const STAGE_MAP_REVERSE = [
        'WAITING_FOR_HEAD_IT_APPROVAL' => 'WAITING_FOR_IT_APPROVAL',
    ];

    public function up(): void
    {
        Schema::table('master_employees', function (Blueprint $table) {
            $table->string('role', 32)->default('user')->change();
        });

        DB::table('master_employees')
            ->whereIn('role', ['finance', 'procurement'])
            ->update(['role' => 'user']);

        $this->remapStatuses(self::STATUS_MAP, self::STAGE_MAP);
    }

    public function down(): void
    {
        $this->remapStatuses(self::STATUS_MAP_REVERSE, self::STAGE_MAP_REVERSE);

        // Akun yang sempat ber-role it_head dikembalikan ke 'it' supaya tetap
        // muat di enum lama. Yang sudah terlanjur diturunkan dari finance atau
        // procurement tidak bisa dikembalikan - informasinya memang sudah hilang.
        DB::table('master_employees')->where('role', 'it_head')->update(['role' => 'it']);

        Schema::table('master_employees', function (Blueprint $table) {
            $table->enum('role', ['user', 'head', 'it', 'finance', 'procurement'])->default('user')->change();
        });
    }

    private function remapStatuses(array $statusMap, array $stageMap): void
    {
        foreach ([HardwareRequest::class, SoftwareRequest::class] as $model) {
            $table = (new $model)->getTable();

            foreach ($statusMap as $from => $to) {
                DB::table($table)->where('status', $from)->update(['status' => $to]);
            }

            foreach ($stageMap as $from => $to) {
                DB::table($table)->where('rejected_at_stage', $from)->update(['rejected_at_stage' => $to]);
            }
        }
    }
};
