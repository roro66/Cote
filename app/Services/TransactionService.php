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
        $maxAttempts = 5;
        $attempt = 0;
        do {
            $attempt++;
            try {
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
            } catch (\Throwable $e) {
                // Detectar unique_violation (Postgres 23505) en distintos tipos de excepción
                $code = (string) $e->getCode();
                $message = (string) $e->getMessage();
                $isUniqueViolation = ($code === '23505') || str_contains($message, '23505') || str_contains($message, 'unique');
                if ($isUniqueViolation && $attempt < $maxAttempts) {
                    // Reintentar: posible colisión de transaction_number por concurrencia
                    continue;
                }
                throw $e;
            }
        } while ($attempt < $maxAttempts);

        throw new \Exception('No se pudo generar un número de transacción único después de varios intentos. Intenta de nuevo.');
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

        // Intent: find the next unused sequential number in the format TXN-YYYY-###
        // We attempt to find a free slot by checking the DB; this reduces collisions under concurrency.
        $lastTransactionNumber = Transaction::where('transaction_number', 'like', "TXN-{$year}-%")
            ->orderBy('transaction_number', 'desc')
            ->value('transaction_number');

        $lastNumber = 0;
        if ($lastTransactionNumber) {
            // Extract trailing number (support trailing suffixes if any)
            if (preg_match('/TXN-\d{4}-(\d{1,6})/', $lastTransactionNumber, $m)) {
                $lastNumber = intval($m[1]);
            }
        }

        // Try a window of sequential numbers to find a free one
        $maxProbe = 1000;
        for ($i = 1; $i <= $maxProbe; $i++) {
            $candidate = sprintf('TXN-%s-%03d', $year, $lastNumber + $i);
            $exists = Transaction::where('transaction_number', $candidate)->exists();
            if (!$exists) {
                return $candidate;
            }
        }

        // Fallback: generate a timestamp+random suffix to guarantee uniqueness
        $suffix = substr(sha1(uniqid((string) mt_rand(), true)), 0, 8);
        return sprintf('TXN-%s-%s-%s', $year, time(), $suffix);
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
