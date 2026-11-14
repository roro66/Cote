@php
    $money = fn ($value) => ($value < 0 ? '-$' : '$') . number_format(abs((float) $value), 0, ',', '.');
    $prev = $month->subMonthNoOverflow();
    $next = $month->addMonthNoOverflow();
    $isCurrent = $month->isSameMonth(now('America/Santiago'));
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <i class="fas fa-book"></i> Libro de caja
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 cote-easy">
            <div class="card cote-easy-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('cashbook.index', ['month' => $prev->format('Y-m')]) }}" class="btn btn-outline-secondary btn-lg" title="Mes anterior" aria-label="Mes anterior">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <h3 class="h3 mb-0 px-2 text-capitalize">{{ $month->translatedFormat('F Y') }}</h3>
                            @unless ($isCurrent)
                                <a href="{{ route('cashbook.index', ['month' => $next->format('Y-m')]) }}" class="btn btn-outline-secondary btn-lg" title="Mes siguiente" aria-label="Mes siguiente">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            @endunless
                        </div>
                        <a href="{{ route('cashbook.export', ['month' => $month->format('Y-m')]) }}" class="btn btn-success btn-lg">
                            <i class="fas fa-file-excel me-1"></i> Descargar Excel
                        </a>
                    </div>

                    <p class="text-muted fs-6 mb-4">
                        Aquí está todo el dinero que entró y salió de la caja en el mes.
                        Lo que cada trabajador gastó está en sus rendiciones.
                        <x-help text="Una entrega es dinero que sale de la caja hacia un trabajador. Una devolución es dinero que un trabajador devuelve a la caja. Un ingreso es dinero nuevo que entra a la caja." />
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-lg-3"><div class="cote-stat"><span>Al empezar el mes</span><strong>{{ $money($opening) }}</strong></div></div>
                        <div class="col-6 col-lg-3"><div class="cote-stat cote-stat--in"><span>Entró</span><strong>{{ $money($total_in) }}</strong></div></div>
                        <div class="col-6 col-lg-3"><div class="cote-stat cote-stat--out"><span>Salió</span><strong>{{ $money($total_out) }}</strong></div></div>
                        <div class="col-6 col-lg-3"><div class="cote-stat cote-stat--total"><span>{{ $isCurrent ? 'Hay ahora en la caja' : 'Quedó al terminar el mes' }}</span><strong>{{ $money($closing) }}</strong></div></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle cote-book">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Qué pasó</th>
                                    <th>Detalle</th>
                                    <th class="text-end">Entrada</th>
                                    <th class="text-end">Salida</th>
                                    <th class="text-end">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="fw-semibold">
                                    <td></td>
                                    <td colspan="4"><em>Saldo al empezar el mes</em></td>
                                    <td class="text-end fw-bold">{{ $money($opening) }}</td>
                                </tr>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td class="text-nowrap">{{ $row['date']->timezone('America/Santiago')->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($row['url'])
                                                <a href="{{ $row['url'] }}">{{ $row['kind'] }}</a>
                                            @else
                                                {{ $row['kind'] }}
                                            @endif
                                        </td>
                                        <td>{{ $row['detail'] }}</td>
                                        <td class="text-end text-success text-nowrap">{{ $row['in'] > 0 ? $money($row['in']) : '' }}</td>
                                        <td class="text-end text-danger text-nowrap">{{ $row['out'] > 0 ? $money($row['out']) : '' }}</td>
                                        <td class="text-end fw-semibold text-nowrap">{{ $money($row['balance']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">En este mes no entró ni salió dinero de la caja.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Totales del mes</td>
                                    <td class="text-end text-success text-nowrap">{{ $money($total_in) }}</td>
                                    <td class="text-end text-danger text-nowrap">{{ $money($total_out) }}</td>
                                    <td class="text-end text-nowrap">{{ $money($closing) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
