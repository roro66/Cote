<?php

namespace App\Services\Ai\Tools;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Transaction;

class TreasurySummaryTool extends AssistantTool
{
    public function name(): string
    {
        return 'resumen_tesoreria';
    }

    public function description(): string
    {
        return 'Resumen actual de la caja chica: saldo de Tesorería, dinero en poder de trabajadores, lo que les debemos a trabajadores y montos pendientes de aprobar.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass];
    }

    public function staffOnly(): bool
    {
        return true;
    }

    public function run(array $args): array
    {
        $people = Account::where('type', 'person')->where('is_enabled', true);
        $pendingTx = Transaction::where('status', 'pending')->where('is_enabled', true);
        $pendingExp = Expense::where('status', 'submitted')->where('is_enabled', true);

        return [
            'ok' => true,
            'resumen' => [
                'tesoreria_clp' => $this->money(Account::where('type', 'treasury')->sum('balance')),
                'en_poder_de_trabajadores_clp' => $this->money((clone $people)->where('balance', '>', 0)->sum('balance')),
                'trabajadores_con_dinero' => (clone $people)->where('balance', '>', 0)->count(),
                'les_debemos_clp' => $this->money(abs((float) (clone $people)->where('balance', '<', 0)->sum('balance'))),
                'trabajadores_a_los_que_se_debe' => (clone $people)->where('balance', '<', 0)->count(),
                'transacciones_pendientes' => (clone $pendingTx)->count(),
                'transacciones_pendientes_clp' => $this->money($pendingTx->sum('amount')),
                'rendiciones_pendientes' => (clone $pendingExp)->count(),
                'rendiciones_pendientes_clp' => $this->money($pendingExp->sum('total_amount')),
            ],
        ];
    }
}
