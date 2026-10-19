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
    'is_protected',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_enabled' => 'boolean',
    'is_protected' => 'boolean',
    ];

    // Maximum storable balance according to the DB column decimal(15,2)
    // Decimal(15,2) allows up to 13 integer digits (10^13 - 1) and 2 decimals.
    public const MAX_BALANCE = 9999999999999.99; // 13 nines before the decimal

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
    /** Mismo criterio que AccountPolicy::view, para listados. */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasAnyRole(['boss', 'treasurer'])) {
            return $query;
        }

        return $query->where('accounts.person_id', $user->person_id ?? 0);
    }

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

    /**
     * Lectura del saldo de un trabajador: positivo = tiene dinero de la empresa por rendir o devolver;
     * negativo = gastó de su bolsillo y la empresa le debe.
     *
     * @return array{label: string, class: string}
     */
    public function getBalanceStatusAttribute(): array
    {
        if ($this->type === 'treasury') {
            return ['label' => 'Disponible', 'class' => 'bg-primary'];
        }

        return match (true) {
            (float) $this->balance > 0 => ['label' => 'Por rendir', 'class' => 'bg-warning text-dark'],
            (float) $this->balance < 0 => ['label' => 'Se le debe', 'class' => 'bg-danger'],
            default => ['label' => 'Al día', 'class' => 'bg-success'],
        };
    }

    public function getBalanceAbsFormattedAttribute(): string
    {
        return '$' . number_format(abs((float) $this->balance), 0, ',', '.');
    }
}
