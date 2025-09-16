<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'person_id',
        'is_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_enabled'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot model events.
     */
    protected static function booted()
    {
        // Prevent deleting a user that still has historical references.
        static::deleting(function (User $user) {
            // If the user has any important related records, block deletion.
            if ($user->createdTransactions()->exists()
                || $user->approvedTransactions()->exists()
                || $user->reviewedExpenses()->exists()
                || $user->uploadedDocuments()->exists()
            ) {
                throw new \Exception('No se puede eliminar el usuario porque tiene registros históricos (transacciones, aprobaciones, gastos o documentos). Desactiva el usuario en su lugar.');
            }
            return true;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_enabled' => 'boolean',
        ];
    }

    // Relaciones
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function createdTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function approvedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'approved_by');
    }

    public function reviewedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'reviewed_by');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    // Métodos de negocio
    public function isTreasurer(): bool
    {
        return $this->hasRole('treasurer');
    }

    public function isBoss(): bool
    {
        return $this->hasRole('boss');
    }
}
