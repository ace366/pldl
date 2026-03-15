<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyMessageAdminRead extends Model
{
    protected $table = 'family_message_admin_reads';

    protected $fillable = [
        'family_message_id',
        'child_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}
