<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoftwareRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_date',
        'software_name',
        'software_type',
        'software_usage',
        'license_count',
        'duration_months',
        'estimated_cost',
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
