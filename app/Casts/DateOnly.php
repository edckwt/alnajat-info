<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * عمود DATE يُخزَّن دائماً 'Y-m-d' مهما كانت قاعدة البيانات.
 *
 * cast 'date' العادي في Laravel يخزّن 'Y-m-d H:i:s'، فتفشل المقارنة المباشرة
 * news.published_date = publications.publication_date على SQLite (الاختبارات)
 * ويختلف شكل القيمة عن البيانات المنقولة من النظام القديم.
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return blank($value) ? null : CarbonImmutable::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (blank($value)) {
            return null;
        }

        return ($value instanceof DateTimeInterface ? CarbonImmutable::instance($value) : CarbonImmutable::parse($value))
            ->toDateString();
    }
}
