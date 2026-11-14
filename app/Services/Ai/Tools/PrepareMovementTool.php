<?php

namespace App\Services\Ai\Tools;

use App\Models\Account;

/**
 * Deja listo el formulario de una entrega, devolución o ingreso para que el usuario lo revise
 * y lo confirme. No guarda nada: el asistente nunca registra movimientos por su cuenta.
 */
class PrepareMovementTool extends AssistantTool
{
    private const MODES = ['entrega' => 'entregar', 'devolucion' => 'devolucion', 'ingreso' => 'ingreso'];

    public function name(): string
    {
        return 'preparar_movimiento';
    }

    public function description(): string
    {
        return 'Prepara (sin guardar) una entrega de dinero a un trabajador, una devolución de un trabajador o un ingreso de dinero a la caja. Devuelve un enlace para que el usuario revise y confirme. Úsala cuando el usuario pida registrar o hacer uno de esos movimientos.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tipo' => ['type' => 'string', 'enum' => array_keys(self::MODES), 'description' => 'entrega: de la caja a un trabajador; devolucion: de un trabajador a la caja; ingreso: dinero nuevo que entra a la caja'],
                'persona' => ['type' => 'string', 'description' => 'Nombre del trabajador (no se usa en ingreso)'],
                'monto' => ['type' => 'integer', 'description' => 'Monto en pesos, sin puntos. Ejemplo: 50 mil = 50000'],
                'detalle' => ['type' => 'string', 'description' => 'Detalle corto, por ejemplo para qué es o de dónde viene el dinero'],
            ],
            'required' => ['tipo'],
        ];
    }

    public function staffOnly(): bool
    {
        return true;
    }

    public function run(array $args): array
    {
        $type = (string) ($args['tipo'] ?? '');
        if (! isset(self::MODES[$type])) {
            return ['ok' => false, 'mensaje' => 'Indica si es una entrega, una devolución o un ingreso a la caja.'];
        }

        $query = array_filter([
            'monto' => isset($args['monto']) ? max(0, (int) $args['monto']) : null,
            'detalle' => isset($args['detalle']) ? mb_substr(trim((string) $args['detalle']), 0, 200) : null,
        ]);
        $summary = match ($type) {
            'entrega' => 'Entrega',
            'devolucion' => 'Devolución',
            default => 'Ingreso a la caja',
        };

        if ($type !== 'ingreso') {
            [$people, $error] = $this->people($args['persona'] ?? null);
            if ($error) {
                return $error;
            }
            if ($people === null) {
                return ['ok' => false, 'mensaje' => '¿A qué trabajador? Pide el nombre.'];
            }
            if ($people->count() > 1) {
                return ['ok' => false, 'mensaje' => 'Hay varias personas con ese nombre; pregunta cuál.', 'candidatos' => $people->map->full_name->all()];
            }
            $person = $people->first();
            if (! Account::where('type', 'person')->where('person_id', $person->id)->where('is_enabled', true)->exists()) {
                return ['ok' => false, 'mensaje' => "{$person->full_name} no tiene una cuenta activa. Se crea en Trabajadores → Crear su cuenta."];
            }
            $query['persona'] = $person->id;
            $summary .= ($type === 'entrega' ? ' a ' : ' de ').$person->full_name;
        }

        if (isset($query['monto'])) {
            $summary .= ' por $'.number_format($query['monto'], 0, ',', '.');
        }

        return [
            'ok' => true,
            'url' => route('cash.movement', ['mode' => self::MODES[$type], ...$query]),
            'resumen' => ['preparado' => $summary],
            'mensaje' => 'NO está guardado. Dile al usuario que use el botón «Revisar y confirmar» para revisarlo y guardarlo.',
        ];
    }
}
