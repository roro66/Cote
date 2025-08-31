<?php

namespace App\Http\Controllers;

use App\Models\ExpenseItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index()
    {
        // Rango de 12 meses (incluyendo el mes actual)
        $months = collect(range(0, 11))
            ->map(fn ($i) => now()->startOfMonth()->subMonths(11 - $i));

        $start = $months->first()->copy();
        $end = now()->endOfMonth();

        // Gastos mensuales por persona (top 5) considerando items de rendiciones APROBADAS
        $raw = ExpenseItem::query()
            ->select([
                DB::raw("date_trunc('month', expense_items.expense_date) as month"),
                'expenses.submitted_by as person_id',
                DB::raw('SUM(expense_items.amount) as total')
            ])
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.status', 'approved')
            ->whereBetween('expense_items.expense_date', [$start, $end])
            ->groupBy('month', 'person_id')
            ->get();

        // Totales por persona en el rango para obtener Top N
        $totalsByPerson = $raw->groupBy('person_id')->map(fn ($rows) => $rows->sum('total'));
        $topPersonIds = $totalsByPerson->sortDesc()->keys()->take(5)->values();

        // Nombres de personas
        $people = DB::table('people')
            ->whereIn('id', $topPersonIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        // Labels de meses (ej: "ago 2025")
        $labels = $months->map(fn ($d) => $d->translatedFormat('MMM Y'));

        // Dataset por persona (relleno con 0 cuando no hay datos)
        $datasets = [];
        foreach ($topPersonIds as $pid) {
            $perMonth = collect($months)->map(function ($m) use ($raw, $pid) {
                $match = $raw->first(fn ($r) => ((new Carbon($r->month))->isSameMonth($m)) && $r->person_id == $pid);
                return $match ? (float) $match->total : 0.0;
            });

            $person = $people->get($pid);
            $name = $person ? (trim(($person->first_name ?? '').' '.($person->last_name ?? '')) ?: 'Persona #'.$pid) : ('Persona #'.$pid);

            $datasets[] = [
                'label' => $name,
                'data' => $perMonth,
            ];
        }

        // Gastos por categoría (últimos 90 días)
        $catStart = now()->subDays(90)->startOfDay();
        $byCategory = ExpenseItem::query()
            ->select(['category', DB::raw('SUM(amount) as total')])
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.status', 'approved')
            ->whereNotNull('expense_items.category')
            ->where('expense_items.expense_date', '>=', $catStart)
            ->groupBy('category')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->limit(10)
            ->get();

        return view('statistics.index', [
            'monthLabels' => $labels->values(),
            'personDatasets' => $datasets,
            'categoryLabels' => $byCategory->pluck('category'),
            'categoryTotals' => $byCategory->pluck('total')->map(fn ($v) => (float) $v),
        ]);
    }
}
