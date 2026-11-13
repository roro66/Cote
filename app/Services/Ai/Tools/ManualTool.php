<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\AppManual;

/**
 * Pasos del manual, solo de las pantallas que el usuario puede abrir.
 */
class ManualTool extends AssistantTool
{
    public function name(): string
    {
        return 'consultar_manual';
    }

    public function description(): string
    {
        return 'Explica cómo se usa COTE: menús, botones, pasos, reglas, mensajes de error y quién puede hacer cada cosa (rendiciones, comprobantes, aprobaciones, entregas, devoluciones, ingresos a caja chica, cuentas, personas, usuarios, contraseñas, informes, cierre de mes). No trae cifras.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tema' => ['type' => 'string', 'description' => 'Tema de la pregunta con las palabras de la pantalla. Ejemplo: rechazar una rendición.'],
            ],
            'required' => ['tema'],
        ];
    }

    public function run(array $args): array
    {
        return app(AppManual::class)->search((string) ($args['tema'] ?? ''), $this->user());
    }
}
