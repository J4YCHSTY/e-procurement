<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Satu perubahan status pada sebuah pengajuan.
 *
 * Baris di tabel ini tidak pernah diubah setelah dibuat - itu sebabnya
 * $timestamps dimatikan sebagian: cuma created_at yang berarti, updated_at
 * tidak, karena tidak ada yang namanya "kejadian yang diperbarui".
 */
class RequestEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'from_status',
        'to_status',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Kalimat yang ditampilkan di linimasa.
     *
     * Diturunkan dari status TUJUAN, bukan disimpan sebagai teks, supaya
     * kalimatnya tidak bisa berbeda-beda antar baris untuk kejadian yang sama -
     * dan supaya memperbaiki redaksinya cukup di satu tempat.
     */
    public function title(): string
    {
        $to = RequestStatus::tryFrom($this->to_status);

        if ($to === null) {
            return 'Status berubah';
        }

        if ($this->from_status === null) {
            return 'Pengajuan dibuat';
        }

        return match ($to) {
            RequestStatus::WaitingHeadItApproval => 'Disetujui Kepala Departemen',
            RequestStatus::OnExternalProcess => 'Disetujui Head of IT, berkas diteruskan ke IT Admin',
            RequestStatus::ItemOnTheWay => 'Barang dalam perjalanan',
            RequestStatus::WaitingBastSignature => 'Barang diterima IT, BAST menunggu tanda tangan pemohon',
            RequestStatus::Completed => 'BAST ditandatangani pemohon, pengajuan selesai',
            RequestStatus::Rejected => 'Pengajuan ditolak',
            default => $to->label(),
        };
    }
}
