<?php

namespace App\Http\Controllers;

use App\Models\ExpenseItem;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\ExcelExportService;
use Illuminate\Support\Str;

class StatisticsController extends Controller
{
    public function index()
    {
        // 12 meses incluyendo el actual
        $months = collect(range(0, 11))
            ->map(fn ($i) => now()->startOfMonth()->subMonths(11 - $i));

        $start = $months->first()->copy();
        $end = now()->endOfMonth();

        // Personas (habilitadas) ordenadas por nombre
        $people = Person::query()
            ->enabled()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $selectedId = optional($people->first())->id;
        $labels = $months->map(fn ($d) => $d->translatedFormat('MMMM Y')); // nombres completos

        [$data] = $selectedId ? [$this->monthlyTotalsForPerson($selectedId, $start, $end, $months)] : [[/* empty */]];

        // Gastos por categoría (últimos N días; por defecto 90)
        $defaultCategoryDays = 90;
        $catStart = now()->subDays($defaultCategoryDays)->startOfDay();
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
            'people' => $people,
            'selectedPersonId' => $selectedId,
            'monthLabels' => $labels->values(),
            'selectedPersonMonthly' => $data,
            'categoryDays' => $defaultCategoryDays,
            'categoryLabels' => $byCategory->pluck('category'),
            'categoryTotals' => $byCategory->pluck('total')->map(fn ($v) => (float) $v),
        ]);
    }

    public function personMonthly(Person $person)
    {
        $months = collect(range(0, 11))
            ->map(fn ($i) => now()->startOfMonth()->subMonths(11 - $i));
        $start = $months->first()->copy();
        $end = now()->endOfMonth();

    $labels = $months->map(fn ($d) => $d->translatedFormat('MMMM Y'));
    $data = $this->monthlyTotalsForPerson($person->id, $start, $end, $months);

        return response()->json([
            'monthLabels' => $labels->values(),
            'data' => $data,
        ]);
    }

    public function exportPersonMonthly(Person $person)
    {
        $months = collect(range(0, 11))
            ->map(fn ($i) => now()->startOfMonth()->subMonths(11 - $i));
        $start = $months->first()->copy();
        $end = now()->endOfMonth();

        $data = $this->monthlyTotalsForPerson($person->id, $start, $end, $months);

    $safe = Str::slug($person->first_name . ' ' . $person->last_name);
        $filename = 'gasto-mensual-' . $safe . '.xlsx';
        $headings = ['Mes', 'Total (CLP)'];
        $rows = [];
        $mesesEs = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
            7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
        foreach ($months as $i => $date) {
            /** @var \Illuminate\Support\Carbon $date */
            $label = ($mesesEs[$date->month] ?? strtolower($date->format('F'))) . ' ' . $date->year;
            $rows[] = [$label, (int) round($data[$i] ?? 0)];
        }
        return ExcelExportService::streamXlsx($filename, $headings, $rows);
    }

    /**
     * Calcula totales mensuales para una persona en un rango y devuelve un array indexado por los 12 meses provistos.
     */
    protected function monthlyTotalsForPerson(int $personId, $start, $end, $months): array
    {
        $raw = ExpenseItem::query()
            ->select([
                DB::raw("date_trunc('month', expense_items.expense_date) as month"),
                DB::raw('SUM(expense_items.amount) as total')
            ])
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.status', 'approved')
            ->where('expenses.submitted_by', $personId)
            ->whereBetween('expense_items.expense_date', [$start, $end])
            ->groupBy('month')
            ->get();

        return collect($months)->map(function ($m) use ($raw) {
            $match = $raw->first(fn ($r) => (new Carbon($r->month))->isSameMonth($m));
            return $match ? (float) $match->total : 0.0;
        })->values()->all();
    }

    public function categories(Request $request)
    {
        $days = (int) $request->query('days', 90);
        if ($days < 7) $days = 7;
        if ($days > 3650) $days = 3650; // límite razonable ~10 años

        $start = now()->subDays($days)->startOfDay();

        $byCategory = ExpenseItem::query()
            ->select(['category', DB::raw('SUM(amount) as total')])
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.status', 'approved')
            ->whereNotNull('expense_items.category')
            ->where('expense_items.expense_date', '>=', $start)
            ->groupBy('category')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->limit(10)
            ->get();

        return response()->json([
            'labels' => $byCategory->pluck('category'),
            'totals' => $byCategory->pluck('total')->map(fn ($v) => (float) $v),
            'days' => $days,
        ]);
    }

    public function categoriesMonthly(Request $request)
    {
        $months = max(1, min(60, (int)$request->query('months', 6)));
        $monthsColl = collect(range(0, $months - 1))
            ->map(fn ($i) => now()->startOfMonth()->subMonths($months - 1 - $i));

        $start = $monthsColl->first()->copy();
        $end = now()->endOfMonth();

        $raw = ExpenseItem::query()
            ->select([
                DB::raw("date_trunc('month', expense_items.expense_date) as month"),
                'expense_items.expense_category_id',
                DB::raw('SUM(expense_items.amount) as total')
            ])
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.status', 'approved')
            ->whereBetween('expense_items.expense_date', [$start, $end])
            ->whereNotNull('expense_items.expense_category_id')
            ->groupBy('month', 'expense_items.expense_category_id')
            ->get();

        // Top categories by total across period
        $catTotals = $raw->groupBy('expense_category_id')->map(fn ($g) => $g->sum('total'))
            ->sortDesc()->take(8);
        $catIds = $catTotals->keys()->all();

        $categories = \App\Models\ExpenseCategory::whereIn('id', $catIds)->get()->keyBy('id');

        $datasets = [];
        foreach ($catIds as $cid) {
            $label = $categories[$cid]->name ?? ('#' . $cid);
            $data = $monthsColl->map(function ($m) use ($raw, $cid) {
                $match = $raw->first(fn ($r) => (new Carbon($r->month))->isSameMonth($m) && $r->expense_category_id == $cid);
                return $match ? (float) $match->total : 0.0;
            })->values()->all();
            $datasets[] = ['label' => $label, 'data' => $data];
        }

        $labels = $monthsColl->map(fn ($d) => $d->translatedFormat('MMM Y'));

        return response()->json([
            'monthLabels' => $labels->values(),
            'datasets' => $datasets,
        ]);
    }

    public function personCategories(Person $person, Request $request)
    {
        $months = max(1, min(60, (int)$request->query('months', 6)));
        $start = now()->startOfMonth()->subMonths($months - 1)->startOfDay();

        $byCategory = ExpenseItem::query()
            ->select(['expense_items.expense_category_id', DB::raw('SUM(expense_items.amount) as total')])
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.status', 'approved')
            ->where('expenses.submitted_by', $person->id)
            ->whereBetween('expense_items.expense_date', [$start, now()->endOfDay()])
            ->whereNotNull('expense_items.expense_category_id')
            ->groupBy('expense_items.expense_category_id')
            ->orderByDesc(DB::raw('SUM(expense_items.amount)'))
            ->limit(12)
            ->get();

        $catIds = $byCategory->pluck('expense_category_id')->all();
        $categories = \App\Models\ExpenseCategory::whereIn('id', $catIds)->get()->keyBy('id');

        $labels = $byCategory->map(fn ($r) => $categories[$r->expense_category_id]->name ?? ('#'.$r->expense_category_id));
        $totals = $byCategory->map(fn ($r) => (float) $r->total);

        return response()->json([
            'labels' => $labels->values(),
            'totals' => $totals->values(),
            'months' => $months,
        ]);
    }

    public function techniciansMonthly(Request $request)
    {
        $months = max(1, min(24, (int)$request->query('months', 6)));
        $monthsColl = collect(range(0, $months - 1))
            ->map(fn ($i) => now()->startOfMonth()->subMonths($months - 1 - $i));
        $start = $monthsColl->first()->copy();
        $end = now()->endOfMonth();

        $raw = ExpenseItem::query()
            ->select([
                DB::raw("date_trunc('month', expense_items.expense_date) as month"),
                'expenses.submitted_by',
                DB::raw('SUM(expense_items.amount) as total')
            ])
            ->join('expenses', 'expense_items.expense_id', '=', 'expenses.id')
            ->where('expenses.status', 'approved')
            ->whereBetween('expense_items.expense_date', [$start, $end])
            ->groupBy('month', 'expenses.submitted_by')
            ->get();

        // Top technicians by total
        $techTotals = $raw->groupBy('submitted_by')->map(fn ($g) => $g->sum('total'))->sortDesc()->take(8);
        $techIds = $techTotals->keys()->all();
        $people = Person::whereIn('id', $techIds)->get()->keyBy('id');

        $datasets = [];
        foreach ($techIds as $pid) {
            $label = trim(($people[$pid]->first_name ?? 'T').' '.($people[$pid]->last_name ?? ''));
            $data = $monthsColl->map(function ($m) use ($raw, $pid) {
                $match = $raw->first(fn ($r) => (new Carbon($r->month))->isSameMonth($m) && $r->submitted_by == $pid);
                return $match ? (float) $match->total : 0.0;
            })->values()->all();
            $datasets[] = ['label' => $label, 'data' => $data];
        }

        $labels = $monthsColl->map(fn ($d) => $d->translatedFormat('MMM Y'));

        return response()->json([
            'monthLabels' => $labels->values(),
            'datasets' => $datasets,
        ]);
    }
}
