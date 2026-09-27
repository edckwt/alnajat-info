<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * إعدادات مفتاح/قيمة. تُقرأ كلها باستعلام واحد وتُحفظ في الكاش
 * (النظام القديم كان ينفّذ استعلاماً لكل مفتاح).
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    public $timestamps = false;

    private const CACHE_KEY = 'settings.all';

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::values()[$key] ?? $default;
    }

    /** @param array<string,mixed> $values */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string,?string> */
    public static function values(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all(),
        );
    }
}
