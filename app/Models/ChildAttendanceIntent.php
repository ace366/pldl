<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildAttendanceIntent extends Model
{
    use HasFactory;

    protected $table = 'child_attendance_intents';

    protected $fillable = [
        'child_id',
        'date',
        'base_id',
        'status', // on / off など
    ];

    protected $casts = [
        'pickup_required'  => 'boolean',
        'pickup_confirmed' => 'boolean',
        'date'             => 'date',
        'manual_updated_at'=> 'datetime',
        'pickup_confirmed_at' => 'datetime',
    ];

    /* =====================
     * リレーション
     * ===================== */

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function base()
    {
        return $this->belongsTo(Base::class);
    }
}
