<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'url', 'image', 'description', 'body', 'is_active'])]
class Banner extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
