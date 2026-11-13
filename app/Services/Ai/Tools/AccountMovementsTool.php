<?php

namespace App\Services\Ai\Tools;

use App\Models\Account;
use App\Services\AccountStatementService;

class AccountMovementsTool extends AssistantTool
{
    public function name(): string
    {
        return 'movimientos_cuenta';
    }

    public function description(): string
    {
        return 'Cartola de una cuenta: entregas, devoluciones, ingresos, rendiciones aprobadas y ajustes, con el saldo después de cada movimiento. Usa persona «Tesorería» para la caja. Para un trabajador, solo su cuenta.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'persona' => ['type' => 'string', 'description' => 'Nombre de la persona, o «Tesorería»'],
                ...$this->periodParameters('por defecto 3'),
            ],
        ];
    }

    public function run(array $args): array
    {
        $text = trim((string) ($args['persona'] ?? ''));
        $wantsTreasury = $this->isStaff() && in_array($this->fold($text), ['tesoreria', 'caja', 'caja chica'], true);

        if ($wantsTreasury) {
            $account = Account::where('type', 'treasury')->first();
        } else {
            if ($this->isStaff() && $text === '') {
                return ['ok' => false, 'mensaje' => 'Indica de qué persona es la cuenta, o «Tesorería».'];
            }
            [$people, $error] = $this->people($text);
            if ($error) {
                return $error;
            }
            if ($people->count() > 1) {
                return ['ok' => false, 'mensaje' => 'Hay varias personas con ese nombre; pide cuál.', 'candidatos' => $people->map->full_name->all()];
            }
            $account = Account::where('type', 'person')->where('person_id', $people->first()->id)
                ->orderByDesc('is_enabled')->orderBy('id')->first();
        }

        if (! $account) {
            return ['ok' => false, 'mensaje' => 'Esa persona no tiene una cuenta interna. Se crea en Cuentas → Nueva Cuenta.'];
        }

        [$from, $to, $label] = $this->period($args);
        $statement = app(AccountStatementService::class)->build($account);

        $movements = $statement['movements']->filter(fn ($m) => $m['date'] !== null
            && $m['date']->between($from, $to));

        $rows = $movements->map(fn ($m) => [
            'fecha' => $m['date']->timezone('America/Santiago')->format('d/m/Y'),
            'tipo' => $m['kind'],
            'referencia' => $m['reference'],
            'detalle' => $m['detail'],
            'ingreso_clp' => $this->money($m['in']),
            'egreso_clp' => $this->money($m['out']),
            'saldo_clp' => $this->money($m['balance_after']),
        ])->values()->all();

        return [
            'ok' => true,
            'periodo' => $label,
            'cuenta' => $account->person?->full_name ?? $account->name,
            'columnas' => ['fecha', 'tipo', 'referencia', 'detalle', 'ingreso_clp', 'egreso_clp', 'saldo_clp'],
            'filas' => $rows,
            'resumen' => [
                'saldo_actual_clp' => $this->money($account->balance),
                'situacion' => $account->balance_status['label'],
                'ingresos_clp' => $this->money($movements->sum('in')),
                'egresos_clp' => $this->money($movements->sum('out')),
            ],
            'mensaje' => $rows === [] ? "La cuenta no tuvo movimientos aprobados entre {$label}." : 'Filas del más reciente al más antiguo; saldo_clp es el saldo después del movimiento.',
        ];
    }
}
