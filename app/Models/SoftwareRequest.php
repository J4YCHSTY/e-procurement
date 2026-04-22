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
        'license_count',
        'duration_months',
        'estimated_cost',
        'justification',
        'digital_signature',
        'status',
    ];
}
