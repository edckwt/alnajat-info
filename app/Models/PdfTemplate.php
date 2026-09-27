<?php

namespace App\Models;

use App\Support\PdfDesign;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'design'])]
class PdfTemplate extends Model
{
    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    protected function casts(): array
    {
        return [
            'design' => 'array',
            'is_default' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::PUBLISHED;
    }

    /** المعتمد لا يُعدّل حتى لا يتغير شكل النشرات التي تستخدمه؛ يُنسخ ثم يُعدّل. */
    public function isEditable(): bool
    {
        return ! $this->isPublished();
    }

    public function toDesign(): PdfDesign
    {
        return PdfDesign::fromArray($this->design ?? []);
    }

    /** القالب الافتراضي للنشرات الجديدة (معتمد)، أو null = القالب القديم الأحدث. */
    public static function default(): ?self
    {
        return static::published()->where('is_default', true)->first();
    }
}
