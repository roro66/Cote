<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends Model
{
    use HasFactory;

    /** Código de «Viáticos»: no exige proveedor, N° ni boleta individual. */
    public const CODE_VIATICOS = 'VIA';

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

    /** Viático u otra categoría que se rinde sin boleta de un comercio concreto. */
    public function allowsBlankVendor(): bool
    {
        return strtoupper(trim((string) $this->code)) === self::CODE_VIATICOS;
    }

    public static function allowsBlankVendorId(int|string|null $categoryId): bool
    {
        if ($categoryId === null || $categoryId === '') {
            return false;
        }

        return in_array((int) $categoryId, static::blankVendorCategoryIds(), true);
    }

    /** @return list<int> */
    public static function blankVendorCategoryIds(): array
    {
        static $ids = null;

        return $ids ??= static::query()
            ->whereRaw('UPPER(TRIM(code)) = ?', [self::CODE_VIATICOS])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
