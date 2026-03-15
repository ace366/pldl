<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Shift extends Model
{
    use SoftDeletes;

    protected $table = 'shifts';

    protected $fillable = [
        'base_id',
        'user_id',
        'shift_date',
        'start_time',
        'end_time',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'shift_date' => 'date',
        // TIME は Laravel の cast が微妙に扱いづらいので、基本は文字列で運用（表示側で整形）
        'start_time' => 'string',
        'end_time'   => 'string',
    ];

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
     * リレーション: 勤怠実績（1シフト1勤怠）
     */
    public function attendance()
    {
        return $this->hasOne(ShiftAttendance::class, 'shift_id');
    }

    /**
     * リレーション: 監査ログ
     */
    public function logs()
    {
        return $this->hasMany(AttendanceLog::class, 'shift_id');
    }

    /**
     * scope: 拠点で絞り込み
     */
    public function scopeForBase(Builder $query, int $baseId): Builder
    {
        return $query->where('base_id', $baseId);
    }

    /**
     * scope: 日付で絞り込み
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('shift_date', $date);
    }

    /**
     * scope: ユーザーで絞り込み
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * scope: 月（YYYY-MM）で絞り込み
     */
    public function scopeForMonth(Builder $query, string $yearMonth): Builder
    {
        // yearMonth: '2026-02'
        return $query->whereRaw("DATE_FORMAT(shift_date, '%Y-%m') = ?", [$yearMonth]);
    }

    /**
     * 便利: 表示用の時間帯文字列
     */
    public function getTimeRangeAttribute(): string
    {
        $s = (string)($this->start_time ?? '');
        $e = (string)($this->end_time ?? '');
        return trim($s) && trim($e) ? "{$s}〜{$e}" : '';
    }

    /**
     * ステータス一覧（UIやバリデーションで使う）
     */
    public static function statuses(): array
    {
        return [
            'scheduled',
            'working',
            'completed',
            'absent',
            'canceled', // 使わないならUIで非表示にするだけでOK
        ];
    }
}
