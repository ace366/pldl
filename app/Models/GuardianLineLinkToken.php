<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianLineLinkToken extends Model
{
    protected $fillable = [
        'guardian_id',
        'token_hash',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    public static function hashToken(string $plainToken): string
    {
        $key = (string) config('app.key', 'pldl-line-link');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false && $decoded !== '') {
                $key = $decoded;
            }
        }

        if ($key === '') {
            $key = 'pldl-line-link';
        }

        return hash_hmac('sha256', $plainToken, $key);
    }
}
