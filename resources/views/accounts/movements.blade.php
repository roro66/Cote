@php
    $clp = fn ($amount) => '$' . number_format($amount, 0, ',', '.');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Movimientos: {{ $account->person?->full_name ?? $account->name }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-3 d-flex justify-content-between">
                <a href="{{ route('accounts.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Volver a Cuentas
                </a>
                <a href="{{ route('accounts.movements.export', $account) }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i>Exportar cartola a Excel
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body">
                        <div class="text-muted small">Saldo actual</div>
                        <div class="fs-4 fw-semibold">{{ $account->balance_abs_formatted }}
                            <span class="badge fs-6 align-middle {{ $account->balance_status['class'] }}">{{ $account->balance_status['label'] }}</span>
                        </div>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body">
                        <div class="text-muted small">Ingresos registrados</div>
                        <div class="fs-4 fw-semibold text-success">{{ $clp($total_in) }}</div>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body">
                        <div class="text-muted small">Egresos registrados</div>
                        <div class="fs-4 fw-semibold text-danger">{{ $clp($total_out) }}</div>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 {{ abs($opening_balance) >= 0.01 ? 'border-warning' : '' }}"><div class="card-body">
                        <div class="text-muted small">Saldo inicial sin movimiento que lo respalde</div>
                        <div class="fs-4 fw-semibold">{{ $clp($opening_balance) }}</div>
                    </div></div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4">
                    @if ($movements->isEmpty())
                        <p class="text-muted mb-0">Esta cuenta no tiene movimientos aprobados.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Referencia</th>
                                        <th>Detalle</th>
                                        <th class="text-end">Ingreso</th>
                                        <th class="text-end">Egreso</th>
                                        <th class="text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($movements as $movement)
                                        <tr>
                                            <td class="text-nowrap">{{ $movement['date']?->format('d/m/Y H:i') }}</td>
                                            <td><span class="badge bg-secondary">{{ $movement['kind'] }}</span></td>
                                            <td class="text-nowrap">
                                                @if ($movement['url'])
                                                    <a href="{{ $movement['url'] }}">{{ $movement['reference'] }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $movement['detail'] }}</td>
                                            <td class="text-end text-success">{{ $movement['in'] > 0 ? $clp($movement['in']) : '' }}</td>
                                            <td class="text-end text-danger">{{ $movement['out'] > 0 ? $clp($movement['out']) : '' }}</td>
                                            <td class="text-end fw-semibold">{{ $clp($movement['balance_after']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
