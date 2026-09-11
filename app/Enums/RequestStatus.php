<?php

namespace App\Enums;

/**
 * Semua kemungkinan status sebuah pengajuan (hardware/software), berurutan
 * sesuai tahapannya. Dipakai di RequestPolicy buat nentuin siapa yang berhak
 * memproses di tahap mana, dan di RequestController buat nge-set status awal
 * pas pengajuan baru dibuat.
 *
 * Batas sistem ini berhenti di IT Admin. Begitu Head of IT setuju, urusannya
 * pindah ke procurement dan finance yang prosesnya di luar sistem (quotation,
 * PO, pembayaran) - makanya tahap sesudahnya bukan "menunggu approval finance"
 * lagi, tapi ON_EXTERNAL_PROCESS: pengajuannya nyangkut di meja IT Admin
 * sebagai penanda bahwa berkasnya sedang diproses di luar.
 */
enum RequestStatus: string
{
    case WaitingHeadApproval = 'WAITING_FOR_HEAD_APPROVAL';
    case WaitingHeadItApproval = 'WAITING_FOR_HEAD_IT_APPROVAL';
    case OnExternalProcess = 'ON_EXTERNAL_PROCESS';
    case ItemOnTheWay = 'ITEM_ON_THE_WAY';
    case WaitingBastSignature = 'WAITING_FOR_BAST_SIGNATURE';
    case Completed = 'COMPLETED';
    case Rejected = 'REJECTED';

    /**
     * Label buat ditampilkan di frontend (badge status, dsb).
     */
    public function label(): string
    {
        return match ($this) {
            self::WaitingHeadApproval => 'Menunggu Kepala Departemen',
            self::WaitingHeadItApproval => 'Menunggu Head of IT',
            self::OnExternalProcess => 'Diproses Eksternal',
            self::ItemOnTheWay => 'Barang Dalam Perjalanan',
            self::WaitingBastSignature => 'Menunggu TTD BAST',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Tahap berikutnya setelah status ini diproses. Alurnya seragam buat
     * hardware maupun software:
     *
     *   Head Departemen -> Head of IT -> IT Admin -> pemohon -> Selesai
     *
     * Tiga tahap terakhir bukan approval lagi, tapi pencatatan perjalanan
     * barangnya:
     *
     *   ON_EXTERNAL_PROCESS        berkas diserahkan ke procurement/finance
     *   ITEM_ON_THE_WAY            PO sudah terbit, barang dalam perjalanan
     *   WAITING_FOR_BAST_SIGNATURE barang sampai, BAST menunggu tanda tangan pemohon
     *
     * Yang menutup alur adalah PEMOHON, bukan IT - begitu dia menandatangani
     * BAST, serah terimanya sah dan tidak ada lagi yang perlu diputuskan.
     * Completed/Rejected itu status final, jadi next()-nya balik ke diri sendiri.
     */
    public function next(): self
    {
        return match ($this) {
            self::WaitingHeadApproval => self::WaitingHeadItApproval,
            self::WaitingHeadItApproval => self::OnExternalProcess,
            self::OnExternalProcess => self::ItemOnTheWay,
            self::ItemOnTheWay => self::WaitingBastSignature,
            self::WaitingBastSignature => self::Completed,
            default => $this,
        };
    }

    /**
     * Urutan numerik tahap, dipakai buat bandingin "udah lewat tahap mana"
     * waktu nyusun riwayat approval per role (lihat
     * DashboardController::approvalHistoryFor()).
     *
     * Head(0) -> Head of IT(1) -> Diproses Eksternal(2) -> Dalam Perjalanan(3)
     * -> Menunggu TTD BAST(4) -> Selesai(5). Rejected sengaja
     * dikasih -1 karena dia bukan "tahap" - posisi tahap tempat dia ditolak
     * disnapshot terpisah di kolom rejected_at_stage, bukan dari order()
     * status Rejected itu sendiri.
     */
    public function order(): int
    {
        return match ($this) {
            self::WaitingHeadApproval => 0,
            self::WaitingHeadItApproval => 1,
            self::OnExternalProcess => 2,
            self::ItemOnTheWay => 3,
            self::WaitingBastSignature => 4,
            self::Completed => 5,
            self::Rejected => -1,
        };
    }

    /**
     * Apakah di tahap ini pengajuan masih boleh ditolak.
     *
     * Cuma dua tahap awal yang berupa keputusan. Begitu masuk
     * ON_EXTERNAL_PROCESS dan seterusnya, berkasnya sudah dilempar ke
     * procurement/finance di luar sistem - kalau di sana batal, itu
     * pembatalan, bukan penolakan approval, dan penanganannya beda
     * (lihat catatan di RequestPolicy).
     */
    public function isDecisionStage(): bool
    {
        return in_array($this, [self::WaitingHeadApproval, self::WaitingHeadItApproval], true);
    }

    /**
     * Tahap-tahap yang berupa keputusan approval berjenjang, terurut.
     * Dipakai buat mencocokkan nilai rejected_at_stage.
     */
    public static function decisionStages(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status) => $status->isDecisionStage(),
        ));
    }

    /**
     * Tahap-tahap yang jadi tanggung jawab IT Admin: mencatat perjalanan
     * barangnya sampai BAST terbit.
     *
     * @return array<int, self>
     */
    public static function itAdminStages(): array
    {
        return [self::OnExternalProcess, self::ItemOnTheWay];
    }
}
