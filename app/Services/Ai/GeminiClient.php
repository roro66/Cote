<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente REST de Gemini (Google AI) con function calling.
 * Traduce el formato de mensajes OpenAI-like que usa DataAssistant.
 */
class GeminiClient
{
    private ?string $activeModel = null;

    /** @var list<array{model: string, status: int|string, seconds: float}> */
    private array $calls = [];

    public function activeModel(): ?string
    {
        return $this->activeModel;
    }

    /**
     * Cada llamada HTTP hecha a Gemini (incluidos reintentos y modelos de respaldo).
     *
     * @return list<array{model: string, status: int|string, seconds: float}>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools  definiciones tipo OpenAI (type=function)
     * @return array{content: string, tool_calls: list<array{name: string, arguments: array<string, mixed>}>, model_parts: list<array<string, mixed>>}
     */
    public function chat(array $messages, array $tools = []): array
    {
        $apiKey = (string) config('ai.gemini.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('Falta GEMINI_API_KEY en el .env. Crea una clave en https://aistudio.google.com/apikey');
        }

        [$system, $contents] = $this->toGeminiContents($messages);
        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.1,
            ],
        ];
        // Gemini 3: menos razonamiento interno responde en segundos en vez de medio minuto.
        $thinking = (string) config('ai.gemini.thinking_level', '');
        if ($thinking !== '') {
            $payload['generationConfig']['thinkingConfig'] = ['thinkingLevel' => $thinking];
        }
        if ($system !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
        }
        if ($tools !== []) {
            $payload['tools'] = [[
                'functionDeclarations' => array_map(fn (array $t) => $this->toDeclaration($t), $tools),
            ]];
            $payload['toolConfig'] = [
                'functionCallingConfig' => ['mode' => 'AUTO'],
            ];
        }

        $response = $this->post($apiKey, $payload);

        if (in_array($response->status(), [429, 500, 503], true)) {
            throw new RuntimeException('Gemini está saturado en este momento (probé todos los modelos de respaldo). Intenta de nuevo en un minuto.');
        }
        if ($response->failed()) {
            $detail = $response->json('error.message') ?? $response->body();
            throw new RuntimeException('Gemini no respondió ('.$response->status().'): '.$detail);
        }

        $parts = $response->json('candidates.0.content.parts') ?? [];
        $texts = [];
        $calls = [];
        foreach ($parts as $part) {
            if (isset($part['text']) && is_string($part['text']) && empty($part['thought'])) {
                $texts[] = $part['text'];
            }
            if (isset($part['functionCall']['name'])) {
                $args = $part['functionCall']['args'] ?? [];
                if (is_string($args)) {
                    $args = json_decode($args, true) ?: [];
                }
                $calls[] = [
                    'name' => (string) $part['functionCall']['name'],
                    'arguments' => is_array($args) ? $args : [],
                ];
            }
        }

        return [
            'content' => trim(implode("\n", $texts)),
            'tool_calls' => $calls,
            // Gemini 3 exige reenviar thoughtSignature en la misma parte del functionCall.
            'model_parts' => is_array($parts) ? $parts : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $apiKey, array $payload): \Illuminate\Http\Client\Response
    {
        // Durante una misma pregunta se sigue con el modelo que ya respondió: Gemini 3 valida
        // las thoughtSignature contra el modelo que las generó.
        $models = array_values(array_unique(array_filter(array_merge(
            [$this->activeModel, (string) config('ai.gemini.model')],
            config('ai.gemini.fallback_models', []),
        ))));

        $response = null;
        $lastError = null;
        $attempts = max(1, (int) config('ai.gemini.retry_rounds', 2));
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($attempt > 1) {
                usleep((int) config('ai.gemini.retry_wait_ms', 2500) * 1000);
            }
            foreach ($models as $model) {
                $url = rtrim((string) config('ai.gemini.base_url'), '/').'/models/'.$model.':generateContent';
                $start = microtime(true);
                try {
                    $response = Http::timeout((int) config('ai.gemini.timeout'))
                        ->withHeaders(['x-goog-api-key' => $apiKey])
                        ->acceptJson()
                        ->post($url, $payload);
                } catch (ConnectionException $e) {
                    $lastError = $e;
                    $this->calls[] = ['model' => $model, 'status' => 'sin respuesta', 'seconds' => round(microtime(true) - $start, 1)];

                    continue;
                }
                $this->calls[] = ['model' => $model, 'status' => $response->status(), 'seconds' => round(microtime(true) - $start, 1)];

                if (! in_array($response->status(), [429, 500, 503], true)) {
                    if ($response->successful()) {
                        $this->activeModel = $model;
                    }

                    return $response;
                }
            }
        }

        if ($response === null) {
            throw new RuntimeException('Gemini no respondió a tiempo. Intenta de nuevo en un minuto.', 0, $lastError);
        }

        return $response;
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return array{0: string, 1: list<array<string, mixed>>}
     */
    private function toGeminiContents(array $messages): array
    {
        $system = '';
        $contents = [];

        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? '');

            if ($role === 'system') {
                $system = trim($system."\n".(string) ($message['content'] ?? ''));

                continue;
            }

            if ($role === 'user') {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => (string) ($message['content'] ?? '')]],
                ];

                continue;
            }

            if ($role === 'assistant') {
                $replay = $message['model_parts'] ?? null;
                if (is_array($replay) && $replay !== []) {
                    // Un functionCall sin argumentos vuelve de json_decode como [] y Gemini exige un objeto.
                    foreach ($replay as $i => $part) {
                        if (isset($part['functionCall']) && empty($part['functionCall']['args'])) {
                            $replay[$i]['functionCall']['args'] = new \stdClass;
                        }
                    }
                    $contents[] = ['role' => 'model', 'parts' => $replay];

                    continue;
                }

                $parts = [];
                $text = trim((string) ($message['content'] ?? ''));
                if ($text !== '') {
                    $parts[] = ['text' => $text];
                }
                foreach ($message['tool_calls'] ?? [] as $call) {
                    $fn = $call['function'] ?? $call;
                    $parts[] = [
                        'functionCall' => [
                            'name' => (string) ($fn['name'] ?? ''),
                            'args' => ! empty($fn['arguments']) && is_array($fn['arguments']) ? $fn['arguments'] : new \stdClass,
                        ],
                    ];
                }
                if ($parts !== []) {
                    $contents[] = ['role' => 'model', 'parts' => $parts];
                }

                continue;
            }

            if ($role === 'tool') {
                $payload = json_decode((string) ($message['content'] ?? '{}'), true);
                if (! is_array($payload)) {
                    $payload = ['raw' => (string) ($message['content'] ?? '')];
                }
                $contents[] = [
                    'role' => 'user',
                    'parts' => [[
                        'functionResponse' => [
                            'name' => (string) ($message['name'] ?? 'consulta'),
                            'response' => $payload,
                        ],
                    ]],
                ];
            }
        }

        return [trim($system), $contents];
    }

    /**
     * @param  array<string, mixed>  $tool
     * @return array<string, mixed>
     */
    private function toDeclaration(array $tool): array
    {
        $fn = $tool['function'] ?? $tool;

        return [
            'name' => (string) ($fn['name'] ?? ''),
            'description' => (string) ($fn['description'] ?? ''),
            'parameters' => $fn['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass],
        ];
    }
}
