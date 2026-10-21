@php
    $clp = fn ($n) => ($n < 0 ? '-$' : '$') . number_format(abs((float) $n), 0, ',', '.');
    $rows = collect($report['rows']);
    $totals = $report['totals'];
    $treasuryRow = $rows->firstWhere('type', 'treasury');
    $monthLabel = ucfirst($month->translatedFormat('F Y'));
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cierre de mes</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('closings.index') }}" class="d-flex flex-wrap align-items-end gap-2 mb-3">
                <div>
                    <label for="month" class="form-label mb-1">Mes</label>
                    <input type="month" id="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}">
                </div>
                <button type="submit" class="btn btn-secondary">Ver</button>
                <a href="{{ route('closings.export', ['month' => $month->format('Y-m')]) }}" class="btn btn-success"
                   title="{{ $closing ? 'Resumen guardado al cerrar el mes' : 'Resumen provisorio: el mes aún no está cerrado' }}">
                    <i class="fas fa-file-excel me-1"></i>Exportar a Excel
                </a>
                <div class="ms-auto">
                    @if ($closing)
                        <span class="badge bg-success fs-6"><i class="fas fa-lock me-1"></i>Cerrado</span>
                        <span class="small text-muted">por {{ $closing->closedBy?->name }} el {{ $closing->closed_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="badge bg-secondary fs-6"><i class="fas fa-lock-open me-1"></i>Abierto</span>
                    @endif
                </div>
            </form>

            <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="card h-100"><div class="card-body">
                    <div class="text-muted small">Tesorería al cierre</div>
                    <div class="fs-4 fw-semibold">{{ $clp($totals['treasury_closing']) }}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body">
                    <div class="text-muted small">Por rendir (trabajadores)</div>
                    <div class="fs-4 fw-semibold">{{ $clp($totals['to_render']) }}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body">
                    <div class="text-muted small">Se les debe</div>
                    <div class="fs-4 fw-semibold">{{ $clp($totals['owed']) }}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body">
                    <div class="text-muted small">Rendido en el mes</div>
                    <div class="fs-4 fw-semibold">{{ $clp($totals['rendered']) }}</div>
                </div></div></div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-4">
                    <h5 class="mb-1">{{ $monthLabel }}</h5>
                    <p class="small text-muted">Cada movimiento cuenta en la fecha en que se aprobó. Entradas: ingresos, entregas recibidas y devoluciones recibidas. Salidas: entregas y devoluciones hechas.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Cuenta</th>
                                    <th class="text-end">Saldo inicial</th>
                                    <th class="text-end">Entradas</th>
                                    <th class="text-end">Salidas</th>
                                    <th class="text-end">Rendido</th>
                                    <th class="text-end">Ajustes</th>
                                    <th class="text-end">Saldo final</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr @class(['fw-semibold' => $row['type'] === 'treasury'])>
                                        <td>
                                            @if (! $closing)
                                                <a href="{{ route('accounts.movements', $row['account_id']) }}">{{ $row['name'] }}</a>
                                            @else
                                                {{ $row['name'] }}
                                            @endif
                                            @if ($row['type'] === 'treasury')
                                                <span class="badge bg-primary">Tesorería</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $clp($row['opening']) }}</td>
                                        <td class="text-end text-success">{{ $row['in'] ? $clp($row['in']) : '' }}</td>
                                        <td class="text-end text-danger">{{ $row['out'] ? $clp($row['out']) : '' }}</td>
                                        <td class="text-end text-danger">{{ $row['rendered'] ? $clp($row['rendered']) : '' }}</td>
                                        <td class="text-end">{{ $row['adjustments'] ? $clp($row['adjustments']) : '' }}</td>
                                        <td class="text-end">
                                            {{ $clp($row['closing']) }}
                                            @if ($row['type'] === 'person' && $row['closing'] > 0)
                                                <span class="badge bg-warning text-dark">Por rendir</span>
                                            @elseif ($row['type'] === 'person' && $row['closing'] < 0)
                                                <span class="badge bg-danger">Se le debe</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted">Sin movimientos.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($closing)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="p-4">
                        <h5>Arqueo de Tesorería</h5>
                        @if ($closing->treasury_counted !== null)
                            @php $difference = (float) $closing->treasury_counted - (float) $totals['treasury_closing']; @endphp
                            <p class="mb-1">Según sistema: <strong>{{ $clp($totals['treasury_closing']) }}</strong> · Contado: <strong>{{ $clp($closing->treasury_counted) }}</strong></p>
                            <p @class(['mb-1', 'text-success' => abs($difference) < 1, 'text-danger' => abs($difference) >= 1])>
                                {{ abs($difference) < 1 ? 'Cuadra.' : 'Diferencia: ' . $clp($difference) }}
                            </p>
                        @else
                            <p class="text-muted mb-1">No se registró arqueo.</p>
                        @endif
                        @if ($closing->notes)
                            <p class="mb-0"><strong>Observaciones:</strong> {{ $closing->notes }}</p>
                        @endif

                        @role('boss')
                            @if ($history->first()?->is($closing))
                                <form method="POST" action="{{ route('closings.destroy', $closing) }}" class="mt-3"
                                      data-cote-confirm="¿Reabrir {{ $monthLabel }}? El resumen guardado se descartará."
                                      data-cote-confirm-text="Reabrir">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-lock-open me-1"></i>Reabrir mes</button>
                                </form>
                            @endif
                        @endrole
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="p-4">
                        <h5>Cerrar {{ $monthLabel }}</h5>
                        @if ($closeBlocker)
                            <p class="text-muted mb-0">{{ $closeBlocker }}</p>
                        @else
                            <p class="small text-muted">Cuenta el dinero que hay en Tesorería (caja y banco) y regístralo para comparar con el sistema. Al cerrar se guarda este resumen tal como está.</p>
                            <form method="POST" action="{{ route('closings.store') }}" class="row g-2"
                                  data-cote-confirm="¿Cerrar {{ $monthLabel }}?"
                                  data-cote-confirm-text="Cerrar mes">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                                <div class="col-md-4">
                                    <label for="treasury_counted" class="form-label">Dinero contado en Tesorería</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="1" class="form-control" id="treasury_counted" name="treasury_counted"
                                               placeholder="{{ number_format($totals['treasury_closing'], 0, '', '') }}">
                                    </div>
                                    <div class="form-text">Según sistema: {{ $clp($totals['treasury_closing']) }}</div>
                                </div>
                                <div class="col-md-8">
                                    <label for="notes" class="form-label">Observaciones</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-lock me-1"></i>Cerrar mes</button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            @if ($history->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4">
                        <h5>Meses cerrados</h5>
                        <ul class="list-unstyled mb-0">
                            @foreach ($history as $item)
                                <li>
                                    <a href="{{ route('closings.index', ['month' => $item->period->format('Y-m')]) }}">{{ ucfirst($item->period->translatedFormat('F Y')) }}</a>
                                    <span class="small text-muted">— {{ $item->closedBy?->name }}, {{ $item->closed_at->format('d/m/Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
