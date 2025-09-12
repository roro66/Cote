<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;

class Account extends Model
{
    use LogsActivity;
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'person_id',
        'balance',
        'notes',
    'is_enabled',
    'is_fondeo',
    'is_protected',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_enabled' => 'boolean',
    'is_fondeo' => 'boolean',
    'is_protected' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relaciones
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function transactionsFrom(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_account_id');
    }

    public function transactionsTo(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_account_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeTreasury($query)
    {
        return $query->where('type', 'treasury');
    }

    public function scopePerson($query)
    {
        return $query->where('type', 'person');
    }

    public function scopeFondeo($query)
    {
        return $query->where('is_fondeo', true);
    }

    // Accessors
    public function getTypeSpanishAttribute(): string
    {
        return match($this->type) {
            'treasury' => 'Tesorería',
            'person' => 'Personal',
            default => 'Desconocido'
        };
    }

    public function getBalanceFormattedAttribute(): string
    {
        return '$' . number_format($this->balance, 0, ',', '.') . ' CLP';
    }

    // Métodos de negocio
    public function updateBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function canDebit(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
