<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'description', 'url', 'image', 'body', 'publication_date',
    'cover', 'other_file', 'pdf_version', 'pdf_template_id', 'is_active',
])]
class Publication extends Model
{
    protected function casts(): array
    {
        return [
            'publication_date' => DateOnly::class,
            'is_active' => 'boolean',
        ];
    }

    /** أخبار النشرة = أخبار تاريخها، كما في النظام القديم. */
    public function news(): HasMany
    {
        return $this->hasMany(News::class, 'published_date', 'publication_date');
    }

    /** قالب مصمَّم (إن وُجد)؛ وإلا تُرسم بالقالب القديم pdf_version. */
    public function pdfTemplate(): BelongsTo
    {
        return $this->belongsTo(PdfTemplate::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
