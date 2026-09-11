<?php

namespace App\Models;

use App\Models\Concerns\HasRequestTimeline;
use Illuminate\Database\Eloquent\Model;

class SoftwareRequest extends Model
{
    use HasRequestTimeline;

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
        'item_identifier',
        'handed_over_at',
        'handed_over_by_id',
        'bast_signed_at',
    ];

    protected $appends = ['detail_url'];

    protected $casts = [
        'rejected_at' => 'datetime',
        'handed_over_at' => 'datetime',
        'bast_signed_at' => 'datetime',
    ];

    /**
     * Segmen URL yang mewakili jenis pengajuan ini. Dipakai HasRequestTimeline
     * buat menyusun tautan detailnya.
     */
    public function requestTypeKey(): string
    {
        return 'software';
    }

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
