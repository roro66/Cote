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
        return 'Saldo de la cuenta interna de cada persona: cuánto dinero de la empresa tiene en su poder (saldo positivo) o cuánto le debemos (saldo negativo). Filtra por persona o por situación. Para un trabajador, solo su propio saldo.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'persona' => ['type' => 'string', 'description' => 'Nombre de la persona tal como lo dijo el usuario (opcional)'],
                'situacion' => [
                    'type' => 'string',
                    'enum' => ['todas', 'en_su_poder', 'le_debemos', 'al_dia'],
                    'description' => 'en_su_poder: tienen dinero de la empresa; le_debemos: gastaron de su bolsillo; al_dia: saldo 0',
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
            ->when($situation === 'en_su_poder', fn ($q) => $q->where('balance', '>', 0))
            ->when($situation === 'le_debemos', fn ($q) => $q->where('balance', '<', 0))
            ->when($situation === 'al_dia', fn ($q) => $q->where('balance', 0))
            ->orderBy('balance', $situation === 'le_debemos' ? 'asc' : 'desc')
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
                'en_poder_de_trabajadores_clp' => $this->money($accounts->where('balance', '>', 0)->sum('balance')),
                'les_debemos_clp' => $this->money(abs((float) $accounts->where('balance', '<', 0)->sum('balance'))),
            ],
            'mensaje' => $rows === [] ? 'No hay cuentas que calcen.' : 'saldo_clp negativo significa que la empresa le debe ese monto: dilo como «le debemos».',
        ];
    }
}
