<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'logo', 'url', 'country', 'description', 'body', 'type', 'is_active'])]
class Newspaper extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }
}
