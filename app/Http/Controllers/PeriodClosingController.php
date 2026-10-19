<?php

namespace App\Http\Controllers;

use App\Models\PeriodClosing;
use App\Services\PeriodReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PeriodClosingController extends Controller
{
    public function __construct(private PeriodReportService $reports)
    {
    }

    public function index(Request $request)
    {
        $month = $this->monthFrom($request->query('month')) ?? now()->subMonthNoOverflow()->startOfMonth();
        $closing = PeriodClosing::with('closedBy')->whereDate('period', $month)->first();

        return view('closings.index', [
            'month' => $month,
            'closing' => $closing,
            'report' => $closing ? $closing->snapshot : $this->reports->build($month),
            'closeBlocker' => $closing ? null : $this->closeBlocker($month),
            'history' => PeriodClosing::with('closedBy')->orderByDesc('period')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|date_format:Y-m',
            'treasury_counted' => 'nullable|numeric',
            'notes' => 'nullable|string|max:2000',
        ], [], ['treasury_counted' => 'dinero contado en Tesorería', 'notes' => 'observaciones']);

        $month = $this->monthFrom($data['month']);
        if ($blocker = $this->closeBlocker($month)) {
            return back()->with('toastr', ['type' => 'error', 'message' => $blocker]);
        }

        $report = $this->reports->build($month);
        PeriodClosing::create([
            'period' => $month->toDateString(),
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
            'treasury_counted' => $data['treasury_counted'] ?? null,
            'notes' => $data['notes'] ?? null,
            'snapshot' => [
                'rows' => $report['rows'],
                'totals' => $report['totals'],
            ],
        ]);

        return redirect()->route('closings.index', ['month' => $month->format('Y-m')])
            ->with('toastr', ['type' => 'success', 'message' => 'Mes ' . $month->translatedFormat('F Y') . ' cerrado.']);
    }

    public function destroy(PeriodClosing $closing)
    {
        abort_unless(request()->user()->hasRole('boss'), 403);

        $latest = PeriodClosing::orderByDesc('period')->first();
        if (! $latest || ! $latest->is($closing)) {
            return back()->with('toastr', ['type' => 'error', 'message' => 'Solo se puede reabrir el último mes cerrado.']);
        }

        $month = $closing->period->format('Y-m');
        $closing->delete();

        return redirect()->route('closings.index', ['month' => $month])
            ->with('toastr', ['type' => 'success', 'message' => 'Cierre reabierto.']);
    }

    /** Motivo por el que el mes no se puede cerrar, o null si se puede. */
    private function closeBlocker(Carbon $month): ?string
    {
        if ($month->gte(now()->startOfMonth())) {
            return 'Solo se pueden cerrar meses ya terminados.';
        }
        if (PeriodClosing::whereDate('period', $month)->exists()) {
            return 'Este mes ya está cerrado.';
        }

        $last = PeriodClosing::orderByDesc('period')->first();
        if ($last && $month->lt($last->period)) {
            return 'Ya hay un cierre posterior; no se puede cerrar un mes anterior.';
        }
        if ($last && ! $month->equalTo($last->period->copy()->addMonth())) {
            return 'Primero cierra ' . $last->period->copy()->addMonth()->translatedFormat('F Y') . '.';
        }

        return null;
    }

    private function monthFrom(?string $value): ?Carbon
    {
        if (! $value || ! preg_match('/^\d{4}-\d{2}$/', $value)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value . '-01')->startOfDay();
    }
}
