<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Expense;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    private TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Mostrar panel de aprobaciones
     */
    public function index(): View
    {
        $pendingTransactions = Transaction::with([
                'fromAccount.person', 
                'toAccount.person', 
                'createdBy'
            ])
            ->where('status', 'pending')
            ->where('is_enabled', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingExpenses = Expense::with(['account.person', 'submittedBy', 'items'])
            ->where('status', 'submitted')
            ->where('is_enabled', true)
            ->orderBy('submitted_at', 'desc')
            ->get();

        return view('approvals.index', compact('pendingTransactions', 'pendingExpenses'));
    }

    /**
     * Aprobar una transacción
     */
    public function approveTransaction(Request $request, Transaction $transaction): JsonResponse
    {
        try {
            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar transacciones pendientes.'
                ], 400);
            }

            // Verificar fondos suficientes antes de aprobar
            if ($transaction->type === 'transfer') {
                if (!$this->transactionService->hasSufficientFunds($transaction->from_account_id, $transaction->amount)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La cuenta origen no tiene fondos suficientes para esta transacción.'
                    ], 400);
                }
            }

            $this->transactionService->approveTransaction($transaction, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Transacción aprobada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la transacción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar una transacción
     */
    public function rejectTransaction(Request $request, Transaction $transaction): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar transacciones pendientes.'
                ], 400);
            }

            $this->transactionService->rejectTransaction(
                $transaction, 
                auth()->id(), 
                $request->rejection_reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Transacción rechazada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la transacción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar una rendición de gastos
     */
    public function approveExpense(Request $request, Expense $expense): JsonResponse
    {
        try {
            if ($expense->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar rendiciones enviadas.'
                ], 400);
            }

            $expense->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()
            ]);

            // TODO: Aquí se implementará el rebajo de deuda automático
            
            return response()->json([
                'success' => true,
                'message' => 'Rendición aprobada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la rendición: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar una rendición de gastos
     */
    public function rejectExpense(Request $request, Expense $expense): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            if ($expense->status !== 'submitted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar rendiciones enviadas.'
                ], 400);
            }

            $expense->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => $request->rejection_reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rendición rechazada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la rendición: ' . $e->getMessage()
            ], 500);
        }
    }
}
