@php
    $money = fn ($value) => ($value < 0 ? '-' : '') . '$' . number_format(abs((float) $value), 0, ',', '.');
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <i class="fas fa-file-excel"></i> Importar trabajadores desde Excel
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 cote-easy">
            <a href="{{ route('workers.index') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left me-1"></i> Volver a Trabajadores
            </a>

            @if (! $analysis)
                <div class="card cote-easy-card">
                    <div class="card-body">
                        <ol class="fs-5 mb-4">
                            <li class="mb-2">
                                Descarga la planilla modelo y copia ahí a tus trabajadores, o usa tu propia planilla:
                                COTE reconoce títulos como «Nombre», «RUT», «Banco» o «N° de cuenta».
                            </li>
                            <li class="mb-2">Sube el archivo. COTE te muestra cada fila para revisarla <strong>antes de guardar</strong>.</li>
                            <li>Si todo se ve bien, pulsa <strong>Importar</strong>.</li>
                        </ol>

                        <a href="{{ route('workers.import.template') }}" class="btn btn-outline-success btn-lg mb-4">
                            <i class="fas fa-download me-1"></i> Descargar planilla modelo
                        </a>

                        <form method="POST" action="{{ route('workers.import.preview') }}" enctype="multipart/form-data">
                            @csrf
                            <label for="archivo" class="form-label fs-5 fw-semibold">Tu archivo (Excel .xlsx, CSV u OpenOffice .ods)</label>
                            <input type="file" id="archivo" name="archivo" accept=".xlsx,.csv,.ods"
                                class="form-control form-control-lg @error('archivo') is-invalid @enderror" required>
                            @error('archivo')
                                <div class="invalid-feedback d-block fs-6">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-primary btn-lg mt-3">
                                <i class="fas fa-search me-1"></i> Revisar archivo
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card cote-easy-card mb-3">
                    <div class="card-body">
                        <h3 class="h5">Revisión de «{{ $fileName }}»</h3>

                        @if ($analysis['error'])
                            <div class="alert alert-danger fs-5 mb-0">{{ $analysis['error'] }}</div>
                        @else
                            <div class="d-flex flex-wrap gap-3 my-3">
                                <div class="cote-stat cote-stat--in"><span>Nuevos</span><strong>{{ $analysis['summary']['nuevos'] }}</strong></div>
                                <div class="cote-stat"><span>Ya están en COTE (se actualizan)</span><strong>{{ $analysis['summary']['actualizar'] }}</strong></div>
                                <div class="cote-stat cote-stat--owed"><span>Filas con problemas (no se importan)</span><strong>{{ $analysis['summary']['errores'] }}</strong></div>
                            </div>
                            <p class="text-muted mb-1">
                                Columnas reconocidas: {{ implode(', ', array_filter($analysis['columns'])) }}.
                                @if ($analysis['ignored'])
                                    <br>Columnas que no se usan: {{ implode(', ', $analysis['ignored']) }}.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                @unless ($analysis['error'])
                    <div class="card mb-3">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Fila</th>
                                        <th>Qué pasará</th>
                                        <th>Nombre</th>
                                        <th>RUT</th>
                                        <th>Correo y teléfono</th>
                                        <th>Cuenta bancaria</th>
                                        <th class="text-end">Saldo inicial</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($analysis['rows'] as $row)
                                        <tr @class(['cote-row-error' => $row['status'] === 'error'])>
                                            <td>{{ $row['line'] }}</td>
                                            <td class="text-nowrap">
                                                @if ($row['status'] === 'error')
                                                    <span class="badge bg-danger">No se importa</span>
                                                @elseif (! empty($row['extra']))
                                                    <span class="badge bg-info text-dark">Otra cuenta</span>
                                                @elseif ($row['status'] === 'nuevo')
                                                    <span class="badge bg-success">Nuevo</span>
                                                @else
                                                    <span class="badge bg-secondary">Se actualiza</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $row['first_name'] }} <strong>{{ $row['last_name'] }}</strong>
                                                @if (! $row['first_name'] && ! $row['last_name'] && $row['name'])
                                                    {{ $row['name'] }}
                                                @endif
                                            </td>
                                            <td class="text-nowrap">{{ $row['rut_formatted'] }}</td>
                                            <td class="small">{{ $row['email'] }}@if ($row['email'] && $row['phone'])<br>@endif{{ $row['phone'] }}</td>
                                            <td class="small">
                                                @if ($row['bank_id'])
                                                    {{ $row['bank_name'] }}<br>{{ $row['account_type_name'] ?? 'Sin tipo' }} N° {{ $row['account_number'] }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">
                                                @if ($row['balance'] !== null && empty($row['extra']))
                                                    {{ $money($row['balance']) }}
                                                    <div class="small text-muted">{{ $row['balance'] > 0 ? 'en su poder' : ($row['balance'] < 0 ? 'le debemos' : 'al día') }}</div>
                                                @endif
                                            </td>
                                            <td class="small">
                                                @foreach ($row['errors'] as $message)
                                                    <div class="text-danger fw-semibold"><i class="fas fa-times-circle me-1"></i>{{ $message }}</div>
                                                @endforeach
                                                @foreach ($row['warnings'] as $message)
                                                    <div class="text-warning-emphasis"><i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}</div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endunless

                @php($toImport = $analysis['error'] ? 0 : $analysis['summary']['nuevos'] + $analysis['summary']['actualizar'])
                <div class="d-flex flex-wrap gap-2">
                    @if ($toImport > 0)
                        <form method="POST" action="{{ route('workers.import.store') }}"
                            data-cote-confirm="Se importarán {{ $toImport }} {{ $toImport === 1 ? 'trabajador' : 'trabajadores' }}. ¿Continuar?" data-cote-confirm-text="Importar">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check me-1"></i> Importar {{ $toImport }} {{ $toImport === 1 ? 'trabajador' : 'trabajadores' }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('workers.import') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-redo me-1"></i> Elegir otro archivo
                    </a>
                </div>
                @if ($toImport > 0 && $analysis['summary']['errores'] > 0)
                    <p class="text-muted mt-2">Las filas en rojo no se importan. Puedes corregirlas en tu Excel y subirlo de nuevo: los que ya se importaron solo se actualizan.</p>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
