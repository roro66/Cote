<?php

namespace App\Services\Ai\Tools;

use App\Models\Expense;
use App\Models\Transaction;

class PendingApprovalsTool extends AssistantTool
{
    public function name(): string
    {
        return 'pendientes_aprobacion';
    }

    public function description(): string
    {
        return 'Lista lo que espera aprobación: transferencias e ingresos a caja chica pendientes, y rendiciones enviadas sin aprobar.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tipo' => ['type' => 'string', 'enum' => ['todo', 'transacciones', 'rendiciones'], 'description' => 'Qué listar (por defecto todo)'],
            ],
        ];
    }

    public function staffOnly(): bool
    {
        return true;
    }

    public function run(array $args): array
    {
        $type = (string) ($args['tipo'] ?? 'todo');
        $rows = [];

        if ($type !== 'rendiciones') {
            $transactions = Transaction::with(['fromAccount.person', 'toAccount.person', 'createdBy'])
                ->where('status', 'pending')->where('is_enabled', true)
                ->orderBy('created_at')->get();
            foreach ($transactions as $tx) {
                $rows[] = [
                    'numero' => $tx->transaction_number,
                    'clase' => $tx->type_spanish,
                    'detalle' => $tx->type === 'deposit'
                        ? 'Hacia Tesorería — '.$tx->description
                        : ($tx->fromAccount?->person?->full_name ?? $tx->fromAccount?->name ?? '?').' → '.($tx->toAccount?->person?->full_name ?? $tx->toAccount?->name ?? '?'),
                    'monto_clp' => $this->money($tx->amount),
                    'fecha' => $tx->created_at?->timezone('America/Santiago')->format('d/m/Y'),
                    'creada_por' => $tx->createdBy?->name,
                ];
            }
        }

        if ($type !== 'transacciones') {
            $expenses = Expense::with(['submittedBy', 'items'])
                ->where('status', 'submitted')->where('is_enabled', true)
                ->orderBy('submitted_at')->get();
            foreach ($expenses as $expense) {
                $withoutReceipt = $expense->items->where('without_receipt', true)->count();
                $rows[] = [
                    'numero' => $expense->expense_number,
                    'clase' => 'Rendición',
                    'detalle' => ($expense->submittedBy?->full_name ?? '?').' — '.$expense->description
                        .($withoutReceipt ? " ({$withoutReceipt} gasto(s) sin comprobante)" : ''),
                    'monto_clp' => $this->money($expense->total_amount),
                    'fecha' => $expense->submitted_at?->timezone('America/Santiago')->format('d/m/Y'),
                    'creada_por' => $expense->submittedBy?->full_name,
                ];
            }
        }

        return [
            'ok' => true,
            'columnas' => ['numero', 'clase', 'detalle', 'monto_clp', 'fecha', 'creada_por'],
            'filas' => $rows,
            'resumen' => [
                'cantidad' => count($rows),
                'total_clp' => $this->money(array_sum(array_column($rows, 'monto_clp'))),
            ],
            'mensaje' => $rows === [] ? 'No hay nada pendiente de aprobación.' : 'Se aprueban en el menú Aprobaciones.',
        ];
    }
}
