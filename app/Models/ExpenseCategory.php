<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'code', 'name', 'description', 'is_enabled'
    ];

    protected $casts = [
        'is_enabled' => 'boolean'
    ];

    public function scopeEnabled($q)
    {
        return $q->where('is_enabled', true);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\ExpenseItem::class, 'expense_category_id');
    }
}
