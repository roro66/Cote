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
                    <div class="text-muted small">En poder de trabajadores</div>
                    <div class="fs-4 fw-semibold">{{ $clp($totals['to_render']) }}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card h-100"><div class="card-body">
                    <div class="text-muted small">Les debemos</div>
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
                                                <span class="badge bg-warning text-dark">En su poder</span>
                                            @elseif ($row['type'] === 'person' && $row['closing'] < 0)
                                                <span class="badge bg-danger">Le debemos</span>
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
                                {{ abs($difference) < 1 ? 'Cuadra.' : ($difference < 0 ? 'Faltaron ' : 'Sobraron ') . $clp(abs($difference)) . '.' }}
                            </p>
                            @if ($closing->count_detail)
                                <p class="small text-muted mb-1">
                                    @foreach ($closing->count_detail['billetes_y_monedas'] ?? [] as $value => $qty)
                                        {{ $qty }} × ${{ number_format((int) $value, 0, ',', '.') }}{{ $loop->last ? '' : ' · ' }}
                                    @endforeach
                                    @if (($closing->count_detail['banco'] ?? 0) > 0)
                                        · Banco {{ $clp($closing->count_detail['banco']) }}
                                    @endif
                                </p>
                            @endif
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
                            <p class="text-muted">Cuenta el dinero que hay en la caja y en el banco. La app suma y te dice si cuadra con el sistema. Al cerrar se guarda este resumen tal como está.</p>
                            <form method="POST" action="{{ route('closings.store') }}" class="cote-easy"
                                  data-cote-confirm="¿Cerrar {{ $monthLabel }}?"
                                  data-cote-confirm-text="Cerrar mes"
                                  x-data="{
                                      qty: {{ json_encode(array_fill_keys(\App\Models\PeriodClosing::DENOMINATIONS, '')) }},
                                      bank: '',
                                      direct: '',
                                      system: {{ (float) $totals['treasury_closing'] }},
                                      get counted() { return Object.entries(this.qty).reduce((s, [v, q]) => s + Number(v) * Number(q || 0), 0) + Number(this.bank || 0) },
                                      get usingCount() { return Object.values(this.qty).some(q => Number(q) > 0) || this.bank !== '' },
                                      get total() { return this.usingCount ? this.counted : (this.direct === '' ? null : Number(this.direct)) },
                                      get diff() { return this.total === null ? null : this.total - this.system },
                                      clp(v) { return (v < 0 ? '-$' : '$') + Math.abs(Math.round(v)).toLocaleString('es-CL') },
                                  }">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">

                                <h6 class="fw-bold mt-2">Paso 1: cuenta los billetes y monedas de la caja</h6>
                                <div class="row g-2 cote-cash-count mb-3">
                                    @foreach (\App\Models\PeriodClosing::DENOMINATIONS as $value)
                                        <div class="col-6 col-md-4 col-xl-3">
                                            <div class="input-group">
                                                <span class="input-group-text" style="min-width: 6.5rem;">{{ $value >= 1000 ? 'Billete' : 'Moneda' }} ${{ number_format($value, 0, ',', '.') }}</span>
                                                <input type="number" min="0" step="1" class="form-control" name="count[{{ $value }}]" x-model="qty[{{ $value }}]" placeholder="0" aria-label="Cantidad de {{ $value >= 1000 ? 'billetes' : 'monedas' }} de ${{ number_format($value, 0, ',', '.') }}">
                                            </div>
                                            <div class="small text-muted ms-1" x-show="Number(qty[{{ $value }}]) > 0" x-text="'= ' + clp({{ $value }} * Number(qty[{{ $value }}] || 0))"></div>
                                        </div>
                                    @endforeach
                                </div>

                                <h6 class="fw-bold">Paso 2: ¿cuánto hay en el banco? (si la caja tiene cuenta)</h6>
                                <div class="input-group mb-3" style="max-width: 18rem;">
                                    <span class="input-group-text">$</span>
                                    <input type="number" min="0" step="1" class="form-control" name="bank" x-model="bank" placeholder="0" aria-label="Dinero en el banco">
                                </div>

                                <details class="mb-3">
                                    <summary class="text-muted">Prefiero escribir el total contado de una vez</summary>
                                    <div class="input-group mt-2" style="max-width: 18rem;">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="1" class="form-control" name="treasury_counted" x-model="direct" :disabled="usingCount" placeholder="Total contado">
                                    </div>
                                </details>

                                <h6 class="fw-bold">Paso 3: revisa y cierra</h6>
                                <div class="cote-confirm-box mb-3">
                                    <p class="fs-5 mb-1">Según el sistema hay <strong>{{ $clp($totals['treasury_closing']) }}</strong>.</p>
                                    <template x-if="total !== null">
                                        <div>
                                            <p class="fs-5 mb-1">Contaste <strong x-text="clp(total)"></strong>.</p>
                                            <p class="fs-5 mb-0 fw-bold" :class="Math.abs(diff) < 1 ? 'text-success' : 'text-danger'"
                                               x-text="Math.abs(diff) < 1 ? '¡Cuadra!' : (diff < 0 ? 'Faltan ' + clp(-diff) : 'Sobran ' + clp(diff))"></p>
                                        </div>
                                    </template>
                                    <p class="text-muted mb-0" x-show="total === null">Si no cuentas el dinero, el mes se cierra sin arqueo.</p>
                                </div>

                                <label for="notes" class="form-label">Observaciones (por ejemplo, por qué no cuadra)</label>
                                <textarea id="notes" name="notes" class="form-control mb-3" rows="2" maxlength="2000"></textarea>

                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-lock me-1"></i>Cerrar mes</button>
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
