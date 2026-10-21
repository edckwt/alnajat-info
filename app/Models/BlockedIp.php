<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** عنوان محظور (App\Support\Firewall). */
class BlockedIp extends Model
{
    public const REASONS = [
        'trap' => 'رابط مصيدة',
        'flood' => 'صفحات غير موجودة كثيرة',
        'manual' => 'يدوي',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['blocked_until' => 'datetime', 'strikes' => 'integer', 'hits' => 'integer'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->blocked_until === null || $this->blocked_until->isFuture();
    }

    public function isPermanent(): bool
    {
        return $this->blocked_until === null;
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function scopeActive(Builder $query): void
    {
        $query->where(fn ($q) => $q->whereNull('blocked_until')->orWhere('blocked_until', '>', now()));
    }

    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull('blocked_until')->where('blocked_until', '<=', now());
    }
}
