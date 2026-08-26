<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HardwareRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_date',
        'hardware_type',
        'hardware_recommendation',
        'justification',
        'digital_signature',
        'status',
        'rejected_by_id',
        'rejected_at',
        'rejected_at_stage',
    ];

    protected $casts = [
        'rejected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User yang nolak pengajuan ini (kalau statusnya REJECTED). Dipakai buat
     * nampilin siapa yang nolak di tab Riwayat Approval.
     */
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }
}
