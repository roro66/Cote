<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Crear una nueva transacción
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Generar número de transacción único
            $data['transaction_number'] = $this->generateTransactionNumber();
            
            // Crear la transacción
            $transaction = Transaction::create($data);
            
            Log::info('Transacción creada', [
                'transaction_id' => $transaction->id,
                'number' => $transaction->transaction_number,
                'amount' => $transaction->amount
            ]);
            
            return $transaction;
        });
    }
    
    /**
     * Aprobar una transacción y actualizar saldos
     */
    public function approveTransaction(Transaction $transaction, int $approvedBy): bool
    {
        if ($transaction->status !== 'pending') {
            throw new \Exception('Solo se pueden aprobar transacciones pendientes.');
        }
        
        return DB::transaction(function () use ($transaction, $approvedBy) {
            // Actualizar estado de la transacción
            $transaction->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);
            
            // Actualizar saldos de cuentas
            $this->updateAccountBalances($transaction);
            
            Log::info('Transacción aprobada', [
                'transaction_id' => $transaction->id,
                'approved_by' => $approvedBy
            ]);
            
            return true;
        });
    }
    
    /**
     * Rechazar una transacción
     */
    public function rejectTransaction(Transaction $transaction, int $rejectedBy, string $reason = null): bool
    {
        if ($transaction->status !== 'pending') {
            throw new \Exception('Solo se pueden rechazar transacciones pendientes.');
        }
        
        $transaction->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'notes' => $transaction->notes . ($reason ? "\n\nMotivo de rechazo: " . $reason : ''),
        ]);
        
        Log::info('Transacción rechazada', [
            'transaction_id' => $transaction->id,
            'rejected_by' => $rejectedBy,
            'reason' => $reason
        ]);
        
        return true;
    }
    
    /**
     * Actualizar saldos de cuentas después de aprobar transacción
     */
    private function updateAccountBalances(Transaction $transaction): void
    {
        if ($transaction->from_account_id) {
            $fromAccount = Account::find($transaction->from_account_id);
            if ($fromAccount) {
                $fromAccount->decrement('balance', $transaction->amount);
            }
        }
        
        if ($transaction->to_account_id) {
            $toAccount = Account::find($transaction->to_account_id);
            if ($toAccount) {
                $toAccount->increment('balance', $transaction->amount);
            }
        }
    }
    
    /**
     * Generar número único de transacción
     */
    private function generateTransactionNumber(): string
    {
        $year = date('Y');
        $lastTransaction = Transaction::where('transaction_number', 'like', "TXN-{$year}-%")
            ->orderBy('transaction_number', 'desc')
            ->first();
        
        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->transaction_number, -3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('TXN-%s-%03d', $year, $newNumber);
    }
    
    /**
     * Obtener resumen de transacciones por período
     */
    public function getTransactionSummary($startDate = null, $endDate = null): array
    {
        $query = Transaction::query();
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        return [
            'total_transactions' => $query->count(),
            'pending_transactions' => $query->where('status', 'pending')->count(),
            'approved_transactions' => $query->where('status', 'approved')->count(),
            'total_amount' => $query->where('status', 'approved')->sum('amount'),
            'pending_amount' => $query->where('status', 'pending')->sum('amount'),
        ];
    }
    
    /**
     * Verificar si una cuenta tiene fondos suficientes
     */
    public function hasSufficientFunds(int $accountId, float $amount): bool
    {
        $account = Account::find($accountId);
        return $account && $account->balance >= $amount;
    }
}
