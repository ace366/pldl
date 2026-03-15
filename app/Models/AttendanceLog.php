<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AttendanceLog extends Model
{
    protected $table = 'attendance_logs';

    // ログは基本削除しない前提（soft delete無し）
    protected $fillable = [
        'shift_id',
        'shift_attendance_id',
        'user_id',
        'base_id',
        'action',
        'source',
        'occurred_at',
        'ip_address',
        'user_agent',
        'payload',
        'actor_user_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload'     => 'array', // JSON <-> array
    ];

    /**
     * リレーション: シフト（予定）
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * リレーション: 勤怠実績
     */
    public function attendance()
    {
        return $this->belongsTo(ShiftAttendance::class, 'shift_attendance_id');
    }

    /**
     * リレーション: 対象スタッフ
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * リレーション: 拠点
     */
    public function base()
    {
        return $this->belongsTo(Base::class, 'base_id');
    }

    /**
     * リレーション: 操作者（本人 or 管理者）
     * ※ actor_user_id はFKを貼っていないので belongsTo でOK
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * scope: 拠点
     */
    public function scopeForBase(Builder $query, int $baseId): Builder
    {
        return $query->where('base_id', $baseId);
    }

    /**
     * scope: 対象ユーザー
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * scope: アクション
     */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * アクション定義（バリデーション・UI向け）
     */
    public static function actions(): array
    {
        return [
            'clock_in',
            'clock_out',
            'admin_edit',
            'admin_create',
            'admin_delete',
            // 将来:
            'request_fix',
            'approve_fix',
        ];
    }

    /**
     * ソース定義
     */
    public static function sources(): array
    {
        return [
            'web',
            'qr',
            'admin',
            'api',
        ];
    }
}
