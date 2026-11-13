<?php

namespace App\Services\Ai\Tools;

use App\Models\Account;

class BalancesTool extends AssistantTool
{
    public function name(): string
    {
        return 'saldos_personas';
    }

    public function description(): string
    {
        return 'Saldo de la cuenta interna de cada persona: cuánto tiene por rendir (saldo positivo) o cuánto se le debe (saldo negativo). Filtra por persona o por situación. Para un trabajador, solo su propio saldo.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'persona' => ['type' => 'string', 'description' => 'Nombre de la persona tal como lo dijo el usuario (opcional)'],
                'situacion' => [
                    'type' => 'string',
                    'enum' => ['todas', 'por_rendir', 'se_le_debe', 'al_dia'],
                    'description' => 'por_rendir: tienen dinero de la empresa; se_le_debe: gastaron de su bolsillo; al_dia: saldo 0',
                ],
            ],
        ];
    }

    public function run(array $args): array
    {
        [$people, $error] = $this->people($args['persona'] ?? null);
        if ($error) {
            return $error;
        }

        $situation = (string) ($args['situacion'] ?? 'todas');
        $accounts = Account::with('person')
            ->where('type', 'person')
            ->where('is_enabled', true)
            ->when($people !== null, fn ($q) => $q->whereIn('person_id', $people->pluck('id')))
            ->when($situation === 'por_rendir', fn ($q) => $q->where('balance', '>', 0))
            ->when($situation === 'se_le_debe', fn ($q) => $q->where('balance', '<', 0))
            ->when($situation === 'al_dia', fn ($q) => $q->where('balance', 0))
            ->orderBy('balance', $situation === 'se_le_debe' ? 'asc' : 'desc')
            ->get();

        $rows = $accounts->map(fn (Account $a) => [
            'persona' => $a->person?->full_name ?? $a->name,
            'cuenta' => $a->name,
            'saldo_clp' => $this->money($a->balance),
            'situacion' => $a->balance_status['label'],
        ])->values()->all();

        return [
            'ok' => true,
            'columnas' => ['persona', 'cuenta', 'saldo_clp', 'situacion'],
            'filas' => $rows,
            'resumen' => [
                'por_rendir_clp' => $this->money($accounts->where('balance', '>', 0)->sum('balance')),
                'se_les_debe_clp' => $this->money(abs((float) $accounts->where('balance', '<', 0)->sum('balance'))),
            ],
            'mensaje' => $rows === [] ? 'No hay cuentas que calcen.' : 'saldo_clp negativo significa que la empresa le debe ese monto.',
        ];
    }
}
