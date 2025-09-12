<div>
    {{-- Reuse the markup from expenses.show but only the inner content to be inserted into modal --}}

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Información General</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>Número de Rendición:</strong> {{ $expense->expense_number }}<br>
                    <strong>Estado:</strong> 
                    @php
                        $statusClass = match($expense->status) {
                            'draft' => 'bg-secondary',
                            'submitted' => 'bg-warning text-dark',
                            'reviewed' => 'bg-info',
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'paid' => 'bg-primary',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $expense->status_spanish }}</span><br>
                    <strong>Cuenta:</strong> {{ $expense->account->name ?? 'N/A' }}<br>
                    <strong>Referencia:</strong> {{ $expense->reference ?? 'Sin referencia' }}
                </div>
                <div class="col-md-6">
                    <strong>Solicitante:</strong> {{ $expense->submittedBy->full_name ?? 'N/A' }}<br>
                    <strong>Fecha de Envío:</strong> {{ $expense->submitted_at ? $expense->submitted_at->format('d/m/Y H:i') : 'Sin enviar' }}<br>
                    <strong>Total:</strong> <span class="text-primary fw-bold">${{ number_format($expense->total_amount, 0, ',', '.') }} CLP</span><br>
                    @if($expense->reviewed_by)
                        <strong>Revisado por:</strong> {{ $expense->reviewedBy->name ?? 'N/A' }}<br>
                        <strong>Fecha de Revisión:</strong> {{ $expense->reviewed_at ? $expense->reviewed_at->format('d/m/Y H:i') : '-' }}
                    @endif
                </div>
            </div>
            
            @if($expense->description)
                <div class="row mt-3">
                    <div class="col-12">
                        <strong>Descripción:</strong><br>
                        <div class="border rounded p-3 bg-light">{{ $expense->description }}</div>
                    </div>
                </div>
            @endif
            
            @if($expense->status === 'rejected' && $expense->rejection_reason)
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <strong>Motivo de Rechazo:</strong><br>
                            {{ $expense->rejection_reason }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Items de Gasto ({{ $expense->items->count() }})</h5>
        </div>
        <div class="card-body">
            @if($expense->items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Descripción</th>
                                <th>Tipo Documento</th>
                                <th>Proveedor</th>
                                <th>N° Documento</th>
                                <th>Monto</th>
                                <th>Adjuntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expense->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>
                                        @php
                                            $docType = match($item->document_type) {
                                                'boleta' => 'Boleta',
                                                'factura' => 'Factura',
                                                'guia_despacho' => 'Guía de Despacho',
                                                'ticket' => 'Ticket',
                                                'vale' => 'Vale',
                                                default => 'Otro'
                                            };
                                        @endphp
                                        {{ $docType }}
                                    </td>
                                    <td>{{ $item->vendor_name }}</td>
                                    <td>{{ $item->document_number ?: '-' }}</td>
                                    <td class="text-end">${{ number_format($item->amount, 0, ',', '.') }} CLP</td>
                                    <td>
                                        @if($item->documents && $item->documents->count())
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($item->documents as $doc)
                                                    @php
                                                        $url = asset('storage/' . $doc->file_path);
                                                        $isImage = str_starts_with($doc->mime_type, 'image/');
                                                    @endphp
                                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="Ver {{ $doc->name }}">
                                                        @if($isImage)
                                                            <img src="{{ $url }}" alt="{{ $doc->name }}" style="height:40px;width:auto;border-radius:4px;object-fit:cover;" />
                                                        @else
                                                            <i class="fas fa-file me-1"></i> {{ \Illuminate\Support\Str::limit($doc->name, 24) }}
                                                        @endif
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-info">
                                <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>${{ number_format($expense->total_amount, 0, ',', '.') }} CLP</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay items de gasto registrados.
                </div>
            @endif
        </div>
    </div>
</div>
