<?php

namespace App\Services;

use App\Support\XlsxColumnWidths;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Common\Entity\Row as SpoutRow;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\CellAlignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    private Style $headerStyle;
    private Style $titleStyle;
    private Style $categoryStyle;
    private Style $dataStyle;
    private Style $totalStyle;
    private XlsxColumnWidths $widths;

    public function __construct()
    {
        $this->initializeStyles();
    }

    /**
     * Inicializar estilos para Excel
     */
    private function initializeStyles(): void
    {
        // Estilo para títulos principales
        $this->titleStyle = (new Style())
            ->setFontBold()
            ->setFontSize(16);

        // Estilo para encabezados de tabla
        $this->headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(11);

        // Estilo para categorías
        $this->categoryStyle = (new Style())
            ->setFontBold()
            ->setFontSize(13);

        // Estilo para datos
        // Con ajuste de texto para que los documentos (varias líneas) se vean uno bajo otro
        $this->dataStyle = (new Style())
            ->setFontSize(10)
            ->setShouldWrapText();

        // Estilo para totales
        $this->totalStyle = (new Style())
            ->setFontBold()
            ->setFontSize(11);
    }

    /**
     * Formatear número según notación chilena
     */
    private function formatChileanNumber($number): string
    {
        // Convertir a float y redondear según reglas chilenas
        $num = is_numeric($number) ? (float) $number : 0;
        
        // Redondear: >=0.5 hacia arriba, <=0.4 hacia abajo
        $rounded = (int) round($num);
        
        // Formatear con puntos como separadores de miles
        return number_format($rounded, 0, ',', '.');
    }

    /**
     * Formatear información de documentos para Excel (versión compacta)
     */
    private function formatDocumentsForExcelCompact(array $documents): string
    {
        if (empty($documents)) {
            return 'Sin documentos';
        }

        $documentInfo = [];
        foreach ($documents as $index => $doc) {
            $size = isset($doc['size']) ? $this->formatFileSize($doc['size']) : '';
            $type = isset($doc['mime_type']) ? $this->getFileTypeDescription($doc['mime_type']) : '';
            
            // Formato más compacto
            $info = ($index + 1) . ". " . $doc['filename'];
            if ($type) {
                $info .= " ({$type})";
            }
            if ($size) {
                $info .= " - {$size}";
            }
            
            $documentInfo[] = $info;
        }

        return implode("\n", $documentInfo);
    }

    /**
     * Formatear información de documentos para Excel (versión completa)
     */
    private function formatDocumentsForExcel(array $documents): string
    {
        if (empty($documents)) {
            return 'Sin documentos';
        }

        $documentInfo = [];
        foreach ($documents as $index => $doc) {
            $size = isset($doc['size']) ? $this->formatFileSize($doc['size']) : '';
            $type = isset($doc['mime_type']) ? $this->getFileTypeDescription($doc['mime_type']) : '';
            
            $info = ($index + 1) . ". " . $doc['filename'];
            if ($type) {
                $info .= " ({$type})";
            }
            if ($size) {
                $info .= " - {$size}";
            }
            $info .= "\nURL: " . $doc['url'];
            
            $documentInfo[] = $info;
        }

        return implode("\n\n", $documentInfo);
    }

    /**
     * Escribe una fila y registra su contenido para el ancho de las columnas.
     * Los títulos de sección ($fitColumns = false) no ensanchan la columna A.
     *
     * @param array<int,mixed> $values
     */
    private function addRow(XlsxWriter $writer, array $values, ?Style $style = null, bool $fitColumns = true): void
    {
        if ($fitColumns) {
            $this->widths->track($values, $style);
        }

        $row = SpoutRow::fromValues($values);
        if ($style !== null) {
            $row->setStyle($style);
        }
        $writer->addRow($row);
    }

    /**
     * Formatear tamaño de archivo
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /**
     * Obtener descripción del tipo de archivo
     */
    private function getFileTypeDescription(string $mimeType): string
    {
        return match($mimeType) {
            'image/jpeg', 'image/jpg' => 'Imagen JPG',
            'image/png' => 'Imagen PNG',
            'image/gif' => 'Imagen GIF',
            'application/pdf' => 'PDF',
            'text/plain' => 'Texto',
            default => 'Archivo'
        };
    }

    /**
     * Agregar sección resumen de documentos al Excel
     */
    private function addDocumentsSummary($writer, array $data): void
    {
        $this->addRow($writer, []); // Línea vacía
        $this->addRow($writer, []); // Línea vacía
        $this->addRow($writer, ['RESUMEN DE DOCUMENTOS ADJUNTOS'], $this->titleStyle, false);
        $this->addRow($writer, []); // Línea vacía
        
        // Encabezados de la tabla de documentos con estilo
        $this->addRow($writer, [
            'N° Rendición', 
            'Fecha', 
            'Persona', 
            'Descripción Item', 
            'Monto',
            'Archivo', 
            'Tipo', 
            'Tamaño', 
            'URL Descarga'
        ], $this->headerStyle);

        $documentCount = 0;
        $totalWithDocs = 0;

        foreach ($data['categories'] as $category) {
            foreach ($category['items'] as $item) {
                if (!empty($item['documents'])) {
                    $totalWithDocs += $item['amount'];
                    
                    foreach ($item['documents'] as $doc) {
                        $documentCount++;
                        $this->addRow($writer, [
                            $item['expense_number'],
                            $item['expense_date'],
                            $item['submitter'],
                            $item['item_description'],
                            '$' . $this->formatChileanNumber($item['amount']),
                            $doc['filename'],
                            $this->getFileTypeDescription($doc['mime_type'] ?? ''),
                            $this->formatFileSize($doc['size'] ?? 0),
                            $doc['url']
                        ], $this->dataStyle);
                    }
                }
            }
        }

        $this->addRow($writer, []); // Línea vacía
        $this->addRow($writer, ['ESTADÍSTICAS DE DOCUMENTOS'], $this->categoryStyle, false);
        $this->addRow($writer, ['Total de documentos adjuntos:', $documentCount], $this->totalStyle);
        $this->addRow($writer, ['Monto total con documentos:', '$' . $this->formatChileanNumber($totalWithDocs)], $this->totalStyle);
        $this->addRow($writer, ['Porcentaje del total general:', round(($totalWithDocs / $data['total_amount']) * 100, 1) . '%'], $this->totalStyle);
        
        // Estadísticas adicionales
        $itemsWithDocs = 0;
        $totalItems = 0;
        foreach ($data['categories'] as $category) {
            foreach ($category['items'] as $item) {
                $totalItems++;
                if (!empty($item['documents'])) {
                    $itemsWithDocs++;
                }
            }
        }
        
        $this->addRow($writer, ['Items con documentos:', $itemsWithDocs . ' de ' . $totalItems], $this->totalStyle);
        $this->addRow($writer, ['Cobertura de documentos:', round(($itemsWithDocs / $totalItems) * 100, 1) . '%'], $this->totalStyle);
    }
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
            $widths = new XlsxColumnWidths();

            $widths->track($headings);
            $writer->addRow(SpoutRow::fromValues($headings));

            foreach ($rows as $row) {
                $widths->track($row);
                $writer->addRow(SpoutRow::fromValues($row));
            }

            $widths->applyTo($writer->getCurrentSheet());
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
            $this->widths = new XlsxColumnWidths();

            // Título principal
            $this->addRow($writer, ['INFORME DE GASTOS MENSUALES'], $this->titleStyle, false);
            $this->addRow($writer, []); // Línea vacía
            
            // Información del informe con estilo
            $this->addRow($writer, ['Período:', $reportInfo['start_date'] . ' al ' . $reportInfo['end_date']]);
            $this->addRow($writer, ['Tipo:', $reportInfo['report_type'] === 'summary' ? 'Resumido' : 'Detallado']);
            $this->addRow($writer, ['Estado:', $reportInfo['approval_status'] === 'approved_only' ? 'Solo aprobadas' : 'Todas las rendiciones']);
            if ($reportInfo['include_documents'] ?? false) {
                $this->addRow($writer, ['Documentos:', 'Incluidos']);
            }
            $this->addRow($writer, []); // Línea vacía

            // Resumen general con estilos
            $this->addRow($writer, ['RESUMEN GENERAL'], $this->categoryStyle, false);
            $this->addRow($writer, ['Total General:', '$' . $this->formatChileanNumber($data['total_amount'])], $this->totalStyle);            
            $this->addRow($writer, ['Total Rendiciones:', $data['total_expenses']]);
            $this->addRow($writer, ['Total Items:', $data['total_items']]);
            $this->addRow($writer, []); // Línea vacía

            if ($data['report_type'] === 'summary') {
                // Informe resumido con estilos
                $this->addRow($writer, ['RESUMEN POR CATEGORÍA'], $this->categoryStyle, false);
                $this->addRow($writer, ['Categoría', 'Total', 'Items', 'Rendiciones'], $this->headerStyle);

                foreach ($data['categories'] as $category) {
                    $this->addRow($writer, [
                        $category['category'],
                        '$' . $this->formatChileanNumber($category['total_amount']),
                        $category['items_count'],
                        $category['expenses_count']
                    ], $this->dataStyle);
                }
            } else {
                // Informe detallado con estilos mejorados
                $this->addRow($writer, ['DETALLE POR CATEGORÍA'], $this->categoryStyle, false);
                $this->addRow($writer, []); // Línea vacía

                foreach ($data['categories'] as $categoryName => $category) {
                    // Título de categoría
                    $this->addRow($writer, [
                        'CATEGORÍA: ' . $categoryName,
                        '',
                        '',
                        '',
                        'Total: $' . $this->formatChileanNumber($category['total_amount'])
                    ], $this->categoryStyle, false);
                    
                    // Encabezados para items con estilo
                    $headers = ['N° Rendición', 'Fecha', 'Persona', 'Descripción', 'Monto', 'N° Recibo', 'Estado'];
                    if ($reportInfo['include_documents'] ?? false) {
                        $headers[] = 'Documentos';
                    }
                    $this->addRow($writer, $headers, $this->headerStyle);

                    foreach ($category['items'] as $item) {
                        $row = [
                            $item['expense_number'],
                            $item['expense_date'],
                            $item['submitter'],
                            $item['item_description'],
                            '$' . $this->formatChileanNumber($item['amount']),
                            $item['receipt_number'] ?? '-',
                            $item['expense_status'] === 'approved' ? 'Aprobada' : 'Pendiente'
                        ];

                        if ($reportInfo['include_documents'] ?? false) {
                            $row[] = $this->formatDocumentsForExcelCompact($item['documents'] ?? []);
                        }

                        $this->addRow($writer, $row, $this->dataStyle);
                    }

                    // Subtotal de categoría
                    $subtotalRow = ['', '', '', 'SUBTOTAL CATEGORÍA', '$' . $this->formatChileanNumber($category['total_amount']), '', ''];
                    if ($reportInfo['include_documents'] ?? false) {
                        $subtotalRow[] = '';
                    }
                    $this->addRow($writer, $subtotalRow, $this->totalStyle);
                    $this->addRow($writer, []); // Línea vacía entre categorías
                }

                // Si se incluyen documentos, agregar hoja resumen de documentos
                if ($reportInfo['include_documents'] ?? false) {
                    $this->addDocumentsSummary($writer, $data);
                }
            }

            $this->widths->applyTo($writer->getCurrentSheet());
            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
