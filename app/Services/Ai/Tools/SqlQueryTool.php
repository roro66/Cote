<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\ReadOnlySql;

/**
 * SELECT escrito por el modelo. ReadOnlySql y el usuario de solo lectura impiden cualquier escritura.
 */
class SqlQueryTool extends AssistantTool
{
    public function name(): string
    {
        return 'consultar_sql';
    }

    public function description(): string
    {
        return 'Ejecuta un SELECT de PostgreSQL de solo lectura para preguntas que no cubren las otras consultas. Una sola sentencia, sin punto y coma. Pon alias claros a cada columna y nombra las columnas (no uses SELECT *). Excluye rendiciones eliminadas (expenses.is_enabled = true).';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sql' => ['type' => 'string', 'description' => 'Un SELECT (o WITH … SELECT) de PostgreSQL. Sin punto y coma.'],
            ],
            'required' => ['sql'],
        ];
    }

    public function staffOnly(): bool
    {
        return true;
    }

    public function run(array $args): array
    {
        return app(ReadOnlySql::class)->run((string) ($args['sql'] ?? ''));
    }
}
