<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Transaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Resumen mensual de caja chica por cuenta.
 *
 * Cada movimiento cuenta en la fecha en que se aprobó (cuando el saldo cambió). El saldo final
 * del mes se reconstruye desde el saldo actual restando lo ocurrido después; el inicial resta
 * además lo ocurrido dentro del mes.
 */
class PeriodReportService
{
    /**
     * @return array{start: Carbon, end: Carbon, rows: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function build(CarbonInterface $month): array
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end = $start->copy()->addMonth();

        $movements = $this->movementsSince($start)->groupBy('account_id');

        $rows = Account::with('person')
            ->orderByRaw("CASE WHEN type = 'treasury' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(function (Account $account) use ($movements, $start, $end) {
                $own = $movements->get($account->id, collect());
                $after = $own->filter(fn ($m) => $m['at']->gte($end));
                $within = $own->filter(fn ($m) => $m['at']->gte($start) && $m['at']->lt($end));

                $closing = (float) $account->balance - $after->sum('amount');
                $opening = $closing - $within->sum('amount');
                $transfers = $within->where('kind', 'transfer');

                return [
                    'account_id' => $account->id,
                    'name' => $account->person?->full_name ?? $account->name,
                    'type' => $account->type,
                    'opening' => round($opening, 2),
                    'in' => round($transfers->where('amount', '>', 0)->sum('amount'), 2),
                    'out' => round(abs($transfers->where('amount', '<', 0)->sum('amount')), 2),
                    'rendered' => round(abs($within->where('kind', 'expense')->sum('amount')), 2),
                    'adjustments' => round($within->where('kind', 'adjustment')->sum('amount'), 2),
                    'closing' => round($closing, 2),
                    'movements' => $within->count(),
                ];
            })
            ->filter(fn ($row) => $row['type'] === 'treasury' || $row['movements'] > 0 || abs($row['opening']) >= 0.01 || abs($row['closing']) >= 0.01)
            ->values();

        $workers = $rows->where('type', 'person');

        return [
            'start' => $start,
            'end' => $end,
            'rows' => $rows->all(),
            'totals' => [
                'treasury_closing' => round($rows->where('type', 'treasury')->sum('closing'), 2),
                'to_render' => round($workers->where('closing', '>', 0)->sum('closing'), 2),
                'owed' => round(abs($workers->where('closing', '<', 0)->sum('closing')), 2),
                'rendered' => round($rows->sum('rendered'), 2),
                'deposits' => round($rows->where('type', 'treasury')->sum('in') - $workers->sum('out'), 2),
            ],
        ];
    }

    /** Movimientos aprobados desde $start, con monto con signo por cuenta. */
    private function movementsSince(Carbon $start): Collection
    {
        $transactions = Transaction::where('status', 'approved')
            ->whereRaw('COALESCE(approved_at, updated_at) >= ?', [$start])
            ->get()
            ->flatMap(function (Transaction $tx) {
                $at = Carbon::parse($tx->approved_at ?? $tx->updated_at);
                $rows = [['account_id' => $tx->to_account_id, 'at' => $at, 'kind' => 'transfer', 'amount' => (float) $tx->amount]];
                if ($tx->from_account_id) {
                    $rows[] = ['account_id' => $tx->from_account_id, 'at' => $at, 'kind' => 'transfer', 'amount' => -(float) $tx->amount];
                }

                return $rows;
            });

        $expenses = Expense::where('status', 'approved')
            ->whereRaw('COALESCE(reviewed_at, updated_at) >= ?', [$start])
            ->get()
            ->map(fn (Expense $e) => [
                'account_id' => $e->account_id,
                'at' => Carbon::parse($e->reviewed_at ?? $e->updated_at),
                'kind' => 'expense',
                'amount' => -(float) $e->total_amount,
            ]);

        $adjustments = Activity::where('log_name', 'balance_adjustment')
            ->where('subject_type', (new Account)->getMorphClass())
            ->where('created_at', '>=', $start)
            ->get()
            ->map(fn (Activity $log) => [
                'account_id' => $log->subject_id,
                'at' => Carbon::parse($log->created_at),
                'kind' => 'adjustment',
                'amount' => (float) $log->properties['new'] - (float) $log->properties['old'],
            ]);

        return $transactions->concat($expenses)->concat($adjustments);
    }
}
