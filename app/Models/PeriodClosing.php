<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodClosing extends Model
{
    /** Billetes y monedas chilenos para contar la caja, de mayor a menor. */
    public const DENOMINATIONS = [20000, 10000, 5000, 2000, 1000, 500, 100, 50, 10];

    protected $fillable = ['period', 'closed_by', 'closed_at', 'treasury_counted', 'notes', 'snapshot', 'count_detail'];

    protected $casts = [
        'period' => 'date',
        'closed_at' => 'datetime',
        'treasury_counted' => 'decimal:2',
        'snapshot' => 'array',
        'count_detail' => 'array',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
