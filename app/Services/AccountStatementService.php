<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class AccountStatementService
{
    /**
     * Movimientos que alteraron el saldo de la cuenta, del más reciente al más antiguo,
     * con el saldo resultante después de cada uno.
     *
     * Con período, solo trae los movimientos entre $from y $to; el saldo de partida se obtiene
     * restando al saldo actual lo ocurrido después de $to, y opening_balance es el saldo al
     * empezar el período.
     *
     * @return array{movements: Collection, total_in: float, total_out: float, opening_balance: float}
     */
    public function build(Account $account, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $movements = $this->transactions($account, $from, $to)
            ->concat($this->expenses($account, $from, $to))
            ->concat($this->adjustments($account, $from, $to))
            // Mismo segundo: el registro creado después va primero
            ->sort(fn (array $a, array $b) => [$b['date']?->getTimestamp() ?? 0, $b['seq']] <=> [$a['date']?->getTimestamp() ?? 0, $a['seq']])
            ->values();

        $running = (float) $account->balance - ($to ? $this->netBetween($account, $to, null) : 0.0);
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

    /** Saldo que ningún movimiento aprobado explica (saldos antiguos o ediciones directas). */
    public function unexplainedBalance(Account $account): float
    {
        return round((float) $account->balance - $this->netBetween($account, null, null), 2);
    }

    /** @return array{in: float, out: float} */
    public function totals(Account $account): array
    {
        $in = (float) $this->approvedTransactions()->where('to_account_id', $account->id)->sum('amount');
        $out = (float) $this->approvedTransactions()->where('from_account_id', $account->id)->sum('amount')
            + (float) $this->approvedExpenses()->where('account_id', $account->id)->sum('total_amount');
        foreach ($this->adjustmentLogs($account, null, null) as $log) {
            $delta = (float) $log->properties['new'] - (float) $log->properties['old'];
            $delta > 0 ? $in += $delta : $out -= $delta;
        }

        return ['in' => $in, 'out' => $out];
    }

    /** Cambio neto del saldo por movimientos aprobados después de $after (exclusive) y hasta $until. */
    private function netBetween(Account $account, ?CarbonInterface $after, ?CarbonInterface $until): float
    {
        $range = function ($query, string $column) use ($after, $until) {
            $date = "COALESCE({$column}, updated_at)";

            return $query
                ->when($after, fn ($q) => $q->whereRaw("{$date} > ?", [$after]))
                ->when($until, fn ($q) => $q->whereRaw("{$date} <= ?", [$until]));
        };

        $in = (float) $range($this->approvedTransactions()->where('to_account_id', $account->id), 'approved_at')->sum('amount');
        $out = (float) $range($this->approvedTransactions()->where('from_account_id', $account->id), 'approved_at')->sum('amount');
        $expenses = (float) $range($this->approvedExpenses()->where('account_id', $account->id), 'reviewed_at')->sum('total_amount');
        $adjustments = $this->adjustmentLogs($account, $after, $until)
            ->sum(fn (Activity $log) => (float) $log->properties['new'] - (float) $log->properties['old']);

        return $in - $out - $expenses + $adjustments;
    }

    private function approvedTransactions()
    {
        return Transaction::query()->where('status', 'approved');
    }

    private function approvedExpenses()
    {
        return Expense::query()->where('status', 'approved');
    }

    private function inRange($query, string $column, ?CarbonInterface $from, ?CarbonInterface $to)
    {
        $date = "COALESCE({$column}, updated_at)";

        return $query
            ->when($from, fn ($q) => $q->whereRaw("{$date} >= ?", [$from]))
            ->when($to, fn ($q) => $q->whereRaw("{$date} <= ?", [$to]));
    }

    private function transactions(Account $account, ?CarbonInterface $from, ?CarbonInterface $to): Collection
    {
        return $this->inRange(Transaction::with(['fromAccount.person', 'toAccount.person']), 'approved_at', $from, $to)
            ->where('status', 'approved')
            ->where(fn ($q) => $q->where('from_account_id', $account->id)->orWhere('to_account_id', $account->id))
            ->get()
            ->map(function (Transaction $tx) use ($account) {
                $incoming = (int) $tx->to_account_id === (int) $account->id;
                $counterpart = $incoming ? $tx->fromAccount : $tx->toAccount;

                return [
                    'date' => $tx->approved_at ?? $tx->updated_at,
                    'seq' => $tx->id,
                    'kind' => $tx->type_spanish,
                    'reference' => $tx->transaction_number,
                    'url' => route('transactions.show', $tx),
                    'detail' => $tx->type === 'deposit'
                        ? $tx->description
                        : trim(($incoming ? 'Desde ' : 'Hacia ') . ($counterpart?->person?->full_name ?? $counterpart?->name ?? 'sin cuenta') . ' — ' . $tx->description),
                    'in' => $incoming ? (float) $tx->amount : 0.0,
                    'out' => $incoming ? 0.0 : (float) $tx->amount,
                ];
            });
    }

    private function expenses(Account $account, ?CarbonInterface $from, ?CarbonInterface $to): Collection
    {
        return $this->inRange(Expense::where('account_id', $account->id), 'reviewed_at', $from, $to)
            ->where('status', 'approved')
            ->get()
            ->map(fn (Expense $expense) => [
                'date' => $expense->reviewed_at ?? $expense->updated_at,
                'seq' => $expense->id,
                'kind' => 'Rendición',
                'reference' => $expense->expense_number,
                'url' => route('expenses.show', $expense),
                'detail' => $expense->description,
                'in' => 0.0,
                'out' => (float) $expense->total_amount,
            ]);
    }

    private function adjustmentLogs(Account $account, ?CarbonInterface $after, ?CarbonInterface $until, bool $inclusive = false): Collection
    {
        return Activity::with('causer')
            ->where('log_name', 'balance_adjustment')
            ->where('subject_type', $account->getMorphClass())
            ->where('subject_id', $account->id)
            ->when($after, fn ($q) => $q->where('created_at', $inclusive ? '>=' : '>', $after))
            ->when($until, fn ($q) => $q->where('created_at', '<=', $until))
            ->get();
    }

    private function adjustments(Account $account, ?CarbonInterface $from, ?CarbonInterface $to): Collection
    {
        return $this->adjustmentLogs($account, $from, $to, true)
            ->map(function (Activity $log) {
                $delta = (float) $log->properties['new'] - (float) $log->properties['old'];

                return [
                    'date' => $log->created_at,
                    'seq' => $log->id,
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
