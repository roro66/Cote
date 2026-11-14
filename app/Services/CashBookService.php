<?php

namespace App\Services;

use App\Models\Account;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Libro de caja de un mes: lo que entró y salió de Tesorería, en orden de fecha,
 * con el saldo después de cada movimiento. Como el cuaderno o la planilla de siempre.
 */
class CashBookService
{
    public function __construct(private AccountStatementService $statements) {}

    /**
     * @return array{treasury: Account|null, month: CarbonImmutable, opening: float, closing: float, total_in: float, total_out: float, rows: Collection<int, array<string, mixed>>}
     */
    public function build(CarbonImmutable $month): array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();
        $treasury = Account::where('type', 'treasury')->first();

        if (! $treasury) {
            return ['treasury' => null, 'month' => $start, 'opening' => 0.0, 'closing' => 0.0, 'total_in' => 0.0, 'total_out' => 0.0, 'rows' => collect()];
        }

        $statement = $this->statements->build($treasury, $start, $end);
        $opening = (float) $statement['opening_balance'];

        $rows = $statement['movements']
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'date' => $m['date'],
                'kind' => $m['kind'],
                'detail' => $m['detail'],
                'reference' => $m['reference'],
                'url' => $m['url'],
                'in' => (float) $m['in'],
                'out' => (float) $m['out'],
                'balance' => (float) $m['balance_after'],
            ]);

        $totalIn = (float) $rows->sum('in');
        $totalOut = (float) $rows->sum('out');

        return [
            'treasury' => $treasury,
            'month' => $start,
            'opening' => round($opening, 2),
            'closing' => round($opening + $totalIn - $totalOut, 2),
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'rows' => $rows,
        ];
    }
}
