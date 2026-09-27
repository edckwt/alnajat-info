<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'path', 'size', 'extension', 'mime', 'user_id'])]
class Upload extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
