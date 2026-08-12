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
            self::Rejected => 'Ditolak',
        };
    }

    /**
     * Tahap berikutnya setelah status ini di-approve. Alurnya seragam buat
     * hardware maupun software: Head -> IT -> Finance -> Approved.
     * Approved/Rejected itu status final, jadi next()-nya balik ke diri sendiri.
     */
    public function next(): self
    {
        return match ($this) {
            self::WaitingHeadApproval => self::WaitingItApproval,
            self::WaitingItApproval => self::WaitingFinanceApproval,
            self::WaitingFinanceApproval => self::Approved,
            default => $this,
        };
    }
}