<?php

namespace App\Traits;

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
        
        $lastRecord = static::where($field, 'like', "{$prefix}-{$year}-%")
            ->orderBy($field, 'desc')
            ->first();
        
        if ($lastRecord) {
            $lastNumber = intval(substr($lastRecord->{$field}, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('%s-%s-%03d', $prefix, $year, $newNumber);
    }
}
