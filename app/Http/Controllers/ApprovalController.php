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
            $this->transactionService->approveTransaction($transaction, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Transacción aprobada correctamente.'
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al aprobar la transacción.'
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
            $this->transactionService->rejectTransaction($transaction, Auth::id(), $request->rejection_reason);

            return response()->json([
                'success' => true,
                'message' => 'Transacción rechazada correctamente.'
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al rechazar la transacción.'
            ], 500);
        }
    }

    /**
     * Aprobar una rendición de gastos
     */
    public function approveExpense(Request $request, Expense $expense)
    {
        try {
            $this->expenseService->approveExpense($expense, Auth::id());

            return $this->expenseResponse($request, $expense, true, 'Rendición aprobada correctamente.');
        } catch (\DomainException $e) {
            return $this->expenseResponse($request, $expense, false, $e->getMessage(), 422);
        } catch (\Throwable $e) {
            report($e);

            return $this->expenseResponse($request, $expense, false, 'Error inesperado al aprobar la rendición.', 500);
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
            $this->expenseService->rejectExpense($expense, Auth::id(), $request->rejection_reason);

            return $this->expenseResponse($request, $expense, true, 'Rendición rechazada correctamente.');
        } catch (\DomainException $e) {
            return $this->expenseResponse($request, $expense, false, $e->getMessage(), 422);
        } catch (\Throwable $e) {
            report($e);

            return $this->expenseResponse($request, $expense, false, 'Error inesperado al rechazar la rendición.', 500);
        }
    }

    private function expenseResponse(Request $request, Expense $expense, bool $success, string $message, int $status = 200)
    {
        if ($request->wantsJson() || $request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => $success, 'message' => $message], $status);
        }

        if ($success) {
            return redirect()->route('expenses.show', $expense)
                ->with('toastr', ['type' => 'success', 'message' => $message]);
        }

        return redirect()->back()->with('toastr', ['type' => 'error', 'message' => $message]);
    }
}
