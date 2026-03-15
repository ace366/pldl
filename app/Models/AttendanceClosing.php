<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceClosing extends Model
{
    protected $table = 'attendance_closings';

    protected $fillable = [
        'base_id',
        'year_month',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
    ];

    protected $casts = [
        'closed_at'   => 'datetime',
        'reopened_at' => 'datetime',
    ];

    /**
     * 締め中判定
     * - closed_at が存在し
     * - reopened_at が無い、または reopened_at が closed_at より前
     * のとき「締め中」とする
     *
     * ※ reopened_at が closed_at より後（または同時刻）なら解除済み扱い
     */
    public static function isClosed(int $baseId, string $yearMonth): bool
    {
        $c = static::query()
            ->where('base_id', $baseId)
            ->where('year_month', $yearMonth)
            ->first();

        if (!$c || !$c->closed_at) {
            return false;
        }

        if (!$c->reopened_at) {
            return true;
        }

        return $c->reopened_at->lt($c->closed_at);
    }
}
