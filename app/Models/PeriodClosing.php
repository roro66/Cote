<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodClosing extends Model
{
    protected $fillable = ['period', 'closed_by', 'closed_at', 'treasury_counted', 'notes', 'snapshot'];

    protected $casts = [
        'period' => 'date',
        'closed_at' => 'datetime',
        'treasury_counted' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
