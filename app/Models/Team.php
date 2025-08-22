<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Team extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'leader_id',
        'is_enabled'
    ];

    protected $casts = [
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
    public function leader(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'leader_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->withPivot('role_in_team', 'is_active')->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->wherePivot('is_active', true);
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
