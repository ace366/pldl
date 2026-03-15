<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'child_id',
        'created_by',
        'sender_type',
        'title',
        'body',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(FamilyMessageRead::class, 'message_id');
    }
}
