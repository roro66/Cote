<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class AccountStatementService
{
    /**
     * Movimientos que alteraron el saldo de la cuenta, del más reciente al más antiguo,
     * con el saldo resultante después de cada uno.
     *
     * @return array{movements: Collection, total_in: float, total_out: float, opening_balance: float}
     */
    public function build(Account $account): array
    {
        $movements = $this->transactions($account)
            ->concat($this->expenses($account))
            ->concat($this->adjustments($account))
            ->sortByDesc(fn (array $m) => $m['date']?->getTimestamp() ?? 0)
            ->values();

        $running = (float) $account->balance;
        $movements = $movements->map(function (array $m) use (&$running) {
            $m['balance_after'] = $running;
            $running -= $m['in'] - $m['out'];

            return $m;
        });

        return [
            'movements' => $movements,
            'total_in' => (float) $movements->sum('in'),
            'total_out' => (float) $movements->sum('out'),
            // Saldo que los movimientos registrados no explican (saldo inicial o ediciones antiguas)
            'opening_balance' => round($running, 2),
        ];
    }

    private function transactions(Account $account): Collection
    {
        return Transaction::with(['fromAccount.person', 'toAccount.person'])
            ->where('status', 'approved')
            ->where(fn ($q) => $q->where('from_account_id', $account->id)->orWhere('to_account_id', $account->id))
            ->get()
            ->map(function (Transaction $tx) use ($account) {
                $incoming = (int) $tx->to_account_id === (int) $account->id;
                $counterpart = $incoming ? $tx->fromAccount : $tx->toAccount;

                return [
                    'date' => $tx->approved_at ?? $tx->updated_at,
                    'kind' => $tx->type === 'adjustment' ? 'Ajuste' : 'Transferencia',
                    'reference' => $tx->transaction_number,
                    'url' => route('transactions.show', $tx),
                    'detail' => trim(($incoming ? 'Desde ' : 'Hacia ') . ($counterpart?->person?->full_name ?? $counterpart?->name ?? 'sin cuenta') . ' — ' . $tx->description),
                    'in' => $incoming ? (float) $tx->amount : 0.0,
                    'out' => $incoming ? 0.0 : (float) $tx->amount,
                ];
            });
    }

    private function expenses(Account $account): Collection
    {
        return Expense::where('account_id', $account->id)
            ->where('status', 'approved')
            ->get()
            ->map(fn (Expense $expense) => [
                'date' => $expense->reviewed_at ?? $expense->updated_at,
                'kind' => 'Rendición',
                'reference' => $expense->expense_number,
                'url' => route('expenses.show', $expense),
                'detail' => $expense->description,
                'in' => 0.0,
                'out' => (float) $expense->total_amount,
            ]);
    }

    private function adjustments(Account $account): Collection
    {
        return Activity::with('causer')
            ->where('log_name', 'balance_adjustment')
            ->where('subject_type', $account->getMorphClass())
            ->where('subject_id', $account->id)
            ->get()
            ->map(function (Activity $log) {
                $delta = (float) $log->properties['new'] - (float) $log->properties['old'];

                return [
                    'date' => $log->created_at,
                    'kind' => 'Ajuste manual',
                    'reference' => null,
                    'url' => null,
                    'detail' => trim(($log->properties['reason'] ?? '') . ($log->causer ? ' (' . $log->causer->name . ')' : '')),
                    'in' => max($delta, 0.0),
                    'out' => max(-$delta, 0.0),
                ];
            });
    }
}
