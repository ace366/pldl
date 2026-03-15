<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'child_id',
        'scanned_by',
        'attendance_type',
        'attended_at',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
    ];
}
