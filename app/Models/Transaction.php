<?php

namespace App\Models;

use App\Traits\HasSequentialNumber;
use App\Traits\HasDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaction extends Model
{
    use LogsActivity, HasSequentialNumber, HasDocumentNumber;

    protected $fillable = [
        'transaction_number',
        'type',
        'from_account_id',
        'to_account_id',
        'amount',
        'description',
        'notes',
        'created_by',
        'approved_by',
        'status',
        'approved_at',
        'is_enabled'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'is_enabled' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relaciones
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Alias para compatibilidad
    public function creator(): BelongsTo
    {
        return $this->createdBy();
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Accessors
    public function getTypeSpanishAttribute(): string
    {
        return match ($this->type) {
            'transfer' => 'Transferencia',
            default => 'Desconocido'
        };
    }

    public function getStatusSpanishAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'completed' => 'Completado',
            default => 'Desconocido'
        };
    }

    public function getAmountFormattedAttribute(): string
    {
        return '$' . number_format($this->amount, 0, ',', '.') . ' CLP';
    }

    // Boot method para generar número de transacción automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = $transaction->generateSequentialNumber();
            }
        });
    }

    /**
     * Get the field name for the sequential number
     */
    public function getSequentialNumberField(): string
    {
        return 'transaction_number';
    }

    /**
     * Get the prefix for the sequential number
     */
    public function getSequentialNumberPrefix(): string
    {
        return 'TXN';
    }
}
