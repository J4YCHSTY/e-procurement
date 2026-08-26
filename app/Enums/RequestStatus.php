<?php

namespace App\Enums;

/**
 * Semua kemungkinan status sebuah pengajuan (hardware/software), berurutan
 * sesuai tahapan approval-nya. Dipakai di RequestPolicy buat nentuin siapa
 * yang berhak approve di tahap mana, dan di RequestController buat nge-set
 * status awal pas pengajuan baru dibuat.
 */
enum RequestStatus: string
{
    case WaitingHeadApproval = 'WAITING_FOR_HEAD_APPROVAL';
    case WaitingItApproval = 'WAITING_FOR_IT_APPROVAL';
    case WaitingFinanceApproval = 'WAITING_FOR_FINANCE_APPROVAL';
    case Approved = 'APPROVED';
    case Completed = 'COMPLETED';
    case Rejected = 'REJECTED';

    /**
     * Label buat ditampilkan di frontend (badge status, dsb).
     */
    public function label(): string
    {
        return match ($this) {
            self::WaitingHeadApproval => 'Menunggu Approval Kepala Departemen',
            self::WaitingItApproval => 'Menunggu Approval Tim IT',
            self::WaitingFinanceApproval => 'Menunggu Approval Finance',
            self::Approved => 'Disetujui',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Tahap berikutnya setelah status ini di-approve. Alurnya seragam buat
     * hardware maupun software: Head -> IT -> Finance -> Approved -> Completed.
     * Approved di sini bukan proses approval lagi, tapi tahap eksekusi
     * administratif oleh procurement (nandain barang/lisensi udah diserahkan).
     * Completed/Rejected itu status final, jadi next()-nya balik ke diri sendiri.
     */
    public function next(): self
    {
        return match ($this) {
            self::WaitingHeadApproval => self::WaitingItApproval,
            self::WaitingItApproval => self::WaitingFinanceApproval,
            self::WaitingFinanceApproval => self::Approved,
            self::Approved => self::Completed,
            default => $this,
        };
    }

    /**
     * Urutan numerik tahap approval, dipakai buat bandingin "udah lewat tahap
     * mana" waktu nyusun riwayat approval per role (lihat
     * DashboardController::approvalHistoryFor()).
     *
     * Head(0) -> IT(1) -> Finance(2) -> Approved/Completed(3), yang berarti
     * udah lewat semua tahap approval berjenjang. Rejected sengaja dikasih -1
     * karena dia bukan "tahap" - posisi tahap tempat dia ditolak disnapshot
     * terpisah di kolom rejected_at_stage, bukan dari order() status Rejected
     * itu sendiri.
     */
    public function order(): int
    {
        return match ($this) {
            self::WaitingHeadApproval => 0,
            self::WaitingItApproval => 1,
            self::WaitingFinanceApproval => 2,
            self::Approved, self::Completed => 3,
            self::Rejected => -1,
        };
    }
}