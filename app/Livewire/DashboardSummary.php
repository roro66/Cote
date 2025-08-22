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
        $this->recentExpenses = Expense::with(['account', 'submitter'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    public function approveTransaction($transactionId)
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);
            $transactionService = new TransactionService();
            
            $transactionService->approveTransaction($transaction, auth()->id());
            
            session()->flash('message', 'Transacción aprobada correctamente.');
            $this->loadSummaryData(); // Refrescar datos
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al aprobar transacción: ' . $e->getMessage());
        }
    }

    public function rejectTransaction($transactionId)
    {
        try {
            $transaction = Transaction::findOrFail($transactionId);
            $transactionService = new TransactionService();
            
            $transactionService->rejectTransaction($transaction, auth()->id(), 'Rechazada desde dashboard');
            
            session()->flash('message', 'Transacción rechazada.');
            $this->loadSummaryData(); // Refrescar datos
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al rechazar transacción: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.dashboard-summary');
    }
}
