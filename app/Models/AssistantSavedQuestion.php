<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantSavedQuestion extends Model
{
    public const MAX_PER_USER = 8;

    protected $fillable = [
        'user_id',
        'question',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
