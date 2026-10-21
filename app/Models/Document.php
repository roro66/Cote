<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Document extends Model
{
    use LogsActivity;

    /** Disco privado: los comprobantes solo se sirven vía DocumentController. */
    public const DISK = 'local';

    protected $fillable = [
        'name',
        'file_path',
        'mime_type',
        'file_size',
        'document_type',
        'expense_item_id',
        'uploaded_by',
        'is_enabled'
    ];

    protected $casts = [
        'file_size' => 'integer',
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
    public function expenseItem(): BelongsTo
    {
        return $this->belongsTo(ExpenseItem::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return route('documents.show', $this);
    }

    public function isViewableBy(User $user): bool
    {
        if ($user->hasAnyRole(['boss', 'treasurer']) || (int) $this->uploaded_by === (int) $user->id) {
            return true;
        }

        $submittedBy = $this->expenseItem?->expense?->submitted_by;

        return $submittedBy !== null && $user->person_id !== null && (int) $submittedBy === (int) $user->person_id;
    }

    public function getDocumentTypeSpanishAttribute(): string
    {
        return match($this->document_type) {
            'boleta' => 'Boleta',
            'factura' => 'Factura',
            'guia_despacho' => 'Guía de Despacho',
            'ticket' => 'Ticket',
            'vale' => 'Vale',
            'other' => 'Otro',
            default => 'Desconocido'
        };
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
