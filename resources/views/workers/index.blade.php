@php
    $money = fn ($value) => '$' . number_format(abs((float) $value), 0, ',', '.');
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <i class="fas fa-users"></i> Trabajadores y su dinero
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 cote-easy">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex flex-wrap gap-3">
                    <div class="cote-stat cote-stat--out">
                        <span>En poder de trabajadores <x-help text="Dinero de la empresa que los trabajadores tienen y todavía no rinden con boletas ni devuelven." /></span>
                        <strong>{{ $money($holding) }}</strong>
                    </div>
                    <div class="cote-stat cote-stat--owed">
                        <span>Les debemos <x-help text="Lo que gastaron de su bolsillo. Se les paga con una entrega desde la caja." /></span>
                        <strong>{{ $money($owed) }}</strong>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('workers.import') }}" class="btn btn-outline-success btn-lg">
                        <i class="fas fa-file-excel me-1"></i> Importar desde Excel
                    </a>
                    <a href="{{ route('people.index', ['nuevo' => 1]) }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-1"></i> Agregar trabajador
                    </a>
                </div>
            </div>

            <input type="search" class="form-control form-control-lg mb-3" placeholder="Escribe un nombre para buscar" id="worker-search" autocomplete="off">

            <div class="row g-3" id="worker-cards">
                @forelse ($people as $person)
                    @php
                        $account = $person->accounts->first();
                        $balance = (float) ($account?->balance ?? 0);
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4 worker-card" data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($person->full_name)) }}">
                        <div class="card cote-easy-card h-100">
                            <div class="card-body">
                                <h3 class="h5 mb-1">{{ $person->full_name }}</h3>
                                @if (! $account)
                                    <p class="text-muted mb-3">Todavía no tiene cuenta para recibir dinero.</p>
                                    <form method="POST" action="{{ route('workers.account', $person) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-lg w-100"><i class="fas fa-wallet me-1"></i> Crear su cuenta</button>
                                    </form>
                                @else
                                    @if ($balance > 0)
                                        <p class="fs-5 mb-3 text-warning-emphasis">Tiene <strong>{{ $money($balance) }}</strong> en su poder</p>
                                    @elseif ($balance < 0)
                                        <p class="fs-5 mb-3 text-danger">Le debemos <strong>{{ $money($balance) }}</strong></p>
                                    @else
                                        <p class="fs-5 mb-3 text-success">Está al día</p>
                                    @endif
                                    @unless ($account->is_enabled)
                                        <p class="small text-muted">Su cuenta está desactivada.</p>
                                    @endunless
                                    <div class="d-grid gap-2" style="grid-template-columns: 1fr 1fr;">
                                        <a href="{{ route('cash.movement', ['mode' => 'entregar', 'persona' => $person->id]) }}" class="btn btn-success">
                                            <i class="fas fa-hand-holding-usd me-1"></i> {{ $balance < 0 ? 'Pagarle' : 'Entregar' }}
                                        </a>
                                        <a href="{{ route('cash.movement', ['mode' => 'devolucion', 'persona' => $person->id]) }}" class="btn btn-info {{ $balance > 0 ? '' : 'disabled' }}" @if ($balance <= 0) aria-disabled="true" @endif>
                                            <i class="fas fa-undo-alt me-1"></i> Devolvió
                                        </a>
                                        <a href="{{ route('expenses.index', ['buscar' => $person->full_name]) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-receipt me-1"></i> Rendiciones
                                        </a>
                                        <a href="{{ route('accounts.movements', $account) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-list me-1"></i> Cartola
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted fs-5">Todavía no hay trabajadores. Usa «Agregar trabajador».</p>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('worker-search')?.addEventListener('input', function () {
            const text = this.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
            document.querySelectorAll('.worker-card').forEach(card => {
                card.style.display = !text || card.dataset.name.includes(text) ? '' : 'none';
            });
        });
    </script>
    @endpush
</x-app-layout>
