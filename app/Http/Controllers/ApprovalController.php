<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Expense;
use App\Services\TransactionService;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    private TransactionService $transactionService;
    private ExpenseService $expenseService;

    public function __construct(TransactionService $transactionService, ExpenseService $expenseService)
    {
        $this->transactionService = $transactionService;
        $this->expenseService = $expenseService;
    }

    /**
     * Mostrar panel de aprobaciones
     */
    public function index(): View
    {
        return view('approvals.index');
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

            $this->transactionService->approveTransaction($transaction, Auth::id());

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
                Auth::id(),
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
    public function approveExpense(Request $request, Expense $expense)
    {
        try {
            if ($expense->status !== 'submitted') {
                $payload = [
                    'success' => false,
                    'message' => 'Solo se pueden aprobar rendiciones enviadas.'
                ];
                if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                    return response()->json($payload, 400);
                }
                return redirect()->back()->with('toastr', ['type' => 'error', 'message' => $payload['message']]);
            }

            $this->expenseService->approveExpense($expense, Auth::id());

            $payload = [
                'success' => true,
                'message' => 'Rendición aprobada correctamente.'
            ];

            if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                return response()->json($payload);
            }

            return redirect()->route('expenses.show', $expense)
                ->with('toastr', ['type' => 'success', 'message' => $payload['message']]);
        } catch (\Exception $e) {
            $message = 'Error al aprobar la rendición: ' . $e->getMessage();
            if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 500);
            }
            return redirect()->back()->with('toastr', ['type' => 'error', 'message' => $message]);
        }
    }

    /**
     * Rechazar una rendición de gastos
     */
    public function rejectExpense(Request $request, Expense $expense)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            if ($expense->status !== 'submitted') {
                $payload = [
                    'success' => false,
                    'message' => 'Solo se pueden rechazar rendiciones enviadas.'
                ];
                if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                    return response()->json($payload, 400);
                }
                return redirect()->back()->with('toastr', ['type' => 'error', 'message' => $payload['message']]);
            }

            $expense->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => $request->rejection_reason
            ]);

            $payload = [
                'success' => true,
                'message' => 'Rendición rechazada correctamente.'
            ];
            if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                return response()->json($payload);
            }
            return redirect()->route('expenses.show', $expense)
                ->with('toastr', ['type' => 'success', 'message' => $payload['message']]);
        } catch (\Exception $e) {
            $message = 'Error al rechazar la rendición: ' . $e->getMessage();
            if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 500);
            }
            return redirect()->back()->with('toastr', ['type' => 'error', 'message' => $message]);
        }
    }
}
