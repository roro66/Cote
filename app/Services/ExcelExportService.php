<?php

namespace App\Services;

use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row as SpoutRow;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Stream an XLSX with headings and rows.
     *
     * @param array<int,string> $headings
     * @param array<int,array<int,mixed>> $rows
     */
    public static function streamXlsx(string $filename, array $headings, array $rows): StreamedResponse
    {
        // Use Laravel's streamDownload to manage headers; write XLSX to php://output
        return response()->streamDownload(function () use ($headings, $rows) {
            $writer = new XlsxWriter();
            // Write directly to output; Laravel handles headers
            $writer->openToFile('php://output');

            // Headings
            $writer->addRow(SpoutRow::fromValues($headings));

            foreach ($rows as $row) {
                $writer->addRow(SpoutRow::fromValues($row));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Exportar informe de gastos mensuales a Excel
     */
    public function exportMonthlyExpenseReport(array $data, array $reportInfo, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($data, $reportInfo) {
            $writer = new XlsxWriter();
            $writer->openToFile('php://output');

            // Información del informe
            $writer->addRow(SpoutRow::fromValues(['INFORME DE GASTOS MENSUALES']));
            $writer->addRow(SpoutRow::fromValues([])); // Línea vacía
            $writer->addRow(SpoutRow::fromValues(['Período:', $reportInfo['start_date'] . ' al ' . $reportInfo['end_date']]));
            $writer->addRow(SpoutRow::fromValues(['Tipo:', $reportInfo['report_type'] === 'summary' ? 'Resumido' : 'Detallado']));
            $writer->addRow(SpoutRow::fromValues(['Estado:', $reportInfo['approval_status'] === 'approved_only' ? 'Solo aprobadas' : 'Todas las rendiciones']));
            $writer->addRow(SpoutRow::fromValues([])); // Línea vacía

            // Resumen general
            $writer->addRow(SpoutRow::fromValues(['RESUMEN GENERAL']));
            $writer->addRow(SpoutRow::fromValues(['Total General:', '$' . number_format($data['total_amount'], 2)]));
            $writer->addRow(SpoutRow::fromValues(['Total Rendiciones:', $data['total_expenses']]));
            $writer->addRow(SpoutRow::fromValues(['Total Items:', $data['total_items']]));
            $writer->addRow(SpoutRow::fromValues([])); // Línea vacía

            if ($data['report_type'] === 'summary') {
                // Informe resumido
                $writer->addRow(SpoutRow::fromValues(['RESUMEN POR CATEGORÍA']));
                $writer->addRow(SpoutRow::fromValues(['Categoría', 'Total', 'Items', 'Rendiciones']));

                foreach ($data['categories'] as $category) {
                    $writer->addRow(SpoutRow::fromValues([
                        $category['category'],
                        '$' . number_format($category['total_amount'], 2),
                        $category['items_count'],
                        $category['expenses_count']
                    ]));
                }
            } else {
                // Informe detallado
                $writer->addRow(SpoutRow::fromValues(['DETALLE POR CATEGORÍA']));
                $writer->addRow(SpoutRow::fromValues([])); // Línea vacía

                foreach ($data['categories'] as $category) {
                    $writer->addRow(SpoutRow::fromValues(['CATEGORÍA: ' . $category['category'] . ' - Total: $' . number_format($category['total_amount'], 2)]));
                    
                    // Encabezados para items
                    $headers = ['N° Rendición', 'Fecha', 'Persona', 'Descripción', 'Monto', 'N° Recibo', 'Estado'];
                    if ($reportInfo['include_documents'] ?? false) {
                        $headers[] = 'Documentos';
                    }
                    $writer->addRow(SpoutRow::fromValues($headers));

                    foreach ($category['items'] as $item) {
                        $row = [
                            $item['expense_number'],
                            $item['expense_date'],
                            $item['submitter'],
                            $item['item_description'],
                            '$' . number_format($item['amount'], 2),
                            $item['receipt_number'] ?? '',
                            $item['expense_status'] === 'approved' ? 'Aprobada' : 'Pendiente'
                        ];

                        if ($reportInfo['include_documents'] ?? false) {
                            $documents = [];
                            if (isset($item['documents']) && is_array($item['documents'])) {
                                foreach ($item['documents'] as $doc) {
                                    $documents[] = $doc['filename'];
                                }
                            }
                            $row[] = implode(', ', $documents);
                        }

                        $writer->addRow(SpoutRow::fromValues($row));
                    }

                    $writer->addRow(SpoutRow::fromValues([])); // Línea vacía entre categorías
                }
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
