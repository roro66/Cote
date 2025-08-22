<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseService
{
    /**
     * Crear una nueva rendición de gastos
     */
    public function createExpense(array $data, array $items = []): Expense
    {
        return DB::transaction(function () use ($data, $items) {
            // Generar número de rendición único
            $data['expense_number'] = $this->generateExpenseNumber();
            
            // Crear la rendición
            $expense = Expense::create($data);
            
            // Crear items si se proporcionan
            if (!empty($items)) {
                foreach ($items as $item) {
                    $item['expense_id'] = $expense->id;
                    ExpenseItem::create($item);
                }
                
                // Actualizar total
                $expense->update([
                    'total_amount' => $expense->items()->sum('amount')
                ]);
            }
            
            Log::info('Rendición creada', [
                'expense_id' => $expense->id,
                'number' => $expense->expense_number,
                'items_count' => count($items)
            ]);
            
            return $expense;
        });
    }
    
    /**
     * Enviar rendición para revisión
     */
    public function submitExpense(Expense $expense): bool
    {
        if ($expense->status !== 'draft') {
            throw new \Exception('Solo se pueden enviar rendiciones en borrador.');
        }
        
        $expense->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        
        Log::info('Rendición enviada para revisión', [
            'expense_id' => $expense->id
        ]);
        
        return true;
    }
    
    /**
     * Aprobar una rendición
     */
    public function approveExpense(Expense $expense, int $reviewedBy): bool
    {
        if (!in_array($expense->status, ['submitted', 'reviewed'])) {
            throw new \Exception('Solo se pueden aprobar rendiciones enviadas o en revisión.');
        }
        
        $expense->update([
            'status' => 'approved',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);
        
        Log::info('Rendición aprobada', [
            'expense_id' => $expense->id,
            'reviewed_by' => $reviewedBy
        ]);
        
        return true;
    }
    
    /**
     * Rechazar una rendición
     */
    public function rejectExpense(Expense $expense, int $reviewedBy, string $reason): bool
    {
        if (!in_array($expense->status, ['submitted', 'reviewed'])) {
            throw new \Exception('Solo se pueden rechazar rendiciones enviadas o en revisión.');
        }
        
        $expense->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
        
        Log::info('Rendición rechazada', [
            'expense_id' => $expense->id,
            'reviewed_by' => $reviewedBy,
            'reason' => $reason
        ]);
        
        return true;
    }
    
    /**
     * Agregar item a una rendición
     */
    public function addExpenseItem(Expense $expense, array $itemData): ExpenseItem
    {
        if ($expense->status !== 'draft') {
            throw new \Exception('Solo se pueden agregar items a rendiciones en borrador.');
        }
        
        $itemData['expense_id'] = $expense->id;
        $item = ExpenseItem::create($itemData);
        
        // Actualizar total de la rendición
        $expense->update([
            'total_amount' => $expense->items()->sum('amount')
        ]);
        
        return $item;
    }
    
    /**
     * Generar número único de rendición
     */
    private function generateExpenseNumber(): string
    {
        $year = date('Y');
        $lastExpense = Expense::where('expense_number', 'like', "RND-{$year}-%")
            ->orderBy('expense_number', 'desc')
            ->first();
        
        if ($lastExpense) {
            $lastNumber = intval(substr($lastExpense->expense_number, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('RND-%s-%03d', $year, $newNumber);
    }
    
    /**
     * Obtener resumen de gastos por período
     */
    public function getExpenseSummary($startDate = null, $endDate = null): array
    {
        $query = Expense::query();
        
        if ($startDate) {
            $query->whereDate('expense_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('expense_date', '<=', $endDate);
        }
        
        return [
            'total_expenses' => $query->count(),
            'draft_expenses' => $query->where('status', 'draft')->count(),
            'submitted_expenses' => $query->where('status', 'submitted')->count(),
            'approved_expenses' => $query->where('status', 'approved')->count(),
            'rejected_expenses' => $query->where('status', 'rejected')->count(),
            'total_amount' => $query->where('status', 'approved')->sum('total_amount'),
            'pending_amount' => $query->whereIn('status', ['submitted', 'reviewed'])->sum('total_amount'),
        ];
    }
}
