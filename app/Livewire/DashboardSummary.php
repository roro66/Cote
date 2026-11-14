<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Expense;
use App\Models\PeriodClosing;
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
    public $toRender = 0;

    /** @var list<array{text: string, tone: string, link?: string, action?: string}> */
    public array $todo = [];

    public function mount()
    {
        $this->loadSummaryData();
    }

    /** Lo que el usuario tiene que hacer hoy, en palabras y con un botón para cada cosa. */
    private function buildTodo($user): array
    {
        $money = fn ($value) => '$'.number_format(abs((float) $value), 0, ',', '.');
        $todo = [];

        if (! $user->hasAnyRole(['boss', 'treasurer'])) {
            $account = Account::where('type', 'person')->where('person_id', $user->person_id ?? 0)->where('is_enabled', true)->first();
            $returned = Expense::visibleTo($user)->where('status', 'rejected')->where('is_enabled', true)->count();
            if ($returned > 0) {
                $todo[] = ['text' => $returned === 1 ? 'Te devolvieron 1 rendición para corregir.' : "Te devolvieron {$returned} rendiciones para corregir.", 'tone' => 'danger', 'link' => route('expenses.index'), 'action' => 'Ver y corregir'];
            }
            if ($account && (float) $account->balance > 0) {
                $todo[] = ['text' => 'Tienes '.$money($account->balance).' de la empresa. Rinde tus gastos con sus boletas o devuelve lo que te sobre.', 'tone' => 'warning', 'link' => route('expenses.create'), 'action' => 'Nueva rendición'];
            } elseif ($account && (float) $account->balance < 0) {
                $todo[] = ['text' => 'Te debemos '.$money($account->balance).'. El jefe o el tesorero te lo pagarán.', 'tone' => 'info'];
            }

            return $todo;
        }

        $toReview = Expense::where('status', 'submitted')->where('is_enabled', true)->count();
        if ($toReview > 0) {
            $todo[] = ['text' => $toReview === 1 ? 'Hay 1 rendición por revisar.' : "Hay {$toReview} rendiciones por revisar.", 'tone' => 'warning', 'link' => route('approvals.review'), 'action' => 'Revisar'];
        }

        $pendingTx = Transaction::where('status', 'pending')->where('is_enabled', true)->count();
        if ($pendingTx > 0) {
            $todo[] = ['text' => $pendingTx === 1 ? 'Hay 1 movimiento antiguo esperando aprobación.' : "Hay {$pendingTx} movimientos antiguos esperando aprobación.", 'tone' => 'warning', 'link' => route('approvals.index'), 'action' => 'Ver'];
        }

        $previous = now('America/Santiago')->subMonthNoOverflow()->startOfMonth();
        $last = PeriodClosing::orderByDesc('period')->first();
        $next = $last ? $last->period->copy()->addMonthNoOverflow()->startOfMonth() : $previous;
        if ($next->lte($previous)) {
            $todo[] = ['text' => 'Falta cerrar '.$next->translatedFormat('F Y').'.', 'tone' => 'info', 'link' => route('closings.index', ['month' => $next->format('Y-m')]), 'action' => 'Cerrar mes'];
        }

        $limit = now()->subDays(30);
        $holding = Account::with('person')->where('type', 'person')->where('is_enabled', true)->where('balance', '>', 0)->get();
        foreach ($holding as $account) {
            $lastMovement = collect([
                Transaction::where('status', 'approved')->where(fn ($q) => $q->where('from_account_id', $account->id)->orWhere('to_account_id', $account->id))->max('approved_at'),
                Expense::where('account_id', $account->id)->where('status', 'approved')->max('reviewed_at'),
            ])->filter()->max();
            if ($lastMovement && \Illuminate\Support\Carbon::parse($lastMovement)->lt($limit)) {
                $days = (int) \Illuminate\Support\Carbon::parse($lastMovement)->diffInDays(now());
                $todo[] = ['text' => ($account->person?->full_name ?? $account->name)." lleva {$days} días con ".$money($account->balance).' sin rendir.', 'tone' => 'secondary', 'link' => route('accounts.movements', $account), 'action' => 'Ver cartola'];
            }
        }

        return $todo;
    }

    public function loadSummaryData()
    {
        $user = auth()->user();
        $this->todo = $this->buildTodo($user);

        $this->recentExpenses = Expense::with(['account', 'submitter'])
            ->visibleTo($user)
            ->where('is_enabled', true)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Las propiedades públicas viajan en el HTML de Livewire: no cargar datos globales sin rol
        if (! $user->hasAnyRole(['boss', 'treasurer'])) {
            $this->recentTransactions = collect();
            $this->accountsByType = collect();
            $this->negativeAccounts = collect();
            return;
        }

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
        $pendingExpenses = Expense::whereIn('status', ['submitted', 'reviewed'])->where('is_enabled', true);
        $this->pendingExpenses = (clone $pendingExpenses)->count();

        $this->pendingTransactionsAmount = (float) Transaction::where('status', 'pending')->sum('amount');
        $this->pendingExpensesAmount = (float) $pendingExpenses->sum('total_amount');
        $this->toRender = (float) Account::where('type', 'person')->where('is_enabled', true)->where('balance', '>', 0)->sum('balance');
        $this->negativeAccounts = Account::with('person')
            ->where('is_enabled', true)
            ->where('balance', '<', 0)
            ->orderBy('balance')
            ->take(5)
            ->get();
    }

    public function approveTransaction($transactionId, TransactionService $transactionService)
    {
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);

        try {
            $transactionService->approveTransaction(Transaction::findOrFail($transactionId), auth()->id());
            $this->dispatch('cote-toast', type: 'success', message: 'Transacción aprobada correctamente.');
        } catch (\DomainException $e) {
            $this->dispatch('cote-toast', type: 'error', message: $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('cote-toast', type: 'error', message: 'Error inesperado al aprobar la transacción.');
        }

        $this->loadSummaryData();
    }

    public function rejectTransaction($transactionId, TransactionService $transactionService)
    {
        abort_unless(auth()->user()?->hasAnyRole(['boss', 'treasurer']), 403);

        try {
            $transactionService->rejectTransaction(Transaction::findOrFail($transactionId), auth()->id(), 'Rechazada desde dashboard');
            $this->dispatch('cote-toast', type: 'success', message: 'Transacción rechazada.');
        } catch (\DomainException $e) {
            $this->dispatch('cote-toast', type: 'error', message: $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('cote-toast', type: 'error', message: 'Error inesperado al rechazar la transacción.');
        }

        $this->loadSummaryData();
    }

    public function render()
    {
        return view('livewire.dashboard-summary');
    }
}
