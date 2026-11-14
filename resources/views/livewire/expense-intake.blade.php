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
    // «Ahora a Carlos le debemos $X», «Carlos quedará con $X en su poder»
    $sentence = function (string $name, $balance, bool $future) use ($money) {
        $balance = (float) $balance;
        return match (true) {
            $balance > 0 => ['text' => $future ? "{$name} quedará con " . $money($balance) . ' en su poder' : "{$name} tiene " . $money($balance) . ' en su poder', 'class' => 'text-warning-emphasis'],
            $balance < 0 => ['text' => ($future ? "a {$name} le deberemos " : "a {$name} le debemos ") . $money($balance), 'class' => 'text-danger'],
            default => ['text' => $future ? "{$name} quedará al día" : "{$name} está al día", 'class' => 'text-success'],
        };
    };
    $personName = $account?->person?->full_name ?? $account?->name;
    $firstName = $account?->person?->first_name ?? $personName;
@endphp

<div class="cote-easy">
    @if ($step === 'listo' && $done)
        @php $s = $sentence($done->account->person?->first_name ?? 'el trabajador', $done->account->balance, false); @endphp
        <div class="card cote-easy-card border-success">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h3 class="mb-2">¡Listo!</h3>
                <p class="fs-5 mb-1">
                    La rendición <strong>{{ $done->expense_number }}</strong> de <strong>{{ $done->account->person?->full_name }}</strong>
                    por <strong>{{ $money($done->total_amount) }}</strong> quedó registrada y aprobada.
                </p>
                <p class="fs-5 {{ $s['class'] }}">Ahora {{ $s['text'] }}.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                    <button type="button" class="btn btn-warning btn-lg" wire:click="restart">
                        <i class="fas fa-plus me-1"></i> Registrar otra rendición
                    </button>
                    <a href="{{ route('expenses.show', $done) }}" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-eye me-1"></i> Ver la rendición
                    </a>
                    <a href="{{ route('expenses.pdf', $done) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-print me-1"></i> Imprimir para firmar
                    </a>
                </div>
            </div>
        </div>
    @elseif ($step === 'persona')
        <div class="card cote-easy-card">
            <div class="card-header">
                <h3 class="h4 mb-0"><i class="fas fa-file-invoice-dollar text-warning me-2"></i>Registrar una rendición recibida</h3>
            </div>
            <div class="card-body">
                <ol class="cote-steps mb-4">
                    <li class="is-current">Trabajador</li>
                    <li>Fotos y planilla</li>
                    <li>Listo</li>
                </ol>
                <p class="fs-5 mb-1"><strong>¿Quién te mandó la rendición?</strong></p>
                <p class="text-muted mb-3">Haz clic en su nombre para empezar. Después subes las fotos y copias la planilla.</p>
                <input type="search" class="form-control form-control-lg mb-3" placeholder="Escribe un nombre para buscar"
                       wire:model.live.debounce.250ms="search" autofocus>
                <div class="row g-3">
                    @forelse ($workers as $worker)
                        @php $s = $situation($worker->balance); @endphp
                        <div class="col-12 col-md-6 col-xl-4" wire:key="w-{{ $worker->id }}">
                            <button type="button" class="btn cote-person-card w-100 text-start" wire:click="choose({{ $worker->id }})">
                                <span class="d-block fw-bold fs-5">{{ $worker->person?->full_name ?? $worker->name }}</span>
                                <span class="d-block {{ $s['class'] }}">{{ ucfirst($s['text']) }}</span>
                                <span class="d-block mt-2 fw-semibold text-warning-emphasis">
                                    <i class="fas fa-file-invoice-dollar me-1"></i> Registrar su rendición <i class="fas fa-chevron-right ms-1 small"></i>
                                </span>
                            </button>
                        </div>
                    @empty
                        <p class="text-muted fs-5">No encontré a nadie con ese nombre. Para agregar un trabajador, ve a <a href="{{ route('workers.index') }}">Trabajadores</a>.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        @php
            $viewerPhotos = collect($savedPhotos)->map(fn ($doc) => [
                'url' => route('documents.show', $doc), 'name' => $doc->name, 'image' => str_starts_with((string) $doc->mime_type, 'image/'),
                'remove' => 'removeSavedPhoto', 'key' => $doc->id,
            ])->concat(collect($photos)->map(function ($photo, $i) {
                $image = in_array(strtolower($photo->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp'], true);

                return ['url' => $image ? $photo->temporaryUrl() : null, 'name' => $photo->getClientOriginalName(), 'image' => $image, 'remove' => 'removeNewPhoto', 'key' => $i];
            }))->values();
            $s = $situation($account?->balance ?? 0);
            $after = (float) ($account?->balance ?? 0) - $total;
            $declared = $declaredTotal !== '' ? (int) $declaredTotal : null;
        @endphp

        {{-- Fotos de la rendición: quedan a la vista mientras se transcribe --}}
        <div class="card cote-intake-viewer mb-3" wire:key="viewer-{{ $viewerPhotos->count() }}"
             x-data="{ photos: @js($viewerPhotos), i: 0, zoom: 1, rot: 0, open: true }"
             x-init="i = Math.max(0, photos.length - 1)">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <strong class="me-2"><i class="fas fa-images me-1"></i> Fotos de la rendición</strong>
                <template x-if="photos.length">
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="i = (i + photos.length - 1) % photos.length; zoom = 1; rot = 0" :disabled="photos.length < 2" title="Foto anterior"><i class="fas fa-chevron-left"></i></button>
                        <span class="small mx-1" x-text="(i + 1) + ' de ' + photos.length"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="i = (i + 1) % photos.length; zoom = 1; rot = 0" :disabled="photos.length < 2" title="Foto siguiente"><i class="fas fa-chevron-right"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" @click="zoom = Math.max(0.5, zoom - 0.25)" title="Alejar"><i class="fas fa-search-minus"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="zoom = Math.min(4, zoom + 0.25)" title="Acercar"><i class="fas fa-search-plus"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="rot = (rot + 90) % 360" title="Girar"><i class="fas fa-redo"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" title="Quitar esta foto"
                                @click="coteConfirm('¿Quitar esta foto de la rendición?', { confirmText: 'Quitar' }).then((ok) => { if (ok) $wire.call(photos[i].remove, photos[i].key) })"><i class="fas fa-trash"></i></button>
                    </div>
                </template>
                <label class="btn btn-sm btn-primary ms-auto mb-0">
                    <i class="fas fa-camera me-1"></i> Agregar fotos
                    <input type="file" class="d-none" wire:model="newPhotos" multiple accept="image/jpeg,image/png,image/webp,application/pdf">
                </label>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="open = !open" x-text="open ? 'Ocultar' : 'Mostrar'"></button>
            </div>
            <div class="card-body p-0" x-show="open">
                <div wire:loading wire:target="newPhotos" class="p-3 text-muted"><i class="fas fa-spinner fa-spin me-1"></i> Subiendo fotos…</div>
                @error('newPhotos.*') <div class="alert alert-danger m-2 mb-0">{{ $message }}</div> @enderror
                @error('photos') <div class="alert alert-danger m-2 mb-0">{{ $message }}</div> @enderror
                <template x-if="!photos.length">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-camera fa-2x mb-2"></i>
                        <p class="mb-0 fs-5">Sube las fotos que mandó el trabajador: la planilla y las hojas con las boletas.</p>
                    </div>
                </template>
                <template x-if="photos.length">
                    <div class="cote-intake-stage">
                        <template x-if="photos[i].image">
                            <img :src="photos[i].url" :alt="photos[i].name" :style="`transform: rotate(${rot}deg) scale(${zoom}); transform-origin: top center;`">
                        </template>
                        <template x-if="!photos[i].image">
                            <div class="text-center py-5">
                                <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                <p class="mb-2" x-text="photos[i].name"></p>
                                <a x-show="photos[i].url" :href="photos[i].url" target="_blank" rel="noopener" class="btn btn-outline-primary">Abrir PDF</a>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="card cote-easy-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h3 class="h5 mb-0">
                    <i class="fas fa-file-invoice-dollar text-warning me-2"></i>
                    Rendición de <strong>{{ $personName }}</strong>
                    <span class="fw-normal {{ $s['class'] }}">({{ $s['text'] }})</span>
                </h3>
                @unless ($expenseId)
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="changeWorker">Cambiar trabajador</button>
                @endunless
            </div>
            <div class="card-body">
                @unless ($expenseId)
                    <ol class="cote-steps mb-3">
                        <li class="is-done">Trabajador</li>
                        <li class="is-current">Fotos y planilla</li>
                        <li>Listo</li>
                    </ol>
                @endunless
                <div class="row g-3 mb-3">
                    <div class="col-sm-4 col-lg-3">
                        <label class="form-label fw-semibold" for="intake-date">Fecha de rendición</label>
                        <input type="date" id="intake-date" class="form-control @error('date') is-invalid @enderror" wire:model.blur="date" max="{{ $today }}">
                        @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-8 col-lg-9">
                        <label class="form-label fw-semibold" for="intake-description">Observación <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" id="intake-description" class="form-control" wire:model.blur="description" maxlength="500" placeholder="Ej.: Gastos obra Lago Chapo, semana del 10 al 14">
                    </div>
                </div>

                <datalist id="intake-projects">@foreach ($projects as $p)<option value="{{ $p }}">@endforeach</datalist>
                <datalist id="intake-cities">@foreach ($cities as $c)<option value="{{ $c }}">@endforeach</datalist>
                <datalist id="intake-vendors">@foreach ($vendors as $v)<option value="{{ $v }}">@endforeach</datalist>

                <p class="text-muted small mb-2">
                    Copia cada línea de la planilla. <kbd>Enter</kbd> pasa al siguiente casillero; en el monto agrega otra línea con la misma fecha, proyecto y ciudad.
                </p>

                <div class="table-responsive cote-intake-grid"
                     x-data
                     @keydown.enter="if ($event.target.matches('input, select')) { $event.preventDefault(); const cells = [...$el.querySelectorAll('input, select')]; const k = cells.indexOf($event.target); if ($event.target.dataset.last !== undefined) { $event.target.blur(); $wire.addRow(); } else if (cells[k + 1]) { cells[k + 1].focus(); } }"
                     @intake-row-added.window="$nextTick(() => setTimeout(() => $el.querySelector(`[data-row='${$event.detail.index}'] select[data-first]`)?.focus(), 50))">
                    <table class="table table-sm align-middle mb-2">
                        <thead>
                            <tr>
                                <th style="width: 1.8rem;">#</th>
                                <th style="min-width: 8.8rem;">Fecha</th>
                                <th style="min-width: 8.2rem;">Concepto</th>
                                <th style="min-width: 7rem;">Proyecto</th>
                                <th style="min-width: 6.5rem;">Ciudad</th>
                                <th style="min-width: 6.5rem;">Documento</th>
                                <th style="min-width: 7.4rem;">Proveedor</th>
                                <th style="min-width: 5.5rem;">N°</th>
                                <th style="min-width: 6.3rem;" class="text-end">Monto</th>
                                <th style="width: 1.8rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                <tr wire:key="row-{{ $i }}" data-row="{{ $i }}">
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td>
                                        <input type="date" class="form-control form-control-sm @error("rows.$i.date") is-invalid @enderror" wire:model.blur="rows.{{ $i }}.date" max="{{ $today }}">
                                        @error("rows.$i.date") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <select data-first class="form-select form-select-sm @error("rows.$i.category_id") is-invalid @enderror" wire:model.change="rows.{{ $i }}.category_id">
                                            <option value="">Elige…</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("rows.$i.category_id") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm" list="intake-projects" wire:model.blur="rows.{{ $i }}.project" maxlength="150"></td>
                                    <td><input type="text" class="form-control form-control-sm" list="intake-cities" wire:model.blur="rows.{{ $i }}.city" maxlength="100"></td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model.change="rows.{{ $i }}.type">
                                            @foreach (\App\Livewire\ExpenseIntake::TYPES as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm @error("rows.$i.vendor") is-invalid @enderror" list="intake-vendors" wire:model.blur="rows.{{ $i }}.vendor" maxlength="255">
                                        @error("rows.$i.vendor") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm @error("rows.$i.number") is-invalid @enderror" wire:model.blur="rows.{{ $i }}.number" maxlength="100">
                                        @error("rows.$i.number") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <input type="text" inputmode="numeric" data-last class="form-control form-control-sm text-end @error("rows.$i.amount") is-invalid @enderror"
                                               wire:model.blur="rows.{{ $i }}.amount" x-on:input="$el.value = $el.value.replace(/\D/g, '')" placeholder="0">
                                        @error("rows.$i.amount") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" wire:click="removeRow({{ $i }})" title="Quitar esta línea" aria-label="Quitar esta línea">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('rows') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
                <button type="button" class="btn btn-outline-primary" wire:click="addRow"><i class="fas fa-plus me-1"></i> Agregar línea</button>

                <div class="row g-3 mt-2 align-items-stretch">
                    <div class="col-lg-6">
                        <div class="cote-confirm-box h-100">
                            <div class="d-flex justify-content-between"><span>Subtotal facturas</span><strong>{{ $money($subtotalFactura) }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Subtotal boletas y otros</span><strong>{{ $money($total - $subtotalFactura) }}</strong></div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fs-5"><span>Total</span><strong>{{ $money($total) }}</strong></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="cote-confirm-box h-100">
                            <label class="form-label fw-semibold mb-1" for="intake-declared">Total escrito en la planilla <span class="text-muted fw-normal">(para comprobar)</span></label>
                            <input type="text" inputmode="numeric" id="intake-declared" class="form-control form-control-lg text-end mb-2" wire:model.blur="declaredTotal"
                                   x-data x-on:input="$el.value = $el.value.replace(/\D/g, '')" placeholder="Ej.: 65110">
                            @if ($declared !== null)
                                @if ($declared === $total)
                                    <div class="text-success fw-semibold fs-5"><i class="fas fa-check-circle me-1"></i>¡Cuadra con la planilla!</div>
                                @else
                                    <div class="text-danger fw-semibold">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        {{ $declared > $total ? 'Faltan' : 'Sobran' }} {{ $money($declared - $total) }} respecto de la planilla. Revisa las líneas.
                                    </div>
                                @endif
                            @endif
                            @php $afterText = $sentence($firstName, $after, true); @endphp
                            <div class="mt-2">Al guardar, <strong class="{{ $afterText['class'] }}">{{ $afterText['text'] }}</strong>.</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="button" class="btn btn-success btn-lg" wire:click="save" wire:loading.attr="disabled" wire:target="save,newPhotos">
                        <span wire:loading.remove wire:target="save"><i class="fas fa-check me-1"></i> Guardar y aprobar</span>
                        <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> Guardando…</span>
                    </button>
                    <a href="{{ $expenseId ? route('expenses.show', $expenseId) : route('dashboard') }}" class="btn btn-outline-secondary btn-lg">Cancelar</a>
                </div>
            </div>
        </div>
    @endif
</div>
