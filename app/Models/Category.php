<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description', 'is_active'])]
class Category extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class);
    }
}
