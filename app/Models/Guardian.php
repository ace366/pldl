<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    protected $table = 'guardians';

    protected $fillable = [
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'name',
        'line_user_id',
        'email',
        'phone',
        'emergency_phone',
        'emergency_phone_label',
        'preferred_contact',
        'avatar_path',
    ];

    /**
     * ✅ 児童（多対多）
     * pivot: child_guardian（child_id, guardian_id, relationship, relation, created_at, updated_at）
     *
     * ※ pivot に created_at/updated_at が無いなら withTimestamps() を削除してください
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_guardian', 'guardian_id', 'child_id')
            ->withPivot(['relationship', 'relation'])
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->last_name ?? '') . ' ' . ($this->first_name ?? ''));
    }

    public function lineLinkTokens(): HasMany
    {
        return $this->hasMany(GuardianLineLinkToken::class, 'guardian_id');
    }

    public function chatThreads(): HasMany
    {
        return $this->hasMany(ChatThread::class, 'guardian_id');
    }
}
