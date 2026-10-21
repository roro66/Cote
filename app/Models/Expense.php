<?php

namespace App\Models;

use App\Traits\HasSequentialNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Expense extends Model
{
    use LogsActivity, HasFactory, HasSequentialNumber;

    protected $fillable = [
        'expense_number',
        'account_id',
        'submitted_by',
        'total_amount',
        'description',
        'expense_date',
        'status',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'rejection_reason',
        'is_enabled'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'expense_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'is_enabled' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // Relaciones
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class);
    }

    // Alias para compatibilidad
    public function expenseItems(): HasMany
    {
        return $this->items();
    }

    public function submitter(): BelongsTo
    {
        return $this->submittedBy();
    }

    // Scopes
    /** Mismo criterio que ExpensePolicy::view, para listados. */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasAnyRole(['boss', 'treasurer'])) {
            return $query;
        }

        return $query->where('expenses.submitted_by', $user->person_id ?? 0);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'submitted');
    }

    // Accessors
    public function getStatusSpanishAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Borrador',
            'submitted' => 'Enviado',
            'reviewed' => 'Revisado',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            default => 'Desconocido'
        };
    }

    public function getTotalAmountFormattedAttribute(): string
    {
        return '$' . number_format($this->total_amount, 0, ',', '.') . ' CLP';
    }

    // Boot method para generar número de rendición automáticamente
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($expense) {
            if (empty($expense->expense_number)) {
                $expense->expense_number = $expense->generateSequentialNumber();
            }
        });
    }

    /**
     * Get the field name for the sequential number
     */
    public function getSequentialNumberField(): string
    {
        return 'expense_number';
    }

    /**
     * Get the prefix for the sequential number
     */
    public function getSequentialNumberPrefix(): string
    {
        return 'RND';
    }

    // Métodos de negocio (aprobar/rechazar va por ExpenseService, que también mueve el saldo)

    /** Una rendición aprobada ya descontó el saldo: no se edita ni se elimina. */
    public function isEditable(): bool
    {
        return $this->is_enabled && in_array($this->status, ['draft', 'submitted', 'rejected'], true);
    }
}
