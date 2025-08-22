<?php

namespace App\Traits;

trait HasDocumentNumber
{
    /**
     * Generar número de documento único
     */
    public static function generateDocumentNumber(string $prefix): string
    {
        $year = date('Y');
        $pattern = "{$prefix}-{$year}-%";
        
        $lastDocument = static::where('transaction_number', 'like', $pattern)
            ->orWhere('expense_number', 'like', $pattern)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastDocument) {
            $numberField = property_exists($lastDocument, 'transaction_number') ? 'transaction_number' : 'expense_number';
            $lastNumber = intval(substr($lastDocument->{$numberField}, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('%s-%s-%03d', $prefix, $year, $newNumber);
    }
    
    /**
     * Boot del trait para auto-generar números
     */
    protected static function bootHasDocumentNumber()
    {
        static::creating(function ($model) {
            if (empty($model->transaction_number) && empty($model->expense_number)) {
                $prefix = 'DOC';
                
                // Determinar prefijo según el modelo
                if (str_contains(get_class($model), 'Transaction')) {
                    $prefix = 'TXN';
                    $model->transaction_number = static::generateDocumentNumber($prefix);
                } elseif (str_contains(get_class($model), 'Expense')) {
                    $prefix = 'RND';
                    $model->expense_number = static::generateDocumentNumber($prefix);
                }
            }
        });
    }
}
