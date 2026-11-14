@php
    $money = fn ($value) => '$' . number_format(abs((float) $value), 0, ',', '.');
    $situation = function ($balance) use ($money) {
        $balance = (float) $balance;
        return match (true) {
            $balance > 0 => 'tendrá ' . $money($balance) . ' en su poder',
            $balance < 0 => 'le deberemos ' . $money($balance),
            default => 'quedará al día',
        };
    };
@endphp
<div class="cote-easy">
    @if (! $current)
        <div class="card cote-easy-card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-double fa-4x text-success mb-3"></i>
                @if ($remaining > 0)
                    <h3>Saltaste las {{ $remaining }} que quedan</h3>
                    <button type="button" class="btn btn-primary btn-lg mt-3" wire:click="restartSkipped">Volver a verlas</button>
                @else
                    <h3>No hay rendiciones por revisar</h3>
                    <p class="fs-5 text-muted">Cuando un trabajador envíe una, aparecerá aquí.</p>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg mt-2"><i class="fas fa-home me-1"></i> Volver al inicio</a>
                @endif
            </div>
        </div>
    @else
        @php
            $balance = (float) ($current->account?->balance ?? 0);
            $after = $balance - (float) $current->total_amount;
            $images = $current->items->flatMap(fn ($item) => $item->documents->map(fn ($doc) => ['doc' => $doc, 'item' => $item]));
        @endphp
        <p class="fs-5 mb-3">Quedan <strong>{{ $remaining }}</strong> por revisar.</p>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card cote-easy-card h-100">
                    <div class="card-header"><strong>Boletas y comprobantes</strong></div>
                    <div class="card-body">
                        @forelse ($images as $entry)
                            @php $doc = $entry['doc']; @endphp
                            <div class="mb-3" wire:key="doc-{{ $doc->id }}">
                                <div class="small text-muted mb-1">{{ $entry['item']->description }} · {{ $money($entry['item']->amount) }}</div>
                                @if (str_starts_with((string) $doc->mime_type, 'image/'))
                                    <a href="{{ $doc->url }}" target="_blank" rel="noopener">
                                        <img src="{{ $doc->url }}" alt="Comprobante: {{ $doc->name }}" class="img-fluid rounded border cote-receipt">
                                    </a>
                                @else
                                    <a href="{{ $doc->url }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-lg">
                                        <i class="fas fa-file-pdf me-1"></i> Abrir {{ $doc->name }}
                                    </a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted">Esta rendición no trae fotos.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card cote-easy-card">
                    <div class="card-body">
                        <h3 class="h4 mb-1">{{ $current->submittedBy?->full_name }}</h3>
                        <p class="text-muted mb-2">{{ $current->expense_number }} · enviada el {{ $current->submitted_at?->format('d/m/Y') }}</p>
                        <p class="fs-5 mb-2">{{ $current->description }}</p>
                        <p class="fs-3 fw-bold mb-3">{{ $money($current->total_amount) }}</p>

                        <ul class="list-group mb-3">
                            @foreach ($current->items as $item)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span>{{ $item->description }}<br><small class="text-muted">{{ $item->vendor_name }} · {{ $item->expense_date?->format('d/m/Y') }}{{ $item->categoryObj ? ' · ' . $item->categoryObj->name : '' }}</small></span>
                                        <strong class="text-nowrap">{{ $money($item->amount) }}</strong>
                                    </div>
                                    @if ($item->without_receipt)
                                        <span class="badge text-bg-warning mt-1">Sin boleta: {{ $item->without_receipt_reason }}</span>
                                    @elseif ($item->documents->isEmpty())
                                        <span class="badge text-bg-danger mt-1">Falta la boleta</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <p class="mb-4">Si la apruebas, {{ $current->submittedBy?->first_name ?? 'el trabajador' }} <strong>{{ $situation($after) }}</strong>.</p>

                        @if (! $returning)
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success btn-lg py-3" wire:click="approve({{ $current->id }})" wire:loading.attr="disabled">
                                    <i class="fas fa-check me-1"></i> Está bien, aprobar
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-lg" wire:click="startReturn">
                                    <i class="fas fa-reply me-1"></i> Devolver para corregir
                                </button>
                                <button type="button" class="btn btn-link" wire:click="skip({{ $current->id }})">Ver la siguiente (revisar esta después)</button>
                            </div>
                        @else
                            <p class="fs-5 mb-2">¿Qué debe corregir?</p>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @foreach ($reasons as $option)
                                    <button type="button" class="btn btn-outline-secondary" wire:click="addReason(@js($option))">{{ $option }}</button>
                                @endforeach
                            </div>
                            <textarea class="form-control form-control-lg mb-2 @error('reason') is-invalid @enderror" rows="3" maxlength="500"
                                      wire:model="reason" placeholder="O escribe con tus palabras"></textarea>
                            @error('reason') <p class="text-danger mb-2">{{ $message }}</p> @enderror
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-danger btn-lg" wire:click="sendBack({{ $current->id }})">
                                    <i class="fas fa-reply me-1"></i> Devolver
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-lg" wire:click="$set('returning', false)">Cancelar</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
