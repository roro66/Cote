@php
    $clp = fn ($n) => '$' . number_format((float) $n, 0, ',', '.');
    $docTypes = ['boleta' => 'Boleta', 'factura' => 'Factura', 'guia_despacho' => 'Guía de despacho', 'ticket' => 'Ticket', 'vale' => 'Vale'];
    $statusColor = match ($expense->status) {
        'approved' => '#198754',
        'rejected' => '#dc3545',
        'submitted' => '#b58100',
        default => '#6c757d',
    };
    $person = $expense->submittedBy;
    // Área útil de una carta con los márgenes de @page (pt)
    $maxWidth = 532;
    $maxHeight = 640;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Rendición {{ $expense->expense_number }}</title>
    <style>
        @page { margin: 40px 40px 55px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #212529; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 18px 0 6px; border-bottom: 1px solid #adb5bd; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 3px; color: #fff; font-weight: bold; font-size: 10px; }
        .info td { padding: 3px 4px; vertical-align: top; }
        .info .label { color: #6c757d; width: 22%; }
        .items th { background: #e9ecef; text-align: left; padding: 5px 4px; border: 1px solid #ced4da; font-size: 9px; }
        .items td { padding: 4px; border: 1px solid #ced4da; vertical-align: top; }
        .items .num { text-align: right; white-space: nowrap; }
        .items tfoot td { font-weight: bold; background: #f8f9fa; }
        .muted { color: #6c757d; }
        .box { border: 1px solid #ced4da; padding: 6px 8px; background: #f8f9fa; }
        .rejected { border-color: #dc3545; background: #f8d7da; }
        .signatures { margin-top: 60px; }
        .signatures td { width: 45%; text-align: center; padding-top: 4px; border-top: 1px solid #212529; }
        .signatures .gap { width: 10%; border: none; }
        .footer { position: fixed; bottom: -35px; left: 0; font-size: 8px; color: #6c757d; }
        .receipt { page-break-before: always; text-align: center; }
        .receipt .caption { text-align: left; font-weight: bold; margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="footer">Generado el {{ $generatedAt->format('d/m/Y H:i') }} · {{ config('app.name') }}</div>

    <table class="header">
        <tr>
            <td style="width: 45%;">
                @if ($logo)
                    <img src="{{ $logo['src'] }}" style="width: 170px; height: {{ round(170 * $logo['ratio']) }}px;" alt="">
                @endif
            </td>
            <td style="text-align: right;">
                <h1>Rendición de gastos</h1>
                <div style="font-size: 13px; margin: 3px 0 5px;">N° {{ $expense->expense_number }}</div>
                <span class="status" style="background: {{ $statusColor }};">{{ $expense->status_spanish }}</span>
            </td>
        </tr>
    </table>

    <h2>Información general</h2>
    <table class="info">
        <tr>
            <td class="label">Solicitante</td>
            <td>{{ $person?->full_name ?? 'N/A' }}@if ($person?->rut) · RUT {{ $person->rut }}@endif</td>
            <td class="label">Fecha de envío</td>
            <td>{{ $expense->submitted_at?->format('d/m/Y H:i') ?? 'Sin enviar' }}</td>
        </tr>
        <tr>
            <td class="label">Cuenta</td>
            <td>{{ $expense->account?->person?->full_name ?? $expense->account?->name ?? 'N/A' }}</td>
            <td class="label">Fecha del gasto</td>
            <td>{{ $expense->expense_date?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Revisado por</td>
            <td>{{ $expense->reviewedBy?->name ?? '—' }}</td>
            <td class="label">Fecha de revisión</td>
            <td>{{ $expense->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
    </table>

    @if ($expense->description)
        <h2>Descripción</h2>
        <div class="box">{{ $expense->description }}</div>
    @endif

    @if ($expense->status === 'rejected' && $expense->rejection_reason)
        <h2>Motivo del rechazo</h2>
        <div class="box rejected">{{ $expense->rejection_reason }}</div>
    @endif

    <h2>Ítems ({{ $expense->items->count() }})</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 10%;">Fecha</th>
                <th>Descripción</th>
                <th style="width: 15%;">Categoría</th>
                <th style="width: 15%;">Documento</th>
                <th style="width: 18%;">Comprobante</th>
                <th style="width: 12%;" class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($expense->items as $index => $item)
                @php $attached = $item->documents->where('is_enabled', true)->count(); @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->expense_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->categoryObj?->name ?? '—' }}</td>
                    <td>{{ $docTypes[$item->document_type] ?? 'Otro' }}@if ($item->document_number) N° {{ $item->document_number }}@endif</td>
                    <td>
                        @if ($attached)
                            {{ $attached === 1 ? '1 adjunto' : $attached . ' adjuntos' }}
                        @elseif ($item->without_receipt)
                            Sin comprobante<br><span class="muted">{{ $item->without_receipt_reason }}</span>
                        @else
                            <span style="color: #dc3545;">Falta comprobante</span>
                        @endif
                    </td>
                    <td class="num">{{ $clp($item->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align: right;">Total</td>
                <td class="num">{{ $clp($expense->total_amount) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signatures">
        <tr>
            <td>Rendido por<br><strong>{{ $person?->full_name ?? '' }}</strong></td>
            <td class="gap"></td>
            <td>Revisado por<br><strong>{{ $expense->reviewedBy?->name ?? '' }}</strong></td>
        </tr>
    </table>

    @php $withoutImage = collect($receipts)->whereNull('image'); @endphp
    @if ($withoutImage->isNotEmpty())
        <h2>Adjuntos que no se muestran en este PDF</h2>
        <p class="muted" style="margin: 0 0 4px;">Documentos PDF o archivos que no se pudieron leer; se ven desde la rendición en {{ config('app.name') }}.</p>
        @foreach ($withoutImage as $receipt)
            <div>Ítem {{ $receipt['item'] }}: {{ $receipt['name'] }}</div>
        @endforeach
    @endif

    @foreach (collect($receipts)->whereNotNull('image') as $receipt)
        @php
            $ratio = $receipt['image']['ratio'];
            $width = $ratio > $maxHeight / $maxWidth ? $maxHeight / $ratio : $maxWidth;
        @endphp
        <div class="receipt">
            <div class="caption">Comprobante del ítem {{ $receipt['item'] }} · {{ $receipt['name'] }}</div>
            <img src="{{ $receipt['image']['src'] }}" style="width: {{ round($width) }}pt; height: {{ round($width * $ratio) }}pt;" alt="">
        </div>
    @endforeach
</body>
</html>
