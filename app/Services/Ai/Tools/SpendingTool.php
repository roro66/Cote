<?php

namespace App\Services\Ai\Tools;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

class SpendingTool extends AssistantTool
{
    public function name(): string
    {
        return 'gasto_rendido';
    }

    public function description(): string
    {
        return 'Gasto rendido y aprobado en un período, agrupado por persona, por categoría o por mes, según la fecha de cada comprobante. Filtra por persona o categoría. Para un trabajador, solo su propio gasto.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'agrupar' => ['type' => 'string', 'enum' => ['persona', 'categoria', 'mes'], 'description' => 'Cómo agrupar (por defecto persona)'],
                'persona' => ['type' => 'string', 'description' => 'Nombre de la persona (opcional)'],
                'categoria' => ['type' => 'string', 'description' => 'Nombre de la categoría de gasto (opcional)'],
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

        $categoryText = trim((string) ($args['categoria'] ?? ''));
        $categoryIds = null;
        if ($categoryText !== '') {
            $folded = $this->fold($categoryText);
            $categoryIds = ExpenseCategory::all()
                ->filter(fn ($c) => str_contains($this->fold($c->name), $folded))
                ->pluck('id');
            if ($categoryIds->isEmpty()) {
                return [
                    'ok' => false,
                    'mensaje' => "No hay una categoría llamada «{$categoryText}».",
                    'candidatos' => ExpenseCategory::orderBy('name')->pluck('name')->all(),
                ];
            }
        }

        $group = (string) ($args['agrupar'] ?? 'persona');
        $base = DB::table('expense_items as i')
            ->join('expenses as e', 'e.id', '=', 'i.expense_id')
            ->leftJoin('people as p', 'p.id', '=', 'e.submitted_by')
            ->leftJoin('expense_categories as c', 'c.id', '=', 'i.expense_category_id')
            ->where('e.status', 'approved')
            ->where('e.is_enabled', true)
            ->whereBetween('i.expense_date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->when($people !== null, fn ($q) => $q->whereIn('e.submitted_by', $people->pluck('id')))
            ->when($categoryIds !== null, fn ($q) => $q->whereIn('i.expense_category_id', $categoryIds));

        $items = $base->get(['i.amount', 'i.expense_date', 'p.first_name', 'p.last_name', 'c.name as category']);

        $grouped = $items->groupBy(fn ($row) => match ($group) {
            'categoria' => $row->category ?? 'Sin categoría',
            'mes' => substr((string) $row->expense_date, 0, 7),
            default => trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: 'Sin persona',
        });

        $rows = $grouped->map(fn ($rows, $key) => [
            $group => $key,
            'gastos' => $rows->count(),
            'total_clp' => $this->money($rows->sum('amount')),
        ]);
        $rows = ($group === 'mes' ? $rows->sortKeys() : $rows->sortByDesc('total_clp'))->values()->all();

        return [
            'ok' => true,
            'periodo' => $label,
            'columnas' => [$group, 'gastos', 'total_clp'],
            'filas' => $rows,
            'resumen' => [
                'total_clp' => $this->money($items->sum('amount')),
                'gastos' => $items->count(),
                'mayor' => $rows === [] ? null : ($group === 'mes' ? collect($rows)->sortByDesc('total_clp')->first()[$group] : $rows[0][$group]),
            ],
            'mensaje' => $rows === [] ? "No hay gasto aprobado en el período {$label}." : null,
        ];
    }
}
