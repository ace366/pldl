<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Child extends Model
{
    protected $table = 'children';

    // ★これだけを使う（guardedは使わない）
    protected $fillable = [
        'child_code',
        'family_login_code',
        'message_icon_guardian_id',
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'name',
        'birth_date',
        'grade',
        'base_id',
        'school_id',
        'has_allergy',
        'allergy_note',
        'status',
        'note',
        // children.base(文字列) を使っている環境があるなら入れてOK
        // 'base',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'grade'     => 'integer',
        'base_id'   => 'integer',
        'school_id' => 'integer',
        'message_icon_guardian_id' => 'integer',
        'has_allergy' => 'boolean',
    ];

    /**
     * 拠点（マスタ）
     */
    public function baseMaster(): BelongsTo
    {
        return $this->belongsTo(Base::class, 'base_id');
    }

    /**
     * 互換：他のコードが $child->base を参照しているため alias
     */
    public function base(): BelongsTo
    {
        return $this->baseMaster();
    }

    /**
     * 学校
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * ✅ 保護者（多対多）
     * pivot: child_guardian（child_id, guardian_id, relationship, relation, created_at, updated_at）
     *
     * ※ pivot に created_at/updated_at が無いなら withTimestamps() を削除してください
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'child_guardian', 'child_id', 'guardian_id')
            ->withPivot(['relationship', 'relation'])
            ->withTimestamps();
    }
    /**
     * ✅ TEL票（やり取り履歴）
     * 最新順で取得しやすいように並び順もここで定義
     */
    public function contactLogs()
    {
        return $this->hasMany(ChildContactLog::class, 'child_id')
            ->latest('id');
    }
    /**
     * 表示用
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->last_name ?? '') . ' ' . ($this->first_name ?? ''));
    }
}
