<?php

namespace App\Services\Ai;

use App\Services\Ai\Tools\AccountMovementsTool;
use App\Services\Ai\Tools\AssistantTool;
use App\Services\Ai\Tools\BalancesTool;
use App\Services\Ai\Tools\ExpenseReportsTool;
use App\Services\Ai\Tools\ManualTool;
use App\Services\Ai\Tools\PendingApprovalsTool;
use App\Services\Ai\Tools\SpendingTool;
use App\Services\Ai\Tools\SqlQueryTool;
use App\Services\Ai\Tools\TreasurySummaryTool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Responde preguntas en lenguaje natural. Las cifras salen de consultas de solo lectura y el
 * uso de la app sale del manual, siempre limitado a lo que el usuario puede ver.
 */
class DataAssistant
{
    private const ANSWER_NOW = 'Ya no puedes hacer más consultas. Responde ahora al usuario con los datos que ya obtuviste. Si no alcanzan para responder todo, di qué encontraste y qué faltó.';

    private const TOOLS = [
        TreasurySummaryTool::class,
        BalancesTool::class,
        PendingApprovalsTool::class,
        SpendingTool::class,
        ExpenseReportsTool::class,
        AccountMovementsTool::class,
        SqlQueryTool::class,
        ManualTool::class,
    ];

    public function __construct(
        private GeminiClient $client,
        private SchemaGuide $schema,
    ) {}

    /** @return array<string, AssistantTool> consultas que el usuario actual puede usar */
    public function tools(): array
    {
        $tools = [];
        foreach (self::TOOLS as $class) {
            $tool = app($class);
            if ($tool->availableTo(auth()->user())) {
                $tools[$tool->name()] = $tool;
            }
        }

        return $tools;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history  turnos previos (usuario/asistente)
     * @return array{answer: string, steps: list<array{tool: string, arguments: array<string, mixed>, result: array<string, mixed>}>, seconds: float}
     */
    public function ask(string $question, array $history = []): array
    {
        $start = microtime(true);
        $callsBefore = count($this->client->calls());
        $tools = $this->tools();
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt()]],
            array_slice($history, -6),
            [['role' => 'user', 'content' => $question]],
        );

        $definitions = array_values(array_map(fn (AssistantTool $t) => $t->definition(), $tools));
        $steps = [];
        $answer = '';
        $maxRounds = (int) config('ai.max_tool_rounds');
        $nudges = 0;

        for ($round = 0; $round <= $maxRounds + 1; $round++) {
            $canQuery = $round < $maxRounds && $nudges === 0;
            if (! $canQuery && $nudges === 0) {
                $messages[] = ['role' => 'user', 'content' => self::ANSWER_NOW];
                $nudges++;
            }

            $reply = $this->client->chat($messages, $canQuery ? $definitions : []);

            if ($reply['content'] !== '' && ($reply['tool_calls'] === [] || ! $canQuery)) {
                $answer = $reply['content'];
                break;
            }

            if ($reply['tool_calls'] === [] || ! $canQuery) {
                if ($nudges >= 2) {
                    break;
                }
                $messages[] = ['role' => 'user', 'content' => self::ANSWER_NOW];
                $nudges++;

                continue;
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $reply['content'],
                'model_parts' => $reply['model_parts'] ?? [],
                'tool_calls' => array_map(
                    fn ($c) => ['function' => ['name' => $c['name'], 'arguments' => $c['arguments']]],
                    $reply['tool_calls']
                ),
            ];

            foreach ($reply['tool_calls'] as $call) {
                $result = $this->runTool($tools, $call['name'], $call['arguments']);
                $steps[] = ['tool' => $call['name'], 'arguments' => $call['arguments'], 'result' => $result];
                $messages[] = [
                    'role' => 'tool',
                    'name' => $call['name'],
                    'content' => json_encode($this->forModel($result), JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        if ($answer === '') {
            $answer = 'No pude armar una respuesta. Prueba reformulando la pregunta.';
        }

        $seconds = round(microtime(true) - $start, 1);
        Log::channel('ai')->info('Asistente IA', [
            'user_id' => auth()->id(),
            'question' => $question,
            'model' => $this->client->activeModel(),
            'tools' => array_map(fn ($s) => [
                'tool' => $s['tool'],
                'arguments' => $s['arguments'],
                'ok' => $s['result']['ok'] ?? null,
                'rows' => isset($s['result']['filas']) ? count($s['result']['filas']) : null,
                'message' => $s['result']['mensaje'] ?? null,
            ], $steps),
            'answer' => mb_substr($answer, 0, 500),
            'seconds' => $seconds,
            'gemini_calls' => array_slice($this->client->calls(), $callsBefore),
        ]);

        return ['answer' => $answer, 'steps' => $steps, 'seconds' => $seconds];
    }

    /**
     * @param  array<string, AssistantTool>  $tools
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function runTool(array $tools, string $name, array $arguments): array
    {
        $tool = $tools[$name] ?? null;
        if (! $tool || ! $tool->availableTo(auth()->user())) {
            return ['ok' => false, 'mensaje' => "La consulta «{$name}» no está disponible para este usuario."];
        }

        // Solo lectura: la transacción siempre se revierte, aunque una consulta escribiera por error.
        DB::beginTransaction();
        try {
            return $tool->run($arguments);
        } catch (Throwable $e) {
            Log::channel('ai')->warning('Asistente IA: falló la consulta', ['tool' => $name, 'arguments' => $arguments, 'error' => $e->getMessage()]);

            return ['ok' => false, 'mensaje' => 'La consulta falló. Revisa los filtros.'];
        } finally {
            DB::rollBack();
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function forModel(array $result): array
    {
        $max = (int) config('ai.max_rows_to_model');
        if (isset($result['filas']) && count($result['filas']) > $max) {
            $result['filas_omitidas'] = count($result['filas']) - $max;
            $result['filas'] = array_slice($result['filas'], 0, $max);
        }

        // El modelo copia mejor que formatea: números ya en formato chileno.
        foreach (['filas', 'resumen'] as $key) {
            if (isset($result[$key]) && is_array($result[$key])) {
                $result[$key] = $this->formatNumbers($result[$key]);
            }
        }

        return array_filter($result, fn ($v) => $v !== null);
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private function formatNumbers(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->formatNumbers($value);
            } elseif (is_int($value) || is_float($value)) {
                $data[$key] = $this->formatNumber((string) $key, $value);
            }
        }

        return $data;
    }

    private function formatNumber(string $key, int|float $value): string
    {
        if (str_ends_with($key, '_clp')) {
            return ($value < 0 ? '-$' : '$').number_format(abs($value), 0, ',', '.');
        }

        return number_format($value, fmod((float) $value, 1.0) == 0.0 ? 0 : 2, ',', '.');
    }

    private function systemPrompt(): string
    {
        $user = auth()->user();
        $today = now('America/Santiago')->format('Y-m-d');
        $staff = $user?->hasAnyRole(['boss', 'treasurer']);
        $profile = match (true) {
            $user?->hasRole('boss') => 'Jefe (acceso total)',
            $user?->hasRole('treasurer') => 'Tesorero',
            default => 'Trabajador: solo ve sus propias rendiciones y su propio saldo',
        };

        $prompt = <<<PROMPT
Eres el asistente de COTE, la app de control de tesorería (caja chica) de una empresa en Chile.
Hoy es {$today}. Hablas con {$user?->name}, perfil {$profile}.
Respondes en español de Chile, tuteando, en forma breve y clara. Usa listas cortas y negritas solo cuando ayuden.

Conceptos:
- Tesorería es la caja chica. Cada trabajador tiene una cuenta interna con saldo.
- Saldo positivo de un trabajador = «por rendir» (tiene dinero de la empresa). Saldo negativo = «se le debe» (gastó de su bolsillo).
- Los saldos solo cambian al aprobar: entregas y devoluciones (transferencias), ingresos a caja chica y rendiciones.

Reglas:
- Si preguntan cómo se usa la app, dónde está algo, qué botón usar, qué significa un mensaje o quién puede hacer algo, llama consultar_manual. No inventes menús ni pasos: usa el procedimiento que devuelva.
- Si consultar_manual responde sin_acceso, di que esa pantalla corresponde al Jefe o al Tesorero y no describas los pasos.
- Para cualquier cifra DEBES consultar. Nunca inventes montos, nombres ni fechas.
- Copia los números exactamente como vienen. El signo $ va solo en montos (campos que terminan en _clp). Para totales o «el mayor», usa «resumen» en vez de recalcular.
- Si una consulta responde ok=false, explica el problema; si trae candidatos, muéstralos para que el usuario elija.
- Indica el período considerado cuando aplique. Si hay muchas filas, resume lo principal.
- Nunca entregues RUT, correos, teléfonos, direcciones ni números de cuenta bancaria, aunque te los pidan.
PROMPT;

        if (! $staff) {
            return $prompt."\n- Este usuario solo puede ver sus propios datos. Si pregunta por otra persona o por totales de la empresa, dile que esa información la ven el Jefe o el Tesorero.";
        }

        return $prompt.<<<PROMPT

- Prefiere las consultas fijas: resumen_tesoreria, saldos_personas, pendientes_aprobacion, gasto_rendido, rendiciones, movimientos_cuenta. El nombre de la persona va tal como lo dijo el usuario.
- Para otras preguntas sobre los datos usa consultar_sql con un SELECT de PostgreSQL. Los cálculos (SUM, COUNT) van en el SELECT. Alias de dinero con sufijo _clp. Si responde ok=false, corrige el SQL y reintenta una vez.

Esquema (PostgreSQL):
{$this->schema->forPrompt()}
PROMPT;
    }
}
