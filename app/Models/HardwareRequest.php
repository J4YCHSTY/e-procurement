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
    ];
}
