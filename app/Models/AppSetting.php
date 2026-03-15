<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (!Schema::hasTable('app_settings')) {
            return $default;
        }

        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        if (!Schema::hasTable('app_settings')) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
