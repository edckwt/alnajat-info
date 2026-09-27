<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * صندوق في الصفحة الرئيسية (context = home) أو في النشرة (context = pdf).
 */
#[Fillable(['context', 'position', 'category_id', 'items_limit', 'type', 'banner_id', 'code'])]
class HomeBox extends Model
{
    public const HOME = 'home';

    public const PDF = 'pdf';

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Banner::class);
    }

    public function scopeFor(Builder $query, string $context): void
    {
        $query->where('context', $context)->orderBy('position');
    }
}
