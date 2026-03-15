<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ShiftAttendance extends Model
{
    use SoftDeletes;

    protected $table = 'shift_attendances';

    protected $fillable = [
        'shift_id',
        'base_id',
        'user_id',
        'attendance_date',
        'clock_in_at',
        'clock_out_at',
        'break_minutes',
        'auto_break_minutes',
        'work_minutes',
        'status',
        'is_locked',
        'locked_at',
        'locked_by',
        'note',
    ];

    protected $casts = [
        'attendance_date'    => 'date',
        'clock_in_at'        => 'datetime',
        'clock_out_at'       => 'datetime',
        'break_minutes'      => 'integer',
        'auto_break_minutes' => 'integer',
        'work_minutes'       => 'integer',
        'is_locked'          => 'boolean',
        'locked_at'          => 'datetime',
    ];

    /**
     * リレーション: シフト（予定）
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * リレーション: 拠点
     */
    public function base()
    {
        return $this->belongsTo(Base::class, 'base_id');
    }

    /**
     * リレーション: スタッフ（ユーザー）
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * リレーション: 監査ログ
     */
    public function logs()
    {
        return $this->hasMany(AttendanceLog::class, 'shift_attendance_id');
    }

    /**
     * scope: 拠点
     */
    public function scopeForBase(Builder $query, int $baseId): Builder
    {
        return $query->where('base_id', $baseId);
    }

    /**
     * scope: ユーザー
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * scope: 日付
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('attendance_date', $date);
    }

    /**
     * scope: 月（YYYY-MM）
     */
    public function scopeForMonth(Builder $query, string $yearMonth): Builder
    {
        return $query->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$yearMonth]);
    }

    /**
     * 状態一覧
     */
    public static function statuses(): array
    {
        return [
            'scheduled',
            'working',
            'completed',
            'absent',
        ];
    }

    /**
     * 勤務中か？
     */
    public function isWorking(): bool
    {
        return $this->status === 'working';
    }

    /**
     * 完了か？
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 打刻済み判定
     */
    public function hasClockIn(): bool
    {
        return !is_null($this->clock_in_at);
    }

    public function hasClockOut(): bool
    {
        return !is_null($this->clock_out_at);
    }

    /**
     * 表示用: 勤務時間（h:mm）
     */
    public function getWorkTimeLabelAttribute(): string
    {
        $m = (int)($this->work_minutes ?? 0);
        $h = intdiv($m, 60);
        $r = $m % 60;
        return sprintf('%d:%02d', $h, $r);
    }

    /**
     * ロックされているか
     */
    public function isLocked(): bool
    {
        return (bool)$this->is_locked;
    }
}
