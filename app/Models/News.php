<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'title', 'source_url', 'image', 'description', 'body', 'newspaper_id', 'newspaper_number',
    'published_date', 'is_active', 'hide_in_pdf', 'hide_title', 'hide_description', 'hide_more',
    'tweet_url', 'sound_url', 'video_url', 'original_image', 'type', 'sort_order',
])]
class News extends Model
{
    protected $table = 'news';

    protected function casts(): array
    {
        return [
            'published_date' => DateOnly::class,
            'is_active' => 'boolean',
            'hide_in_pdf' => 'boolean',
            'hide_title' => 'boolean',
            'hide_description' => 'boolean',
            'hide_more' => 'boolean',
            'original_image' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function newspaper(): BelongsTo
    {
        return $this->belongsTo(Newspaper::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** إعدادات نوع الخبر من config/alnajat.php (خبر، صوتي، مرئي، تغريدة، مشروع). */
    public function typeConfig(): array
    {
        return config('alnajat.news_types.'.$this->type) ?? config('alnajat.news_types.1');
    }

    /** رابط الصورة (نسبي أو كامل). */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => Media::url($this->image));
    }

    /** المصغّر 150×150 إن وُجد، وإلا الأصل. */
    protected function thumbUrl(): Attribute
    {
        return Attribute::get(fn () => Media::thumb($this->image, 'xsmall'));
    }
}
