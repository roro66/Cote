<?php

namespace App\Services\Ai\Tools;

use App\Models\Expense;

class ExpenseReportsTool extends AssistantTool
{
    private const STATUSES = [
        'pendiente' => ['submitted', 'reviewed'],
        'aprobada' => ['approved'],
        'rechazada' => ['rejected'],
    ];

    public function name(): string
    {
        return 'rendiciones';
    }

    public function description(): string
    {
        return 'Lista rendiciones con su número, persona, fecha, estado, total y motivo de rechazo. Filtra por estado, persona y período (fecha de la rendición). Para un trabajador, solo las suyas.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'estado' => ['type' => 'string', 'enum' => ['todas', 'pendiente', 'aprobada', 'rechazada'], 'description' => 'Por defecto todas'],
                'persona' => ['type' => 'string', 'description' => 'Nombre de la persona (opcional)'],
                'numero' => ['type' => 'string', 'description' => 'Número de rendición, por ejemplo RND-2026-000012 (opcional)'],
                ...$this->periodParameters('por defecto 3'),
            ],
        ];
    }

    public function run(array $args): array
    {
        [$people, $error] = $this->people($args['persona'] ?? null);
        if ($error) {
            return $error;
        }
        [$from, $to, $label] = $this->period($args);
        $number = trim((string) ($args['numero'] ?? ''));
        $status = (string) ($args['estado'] ?? 'todas');

        $expenses = Expense::with(['submittedBy', 'items'])
            ->where('is_enabled', true)
            ->when($people !== null, fn ($q) => $q->whereIn('submitted_by', $people->pluck('id')))
            ->when($number !== '', fn ($q) => $q->where('expense_number', 'like', '%'.$number.'%'))
            ->when($number === '', fn ($q) => $q->whereBetween('expense_date', [$from->toDateString(), $to->toDateString().' 23:59:59']))
            ->when(isset(self::STATUSES[$status]), fn ($q) => $q->whereIn('status', self::STATUSES[$status]))
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->limit(100)
            ->get();

        $rows = $expenses->map(fn (Expense $e) => [
            'numero' => $e->expense_number,
            'persona' => $e->submittedBy?->full_name,
            'fecha' => $e->expense_date?->format('d/m/Y'),
            'estado' => $e->status_spanish,
            'total_clp' => $this->money($e->total_amount),
            'descripcion' => $e->description,
            'gastos' => $e->items->count(),
            'sin_comprobante' => $e->items->where('without_receipt', true)->count(),
            'motivo_rechazo' => $e->status === 'rejected' ? $e->rejection_reason : null,
        ])->values()->all();

        return [
            'ok' => true,
            'periodo' => $number === '' ? $label : null,
            'columnas' => ['numero', 'persona', 'fecha', 'estado', 'total_clp', 'descripcion', 'gastos', 'sin_comprobante', 'motivo_rechazo'],
            'filas' => $rows,
            'resumen' => [
                'cantidad' => count($rows),
                'total_clp' => $this->money($expenses->sum('total_amount')),
            ],
            'mensaje' => $rows === [] ? 'No hay rendiciones que calcen.' : null,
        ];
    }
}
