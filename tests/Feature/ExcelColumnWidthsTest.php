<?php

use App\Services\ExcelExportService;
use App\Support\XlsxColumnWidths;
use OpenSpout\Reader\XLSX\Reader;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @return array<int,float> columna (1..n) => ancho
 */
function xlsxColumnWidths(StreamedResponse $response): array
{
    ob_start();
    $response->sendContent();
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, ob_get_clean());

    $reader = new Reader();
    $reader->open($path);
    $widths = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getColumnWidths() as $columnWidth) {
            for ($column = $columnWidth->start; $column <= $columnWidth->end; $column++) {
                $widths[$column] = $columnWidth->width;
            }
        }
        break;
    }
    $reader->close();
    unlink($path);

    return $widths;
}

it('sizes simple exports to the longest value of each column', function () {
    $response = ExcelExportService::streamXlsx('gasto.xlsx', ['Mes', 'Total (CLP)'], [
        ['septiembre 2026', 1234567890],
        ['oct', 5],
    ]);

    $widths = xlsxColumnWidths($response);

    expect($widths[1])->toBe(XlsxColumnWidths::widthFor(mb_strlen('septiembre 2026')))
        ->and($widths[2])->toBe(XlsxColumnWidths::widthFor(mb_strlen('1.234.567.890')));
});

it('sizes the detailed report to its data without truncating it', function () {
    $description = 'Compra de materiales de oficina para la sucursal de Puerto Montt, incluye despacho';
    $data = [
        'report_type' => 'detailed',
        'total_amount' => 15000,
        'total_expenses' => 1,
        'total_items' => 1,
        'categories' => [
            'Oficina' => [
                'category' => 'Oficina',
                'total_amount' => 15000,
                'items' => [[
                    'expense_number' => 'REN-2026-000001',
                    'expense_date' => '2026-10-01',
                    'submitter' => 'María José Fernández Riquelme',
                    'item_description' => $description,
                    'amount' => 15000,
                    'receipt_number' => null,
                    'expense_status' => 'approved',
                ]],
            ],
        ],
    ];
    $info = ['start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'report_type' => 'detailed', 'approval_status' => 'all'];

    $widths = xlsxColumnWidths((new ExcelExportService())->exportMonthlyExpenseReport($data, $info, 'informe.xlsx'));

    expect($widths[4])->toBeGreaterThanOrEqual(mb_strlen($description))
        ->and($widths[3])->toBeGreaterThanOrEqual(mb_strlen('María José Fernández Riquelme'))
        // El título "INFORME DE GASTOS MENSUALES" no ensancha la columna del N° de rendición
        ->and($widths[1])->toBeLessThan(XlsxColumnWidths::widthFor(mb_strlen('INFORME DE GASTOS MENSUALES') * 16 / 11 * 1.1));
});
