@php
    $money = fn ($value) => '$' . number_format(abs((float) $value), 0, ',', '.');
    $situation = function ($balance) use ($money) {
        $balance = (float) $balance;
        return match (true) {
            $balance > 0 => ['text' => 'tiene ' . $money($balance) . ' en su poder', 'class' => 'text-warning-emphasis'],
            $balance < 0 => ['text' => 'le debemos ' . $money($balance), 'class' => 'text-danger'],
            default => ['text' => 'está al día', 'class' => 'text-success'],
        };
    };
    $titles = [
        'entregar' => ['Entregar dinero a un trabajador', 'fa-hand-holding-usd', 'success'],
        'devolucion' => ['Me devolvieron dinero', 'fa-undo-alt', 'info'],
        'ingreso' => ['Entró dinero a la caja', 'fa-piggy-bank', 'primary'],
    ];
    [$title, $icon, $color] = $titles[$mode];
    $steps = $mode === 'ingreso'
        ? ['monto' => 'Cuánto', 'detalle' => 'De dónde', 'confirmar' => 'Confirmar']
        : ['persona' => 'Quién', 'monto' => 'Cuánto', 'detalle' => 'Detalle', 'confirmar' => 'Confirmar'];
    $amountValue = (int) $amount;
    $treasuryBalance = (float) ($treasury?->balance ?? 0);
    $personName = $account?->person?->full_name ?? $account?->name;
    $firstName = $account?->person?->first_name ?? $personName;
@endphp

<div class="cote-easy">
    @if (! $treasury)
        <div class="alert alert-danger fs-5">No existe la cuenta Tesorería. Pídele al Jefe que la cree en Cuentas.</div>
    @elseif ($step === 'listo' && $done)
        <div class="card cote-easy-card border-success">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h3 class="mb-2">¡Listo!</h3>
                <p class="fs-5 mb-1">
                    @if ($mode === 'entregar')
                        Entregaste <strong>{{ $money($done->amount) }}</strong> a <strong>{{ $done->worker_name }}</strong>.
                    @elseif ($mode === 'devolucion')
                        <strong>{{ $done->worker_name }}</strong> devolvió <strong>{{ $money($done->amount) }}</strong>.
                    @else
                        Entraron <strong>{{ $money($done->amount) }}</strong> a la caja.
                    @endif
                </p>
                <p class="fs-5">La caja tiene ahora <strong>{{ $money($treasuryBalance) }}</strong>.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                    <button type="button" class="btn btn-{{ $color }} btn-lg" wire:click="restart">
                        <i class="fas fa-plus me-1"></i> Registrar otro
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-home me-1"></i> Volver al inicio
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-lg"
                            onclick="cotePrompt('¿Qué estuvo mal? Se anula y los saldos vuelven a como estaban.').then((reason) => { if (reason) $wire.annulDone(reason); })">
                        <i class="fas fa-undo me-1"></i> Me equivoqué, anular
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="card cote-easy-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="h4 mb-0"><i class="fas {{ $icon }} text-{{ $color }} me-2"></i>{{ $title }}</h3>
                <span class="text-muted">Caja: <strong>{{ $money($treasuryBalance) }}</strong></span>
            </div>

            <div class="card-body">
                <ol class="cote-steps mb-4">
                    @foreach ($steps as $key => $label)
                        @php $reached = array_search($key, array_keys($steps)) <= array_search($step, array_keys($steps)); @endphp
                        <li class="{{ $key === $step ? 'is-current' : ($reached ? 'is-done' : '') }}">{{ $label }}</li>
                    @endforeach
                </ol>

                @if ($step === 'persona')
                    <p class="fs-5 mb-3">{{ $mode === 'entregar' ? '¿A quién le entregas dinero?' : '¿Quién te devolvió dinero?' }}</p>
                    <input type="search" class="form-control form-control-lg mb-3" placeholder="Escribe un nombre para buscar"
                           wire:model.live.debounce.250ms="search" autofocus>
                    <div class="row g-3">
                        @forelse ($workers as $worker)
                            @php $s = $situation($worker->balance); @endphp
                            <div class="col-12 col-md-6 col-xl-4" wire:key="w-{{ $worker->id }}">
                                <button type="button" class="btn cote-person-card w-100 text-start" wire:click="choose({{ $worker->id }})">
                                    <span class="d-block fw-bold fs-5">{{ $worker->person?->full_name ?? $worker->name }}</span>
                                    <span class="d-block {{ $s['class'] }}">{{ ucfirst($s['text']) }}</span>
                                </button>
                            </div>
                        @empty
                            <p class="text-muted fs-5">No encontré a nadie con ese nombre. Para agregar un trabajador, ve a <a href="{{ route('workers.index') }}">Trabajadores</a>.</p>
                        @endforelse
                    </div>
                @endif

                @if ($step === 'monto')
                    @if ($account)
                        @php $s = $situation($account->balance); @endphp
                        <p class="fs-5 mb-1"><strong>{{ $personName }}</strong> {{ $s['text'] }}.</p>
                    @endif
                    <label for="cash-amount" class="form-label fs-5 mt-2">
                        {{ match ($mode) { 'entregar' => '¿Cuánto le entregas?', 'devolucion' => '¿Cuánto te devolvió?', default => '¿Cuánto dinero entró a la caja?' } }}
                    </label>
                    @php
                        $limit = match ($mode) {
                            'entregar' => $treasuryBalance,
                            'devolucion' => max(0, (float) ($account?->balance ?? 0)),
                            default => null,
                        };
                        $limitText = match ($mode) {
                            'entregar' => 'La caja solo tiene ' . $money($treasuryBalance) . '.',
                            'devolucion' => $firstName . ' solo tiene ' . $money(max(0, (float) ($account?->balance ?? 0))) . ' en su poder.',
                            default => '',
                        };
                    @endphp
                    <div x-data="{ value: @js($amountValue ?: ''), limit: @js($limit), get over() { return this.limit !== null && Number(this.value || 0) > this.limit } }">
                        <div class="input-group input-group-lg cote-amount">
                            <span class="input-group-text">$</span>
                            <input type="text" inputmode="numeric" id="cash-amount" class="form-control @error('amount') is-invalid @enderror"
                                   placeholder="0" autocomplete="off" autofocus
                                   value="{{ $amountValue ? number_format($amountValue, 0, ',', '.') : '' }}"
                                   x-on:input="value = $el.value.replace(/\D/g, '').slice(0, 12); $el.value = value ? Number(value).toLocaleString('es-CL') : ''; $wire.set('amount', value, false)"
                                   x-on:keydown.enter.prevent="$wire.toDetail()">
                        </div>
                        <p class="text-danger fs-6 mt-2 mb-0" x-show="over" x-cloak><i class="fas fa-exclamation-triangle me-1"></i>{{ $limitText }}</p>
                    </div>
                    @error('amount') <p class="text-danger fs-6 mt-2 mb-0"><i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}</p> @enderror

                    @if ($mode === 'entregar' && $account && (float) $account->balance < 0)
                        <button type="button" class="btn btn-outline-secondary mt-3" wire:click="useAmount({{ (int) abs($account->balance) }})">
                            Pagarle lo que le debemos ({{ $money($account->balance) }})
                        </button>
                    @endif
                    @if ($mode === 'devolucion' && $account && (float) $account->balance > 0)
                        <button type="button" class="btn btn-outline-secondary mt-3" wire:click="useAmount({{ (int) $account->balance }})">
                            Devolvió todo ({{ $money($account->balance) }})
                        </button>
                    @endif
                @endif

                @if ($step === 'detalle')
                    <label for="cash-detail" class="form-label fs-5">
                        {{ $mode === 'ingreso' ? '¿De dónde viene el dinero?' : 'Detalle (para acordarte después)' }}
                    </label>
                    @if ($mode === 'ingreso')
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @foreach (['Cheque de la empresa', 'Transferencia bancaria de la empresa', 'Efectivo entregado por el jefe'] as $option)
                                <button type="button" class="btn btn-outline-primary" wire:click="useDetail(@js($option))">{{ $option }}</button>
                            @endforeach
                        </div>
                    @endif
                    <input type="text" id="cash-detail" class="form-control form-control-lg @error('description') is-invalid @enderror"
                           wire:model="description" maxlength="500" autofocus
                           placeholder="{{ $mode === 'ingreso' ? 'Ej.: Cheque N° 1234' : 'Ej.: Para materiales de la obra' }}"
                           x-on:keydown.enter.prevent="$wire.toConfirm()">
                    @error('description') <p class="text-danger fs-6 mt-2 mb-0">{{ $message }}</p> @enderror
                @endif

                @if ($step === 'confirmar')
                    @php
                        $treasuryAfter = $mode === 'devolucion' || $mode === 'ingreso' ? $treasuryBalance + $amountValue : $treasuryBalance - $amountValue;
                        $workerAfter = $account ? ((float) $account->balance + ($mode === 'entregar' ? $amountValue : -$amountValue)) : null;
                    @endphp
                    <div class="cote-confirm-box">
                        <p class="fs-4 mb-3">
                            @if ($mode === 'entregar')
                                Vas a entregar <strong>{{ $money($amountValue) }}</strong> a <strong>{{ $personName }}</strong>.
                            @elseif ($mode === 'devolucion')
                                <strong>{{ $personName }}</strong> te devuelve <strong>{{ $money($amountValue) }}</strong>.
                            @else
                                Entran <strong>{{ $money($amountValue) }}</strong> a la caja.
                            @endif
                        </p>
                        <ul class="fs-5 mb-3">
                            <li>La caja queda con <strong>{{ $money($treasuryAfter) }}</strong> (ahora tiene {{ $money($treasuryBalance) }}).</li>
                            @if ($account)
                                <li>{{ $firstName }} queda: <strong>{{ $situation($workerAfter)['text'] }}</strong>.</li>
                            @endif
                            <li>Detalle: {{ $description }}</li>
                        </ul>
                        <p class="text-muted mb-0">Si algo no está bien, usa «Atrás» y corrígelo.</p>
                    </div>
                    @error('amount') <p class="text-danger fs-6 mt-2 mb-0">{{ $message }}</p> @enderror
                @endif
            </div>

            <div class="card-footer d-flex flex-wrap justify-content-between gap-2">
                @if ($step !== 'persona' && ! ($mode === 'ingreso' && $step === 'monto'))
                    <button type="button" class="btn btn-outline-secondary btn-lg" wire:click="back">
                        <i class="fas fa-arrow-left me-1"></i> Atrás
                    </button>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                @endif

                @if ($step === 'monto')
                    <button type="button" class="btn btn-{{ $color }} btn-lg" wire:click="toDetail">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                @elseif ($step === 'detalle')
                    <button type="button" class="btn btn-{{ $color }} btn-lg" wire:click="toConfirm">Siguiente <i class="fas fa-arrow-right ms-1"></i></button>
                @elseif ($step === 'confirmar')
                    <button type="button" class="btn btn-success btn-lg" wire:click="save" wire:loading.attr="disabled">
                        <i class="fas fa-check me-1"></i> Sí, confirmo
                    </button>
                @endif
            </div>
        </div>
    @endif

    @include('partials.cote-form-autosave', [
        'formSlug' => 'cash-movement-'.$mode,
        'entityId' => null,
        'fields' => ['accountId', 'amount', 'description', 'step', 'search'],
    ])
</div>
