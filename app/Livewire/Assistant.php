<?php

namespace App\Livewire;

use App\Models\AssistantSavedQuestion;
use App\Services\Ai\DataAssistant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Preguntas en lenguaje natural sobre COTE: cómo se usa (manual) y los datos que el usuario puede ver.
 */
class Assistant extends Component
{
    public const STAFF_EXAMPLES = [
        '¿Cuánto hay en Tesorería y cuánto está por rendir?',
        '¿Qué está pendiente de aprobar?',
        '¿A quiénes les debemos dinero?',
        '¿Cuánto se gastó por categoría el mes pasado?',
        '¿Quién rindió más en los últimos 3 meses?',
        '¿Cómo registro un ingreso a caja chica?',
    ];

    public const WORKER_EXAMPLES = [
        '¿Cuánto tengo por rendir?',
        '¿En qué estado están mis rendiciones?',
        '¿Por qué me rechazaron una rendición?',
        '¿Cómo creo una rendición?',
        '¿Qué hago si no tengo boleta?',
    ];

    public string $question = '';

    public string $saveNotice = '';

    /** @var list<array{role: string, content: string, steps?: list<array<string, mixed>>, seconds?: float, error?: bool}> */
    #[Locked]
    public array $conversation = [];

    public function boot(): void
    {
        abort_unless(config('ai.enabled'), 404);
        abort_unless(Auth::check(), 403);
    }

    public function mount(): void
    {
        $preset = trim((string) request()->query('q', ''));
        if ($preset !== '') {
            $this->question = mb_substr($preset, 0, 500);
            $this->ask(app(DataAssistant::class));
        }
    }

    public function useExample(int $index, DataAssistant $assistant): void
    {
        $this->question = $this->examples()[$index] ?? '';
        $this->ask($assistant);
    }

    public function useSaved(int $id, DataAssistant $assistant): void
    {
        $this->question = AssistantSavedQuestion::where('user_id', Auth::id())->findOrFail($id)->question;
        $this->ask($assistant);
    }

    public function toggleSaved(int $index): void
    {
        $turn = $this->conversation[$index] ?? null;
        $text = is_array($turn) && ($turn['role'] ?? '') === 'user' ? trim((string) $turn['content']) : '';
        if ($text === '') {
            return;
        }

        $this->saveNotice = '';
        $existing = AssistantSavedQuestion::where('user_id', Auth::id())->where('question', $text)->first();
        if ($existing) {
            $existing->delete();

            return;
        }

        if (AssistantSavedQuestion::where('user_id', Auth::id())->count() >= AssistantSavedQuestion::MAX_PER_USER) {
            $this->saveNotice = 'Puedes guardar hasta '.AssistantSavedQuestion::MAX_PER_USER.' preguntas. Quita una para agregar otra.';

            return;
        }

        AssistantSavedQuestion::create(['user_id' => Auth::id(), 'question' => mb_substr($text, 0, 500)]);
    }

    public function forgetSaved(int $id): void
    {
        AssistantSavedQuestion::where('user_id', Auth::id())->whereKey($id)->delete();
        $this->saveNotice = '';
    }

    public function ask(DataAssistant $assistant): void
    {
        $question = mb_substr(trim($this->question), 0, 500);
        if ($question === '') {
            return;
        }

        @set_time_limit(240);
        $history = array_map(
            fn ($turn) => ['role' => $turn['role'], 'content' => $turn['content']],
            array_values(array_filter($this->conversation, fn ($turn) => empty($turn['error'])))
        );

        $this->conversation[] = ['role' => 'user', 'content' => $question];
        $this->question = '';

        try {
            $result = $assistant->ask($question, $history);
            $this->conversation[] = [
                'role' => 'assistant',
                'content' => $result['answer'],
                'steps' => array_map(fn ($step) => $this->compactStep($step), $result['steps']),
                'seconds' => $result['seconds'],
            ];
        } catch (Throwable $e) {
            report($e);
            Log::channel('ai')->error('Asistente IA: sin respuesta', ['user_id' => Auth::id(), 'question' => $question, 'error' => $e->getMessage()]);
            $this->conversation[] = [
                'role' => 'assistant',
                'content' => 'No pude consultar a Gemini: '.$e->getMessage(),
                'error' => true,
            ];
        }
    }

    public function clear(): void
    {
        $this->conversation = [];
        $this->question = '';
    }

    /** @return list<string> */
    private function examples(): array
    {
        return Auth::user()?->hasAnyRole(['boss', 'treasurer']) ? self::STAFF_EXAMPLES : self::WORKER_EXAMPLES;
    }

    /**
     * @param  array<string, mixed>  $step
     * @return array<string, mixed>
     */
    private function compactStep(array $step): array
    {
        $result = $step['result'];
        $arguments = $step['arguments'];
        unset($arguments['sql']);

        return [
            'tool' => $step['tool'],
            'arguments' => $arguments,
            'consulta' => $result['consulta'] ?? null,
            'ok' => (bool) ($result['ok'] ?? false),
            'mensaje' => $result['mensaje'] ?? null,
            'periodo' => $result['periodo'] ?? null,
            'columnas' => $result['columnas'] ?? [],
            'filas' => array_slice($result['filas'] ?? [], 0, 50),
            'resumen' => $result['resumen'] ?? null,
            'candidatos' => $result['candidatos'] ?? [],
            'secciones' => array_column($result['secciones'] ?? [], 'titulo'),
        ];
    }

    public function render()
    {
        $saved = AssistantSavedQuestion::where('user_id', Auth::id())->orderBy('id')->get();

        return view('livewire.assistant', [
            'examples' => $this->examples(),
            'model' => config('ai.gemini.model'),
            'saved' => $saved,
            'savedTexts' => $saved->pluck('question')->all(),
        ]);
    }
}
