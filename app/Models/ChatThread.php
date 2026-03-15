<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    protected $fillable = [
        'guardian_id',
        'status',
        'last_message_at',
        'unread_count_staff',
        'last_staff_read_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_staff_read_at' => 'datetime',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'thread_id')->latestOfMany('id');
    }
}
