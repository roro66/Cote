<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HasSequentialNumber
{
    /**
     * Boot the trait
     */
    protected static function bootHasSequentialNumber()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getSequentialNumberField()})) {
                $model->{$model->getSequentialNumberField()} = $model->generateSequentialNumber();
            }
        });
    }

    /**
     * Get the field name for the sequential number
     */
    abstract public function getSequentialNumberField(): string;

    /**
     * Get the prefix for the sequential number
     */
    abstract public function getSequentialNumberPrefix(): string;

    /**
     * Generate a sequential number
     */
    public function generateSequentialNumber(): string
    {
        $field = $this->getSequentialNumberField();
        $prefix = $this->getSequentialNumberPrefix();
        $year = date('Y');

        // Obtener el máximo numérico del sufijo para evitar problemas de orden lexicográfico
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            $max = static::where($field, 'like', "{$prefix}-{$year}-%")
                ->select(DB::raw("MAX(CAST(split_part({$field}, '-', 3) AS INTEGER)) AS max_suffix"))
                ->value('max_suffix');
        } elseif ($driver === 'mysql') {
            $max = static::where($field, 'like', "{$prefix}-{$year}-%")
                ->select(DB::raw("MAX(CAST(SUBSTRING_INDEX({$field}, '-', -1) AS UNSIGNED)) AS max_suffix"))
                ->value('max_suffix');
        } else {
            // Fallback: obtener todos y calcular en PHP (para sqlite u otros)
            $max = static::where($field, 'like', "{$prefix}-{$year}-%")
                ->pluck($field)
                ->map(function ($val) {
                    $parts = explode('-', $val);
                    $suffix = end($parts);
                    return ctype_digit($suffix) ? intval($suffix) : 0;
                })
                ->max();
        }

        $lastNumber = intval($max ?? 0);

        $newNumber = $lastNumber + 1;

        // Use 6-digit padding to preserve lexicographic ordering and avoid early collisions
        return sprintf('%s-%s-%06d', $prefix, $year, $newNumber);
    }
}
