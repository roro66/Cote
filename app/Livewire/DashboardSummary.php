<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Expense;
use App\Services\TransactionService;
use App\Services\ExpenseService;
use Livewire\Component;

class DashboardSummary extends Component
{
    public $totalAccounts;
    public $totalBalance;
    public $pendingTransactions;
    public $pendingExpenses;
    public $recentTransactions;
    public $recentExpenses;
    public $accountsByType;
    public $pendingTransactionsAmount;
    public $pendingExpensesAmount;
    public $negativeAccounts;

    public function mount()
    {
        $this->loadSummaryData();
    }

    public function loadSummaryData()
    {
        // Cuentas
        $this->totalAccounts = Account::where('is_enabled', true)->count();
        $this->totalBalance = Account::where('is_enabled', true)->sum('balance');
        $this->accountsByType = Account::where('is_enabled', true)
            ->selectRaw('type, COUNT(*) as count, SUM(balance) as total_balance')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        // Transacciones
        $this->pendingTransactions = Transaction::where('status', 'pending')->count();
        $this->recentTransactions = Transaction::with(['fromAccount', 'toAccount', 'creator'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Gastos
        $this->pendingExpenses = Expense::whereIn('status', ['submitted', 'reviewed'])->count();

        $this->pendingTransactionsAmount = (float) Transaction::where('status', 'pending')->sum('amount');
        $this->pendingExpensesAmount = (float) Expense::whereIn('status', ['submitted', 'reviewed'])->sum('total_amount');
        $this->negativeAccounts = Account::with('person')
            ->where('is_enabled', true)
            ->where('balance', '<', 0)
            ->orderBy('balance')
            ->take(5)
            ->get();
        $this->recentExpenses = Expense::with(['account', 'submitter'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    public function approveTransaction($transactionId, TransactionService $transactionService)
    {
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);

        try {
            $transactionService->approveTransaction(Transaction::findOrFail($transactionId), auth()->id());
            session()->flash('message', 'Transacción aprobada correctamente.');
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Error inesperado al aprobar la transacción.');
        }

        $this->loadSummaryData();
    }

    public function rejectTransaction($transactionId, TransactionService $transactionService)
    {
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);

        try {
            $transactionService->rejectTransaction(Transaction::findOrFail($transactionId), auth()->id(), 'Rechazada desde dashboard');
            session()->flash('message', 'Transacción rechazada.');
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Error inesperado al rechazar la transacción.');
        }

        $this->loadSummaryData();
    }

    public function render()
    {
        return view('livewire.dashboard-summary');
    }
}
