<?php

namespace App\Models;

use App\Rules\ValidChileanRut;
use App\Helpers\RutHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Person extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'first_name',
        'last_name', 
        'rut',
        'email',
        'phone',
        'bank_name',
        'account_type',
        'account_number',
        'address',
        'role_type',
        'is_enabled'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Validation rules for Person model
     */
    public static function validationRules()
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'rut' => ['required', 'string', 'unique:people,rut', new ValidChileanRut()],
            'email' => 'required|email|unique:people,email',
            'phone' => 'nullable|string|max:20',
            'role_type' => 'required|in:tesorero,trabajador',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Clean and format RUT before saving
        static::saving(function ($person) {
            if ($person->rut) {
                $person->rut = RutHelper::clean($person->rut);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relaciones
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('role_in_team', 'is_active')->withTimestamps();
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function submittedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'submitted_by');
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    // Mutators
    public function setRutAttribute($value)
    {
        // Normalizar el RUT removiendo puntos antes de guardarlo
        $this->attributes['rut'] = RutHelper::clean($value);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getRutFormattedAttribute(): string
    {
        // Formatear el RUT con puntos para mostrar
        return RutHelper::format($this->rut);
    }

    public function getRoleTypeSpanishAttribute(): string
    {
        return match($this->role_type) {
            'tesorero' => 'Tesorero',
            'trabajador' => 'Trabajador',
            default => 'Sin Rol'
        };
    }
}
