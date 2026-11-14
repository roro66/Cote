<div class="cote-assistant">
    <div wire:loading.flex wire:target="ask,useExample,useSaved" class="cote-assistant-busy" role="status" aria-live="polite">
        <div class="cote-assistant-busy-card">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-3 mb-1 fw-bold">Pensando…</p>
            <p class="mb-0 small cote-assistant-muted">Si la pregunta es sobre cifras, el asistente arma una consulta de solo lectura. Suele tardar unos segundos.</p>
        </div>
    </div>

    <div class="alert alert-info d-flex gap-2 align-items-start">
        <i class="fas fa-lock mt-1"></i>
        <div class="small">
            Pregunta por tus datos o por cómo se usa una pantalla. Las cifras salen de consultas de lectura: nada se modifica.
            Los pasos salen del manual de COTE y solo de lo que tu perfil puede abrir.
            Bajo cada respuesta, <strong>Datos usados</strong> muestra de dónde salió.
            <span class="cote-assistant-muted">Modelo {{ $model }} (Google Gemini).</span>
        </div>
    </div>

    <div class="card cote-assistant-card">
        <div class="card-body" style="min-height: 260px;">
            @if($saved->isNotEmpty())
                <p class="small cote-assistant-muted mb-2"><i class="fas fa-bookmark"></i> Tus preguntas</p>
                <div class="d-flex flex-wrap mb-3 gap-2">
                    @foreach($saved as $item)
                        <div class="btn-group btn-group-sm" wire:key="saved-{{ $item->id }}">
                            <button type="button" class="btn btn-outline-secondary text-start" wire:click="useSaved({{ $item->id }})">{{ $item->question }}</button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="forgetSaved({{ $item->id }})" title="Quitar" aria-label="Quitar pregunta guardada">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
            @if($saveNotice !== '')
                <p class="text-warning small">{{ $saveNotice }}</p>
            @endif

            @if($conversation === [])
                <p class="small cote-assistant-muted mb-2">Prueba con una de estas preguntas:</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($examples as $i => $example)
                        <button type="button" class="btn btn-outline-primary btn-sm text-start" wire:click="useExample({{ $i }})">{{ $example }}</button>
                    @endforeach
                </div>
            @endif

            @foreach($conversation as $i => $turn)
                <div class="mb-3 d-flex {{ $turn['role'] === 'user' ? 'justify-content-end' : '' }}" wire:key="turn-{{ $i }}">
                    <div class="cote-assistant-msg {{ $turn['role'] === 'user' ? 'cote-assistant-msg--user' : (! empty($turn['error']) ? 'cote-assistant-msg--error' : 'cote-assistant-msg--bot') }}">
                        @if($turn['role'] === 'user')
                            <div>{{ $turn['content'] }}</div>
                            <button type="button" class="btn btn-link btn-sm p-0 mt-1 cote-assistant-save" wire:click="toggleSaved({{ $i }})">
                                <i class="fas fa-bookmark"></i>
                                {{ in_array($turn['content'], $savedTexts, true) ? 'Guardada' : 'Guardar' }}
                            </button>
                        @else
                            <div class="cote-assistant-answer">{!! \Illuminate\Support\Str::markdown($turn['content'], ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                            @foreach(($turn['steps'] ?? []) as $step)
                                @if(! empty($step['url']))
                                    <a href="{{ $step['url'] }}" class="btn btn-success btn-lg mt-2">
                                        <i class="fas fa-check-circle me-1"></i> Revisar y confirmar
                                    </a>
                                @endif
                            @endforeach
                        @endif

                        @foreach(($turn['steps'] ?? []) as $s => $step)
                            <details class="mt-2" wire:key="turn-{{ $i }}-step-{{ $s }}">
                                <summary class="small cote-assistant-muted" style="cursor: pointer;">
                                    <i class="fas {{ $step['tool'] === 'consultar_manual' ? 'fa-book' : 'fa-database' }}"></i>
                                    Datos usados: <code>{{ $step['tool'] }}</code>
                                    @foreach($step['arguments'] as $k => $v)
                                        · {{ $k }}: <strong>{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}</strong>
                                    @endforeach
                                </summary>
                                <div class="small mt-2">
                                    @if(! empty($step['consulta']))
                                        <pre class="small mb-2 p-2 border rounded cote-assistant-sql">{{ $step['consulta'] }}</pre>
                                    @endif
                                    @if($step['secciones'])
                                        <div><strong>Manual:</strong> {{ implode(' · ', $step['secciones']) }}</div>
                                    @endif
                                    @if($step['periodo'])
                                        <div><strong>Período:</strong> {{ $step['periodo'] }}</div>
                                    @endif
                                    @if($step['mensaje'])
                                        <div class="cote-assistant-muted">{{ $step['mensaje'] }}</div>
                                    @endif
                                    @if($step['candidatos'])
                                        <div><strong>Candidatos:</strong> {{ implode(' · ', $step['candidatos']) }}</div>
                                    @endif
                                    @if($step['resumen'])
                                        <div class="mt-1 d-flex flex-wrap gap-1">
                                            @foreach($step['resumen'] as $k => $v)
                                                <span class="badge text-bg-light border">{{ str_replace('_', ' ', str_replace('_clp', '', $k)) }}: {{ is_numeric($v) ? (str_ends_with($k, '_clp') ? '$' : '') . number_format((float) $v, 0, ',', '.') : $v }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($step['filas'])
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm table-bordered table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        @foreach($step['columnas'] as $col)
                                                            <th>{{ str_replace('_', ' ', str_replace('_clp', '', $col)) }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($step['filas'] as $row)
                                                        <tr>
                                                            @foreach($step['columnas'] as $col)
                                                                @php $cell = $row[$col] ?? ''; @endphp
                                                                <td class="{{ is_numeric($cell) ? 'text-end text-nowrap' : '' }}">
                                                                    {{ is_numeric($cell) ? (str_ends_with($col, '_clp') ? '$' : '') . number_format((float) $cell, fmod((float) $cell, 1) ? 2 : 0, ',', '.') : $cell }}
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </details>
                        @endforeach

                        @if(isset($turn['seconds']))
                            <div class="small cote-assistant-muted mt-1">{{ number_format($turn['seconds'], 1, ',', '.') }} s</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-footer">
            <form wire:submit="ask" class="d-flex flex-wrap gap-2" id="assistant-question-form">
                <input type="text"
                       id="assistant-question"
                       class="form-control"
                       style="flex: 1 1 16rem;"
                       wire:model="question"
                       maxlength="500"
                       placeholder="Ej.: ¿Cuánto tiene por rendir Juan Pérez?"
                       autocomplete="off"
                       autofocus>
                <button type="button" class="btn btn-outline-secondary" data-assistant-mic title="Dictar pregunta" aria-label="Dictar pregunta">
                    <i class="fas fa-microphone"></i>
                </button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="ask,useExample,useSaved">
                    <i class="fas fa-paper-plane"></i> Preguntar
                </button>
                @if($conversation !== [])
                    <button type="button" class="btn btn-outline-secondary" wire:click="clear" title="Nueva conversación" aria-label="Nueva conversación">
                        <i class="fas fa-eraser"></i>
                    </button>
                @endif
            </form>
            <p id="assistant-voice-notice" class="small cote-assistant-muted mb-0 mt-2" hidden></p>
        </div>
    </div>

    <style>
        .cote-assistant-msg { max-width: 85%; border-radius: 12px; padding: 0.6rem 0.9rem; }
        .cote-assistant-msg--user { background: #e7f1ff; color: #1f2937; }
        .cote-assistant-msg--bot { background: #f8f9fa; border: 1px solid #e3e7ec; color: #1f2937; }
        .cote-assistant-msg--error { background: #fdecea; border: 1px solid #f5c2c7; color: #7a2e2e; }
        .cote-assistant-answer p:last-child, .cote-assistant-answer ul:last-child, .cote-assistant-answer ol:last-child { margin-bottom: 0; }
        .cote-assistant-answer ul, .cote-assistant-answer ol { padding-left: 1.2rem; }
        .cote-assistant-muted { color: #6c757d; }
        .cote-assistant-sql { white-space: pre-wrap; background: #f8f9fa; }
        .cote-assistant-busy { position: fixed; inset: 0; z-index: 2000; background: rgba(33, 37, 41, 0.35); align-items: center; justify-content: center; }
        .cote-assistant-busy-card { background: #fff; border-radius: 10px; padding: 1.5rem 1.75rem; max-width: 26rem; text-align: center; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2); }
        [data-bs-theme="dark"] .cote-assistant-msg--user { background: #1e3a5f; color: #e9ecef; }
        [data-bs-theme="dark"] .cote-assistant-msg--bot { background: #2b3035; border-color: #495057; color: #e9ecef; }
        [data-bs-theme="dark"] .cote-assistant-msg--error { background: #4a2626; border-color: #7a3a3a; color: #f8dede; }
        [data-bs-theme="dark"] .cote-assistant-muted { color: #adb5bd; }
        [data-bs-theme="dark"] .cote-assistant-sql { background: #212529; color: #e9ecef; }
        [data-bs-theme="dark"] .cote-assistant-busy-card { background: #343a40; color: #e9ecef; }
    </style>
    <script>
        if (!window.coteAssistantVoiceBound) {
            window.coteAssistantVoiceBound = true;
            let recognition = null;
            let listening = false;
            let baseText = '';

            const voiceNotice = (message) => {
                const notice = document.getElementById('assistant-voice-notice');
                if (notice) {
                    notice.hidden = message === '';
                    notice.textContent = message;
                }
            };
            const questionInput = () => document.getElementById('assistant-question');
            const writeQuestion = (text) => {
                const input = questionInput();
                if (input) {
                    input.value = text.slice(0, 500);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            };
            const markListening = (on) => {
                const button = document.querySelector('[data-assistant-mic]');
                button?.classList.toggle('btn-danger', on);
                button?.classList.toggle('btn-outline-secondary', !on);
            };
            const stopVoice = () => {
                listening = false;
                if (recognition) {
                    const current = recognition;
                    recognition = null;
                    current.onend = null;
                    try { current.stop(); } catch (error) { /* ya estaba cerrado */ }
                }
                markListening(false);
            };

            document.addEventListener('click', (event) => {
                if (!event.target.closest('[data-assistant-mic]')) {
                    return;
                }
                event.preventDefault();

                if (listening) {
                    stopVoice();
                    voiceNotice('');
                    questionInput()?.focus();
                    return;
                }

                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!window.isSecureContext) {
                    voiceNotice('El navegador solo entrega el micrófono en https o en localhost. En la dirección del servidor, por http, no puede dictar.');
                    return;
                }
                if (!SpeechRecognition) {
                    voiceNotice('Este navegador no transcribe voz. Prueba con Chrome.');
                    return;
                }

                baseText = (questionInput()?.value || '').trim();
                recognition = new SpeechRecognition();
                recognition.lang = 'es-CL';
                recognition.interimResults = true;
                recognition.continuous = true;
                recognition.onresult = (speechEvent) => {
                    let spoken = '';
                    for (const result of speechEvent.results) {
                        spoken += result[0].transcript;
                    }
                    spoken = spoken.trim();
                    writeQuestion(baseText === '' ? spoken : baseText + ' ' + spoken);
                };
                recognition.onerror = (speechEvent) => {
                    const code = speechEvent.error || '';
                    if (code === 'not-allowed' || code === 'service-not-allowed') {
                        listening = false;
                        voiceNotice('El navegador no entregó el micrófono. Revisa el permiso del sitio.');
                    } else if (code !== 'no-speech' && code !== 'aborted') {
                        listening = false;
                        voiceNotice('No se pudo transcribir. Aprieta el micrófono otra vez.');
                    }
                };
                recognition.onend = () => {
                    if (!listening || !recognition) {
                        markListening(false);
                        return;
                    }
                    baseText = (questionInput()?.value || '').trim();
                    window.setTimeout(() => {
                        if (listening && recognition) {
                            try { recognition.start(); } catch (error) {
                                stopVoice();
                                voiceNotice('Se cortó el micrófono. Apriétalo otra vez para seguir.');
                            }
                        }
                    }, 200);
                };

                listening = true;
                markListening(true);
                voiceNotice('Escuchando… puedes hacer pausas. Aprieta el micrófono otra vez cuando termines.');
                try { recognition.start(); } catch (error) {
                    stopVoice();
                    voiceNotice('No se pudo abrir el micrófono. Intenta de nuevo.');
                }
            });

            document.addEventListener('submit', (event) => {
                if (event.target?.id === 'assistant-question-form') {
                    stopVoice();
                    voiceNotice('');
                }
            });
        }
    </script>
</div>
