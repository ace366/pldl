<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class StaffBase extends Model
{
    use SoftDeletes;

    protected $table = 'staff_bases';

    protected $fillable = [
        'user_id',
        'base_id',
        'is_primary',
        'active_from',
        'active_to',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'active_from' => 'date',
        'active_to'   => 'date',
    ];

    /**
     * リレーション: スタッフ（ユーザー）
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
        // Base モデルが App\Models\Base のはずなので、それに合わせる
        return $this->belongsTo(Base::class, 'base_id');
    }

    /**
     * scope: 指定ユーザーの所属
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * scope: 指定拠点の所属
     */
    public function scopeForBase(Builder $query, int $baseId): Builder
    {
        return $query->where('base_id', $baseId);
    }

    /**
     * scope: 現在有効な所属（active_from/to を考慮）
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where(function ($q) use ($today) {
                $q->whereNull('active_from')
                  ->orWhere('active_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('active_to')
                  ->orWhere('active_to', '>=', $today);
            });
    }
}
