<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEmployee extends Model
{
    protected $fillable = [
        'name',
        'email',
        'entity',
        'position',
        'departement_id',
        'role',
    ];
}
