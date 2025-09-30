<?php

namespace App\Services;

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
        $this->dataStyle = (new Style())
            ->setFontSize(10);

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
            $filename = $this->truncateText($doc['filename'], 30);
            $info = ($index + 1) . ". " . $filename;
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
     * Truncar texto para Excel
     */
    private function truncateText(string $text, int $length = 50): string
    {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
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
        $writer->addRow(SpoutRow::fromValues([])); // Línea vacía
        $writer->addRow(SpoutRow::fromValues([])); // Línea vacía
        $writer->addRow(SpoutRow::fromValues(['RESUMEN DE DOCUMENTOS ADJUNTOS'])->setStyle($this->titleStyle));
        $writer->addRow(SpoutRow::fromValues([])); // Línea vacía
        
        // Encabezados de la tabla de documentos con estilo
        $writer->addRow(SpoutRow::fromValues([
            'N° Rendición', 
            'Fecha', 
            'Persona', 
            'Descripción Item', 
            'Monto',
            'Archivo', 
            'Tipo', 
            'Tamaño', 
            'URL Descarga'
        ])->setStyle($this->headerStyle));

        $documentCount = 0;
        $totalWithDocs = 0;

        foreach ($data['categories'] as $category) {
            foreach ($category['items'] as $item) {
                if (!empty($item['documents'])) {
                    $totalWithDocs += $item['amount'];
                    
                    foreach ($item['documents'] as $doc) {
                        $documentCount++;
                        $writer->addRow(SpoutRow::fromValues([
                            $item['expense_number'],
                            $item['expense_date'],
                            $this->truncateText($item['submitter'], 25),
                            $this->truncateText($item['item_description'], 40),
                            '$' . $this->formatChileanNumber($item['amount']),
                            $this->truncateText($doc['filename'], 35),
                            $this->getFileTypeDescription($doc['mime_type'] ?? ''),
                            $this->formatFileSize($doc['size'] ?? 0),
                            $doc['url']
                        ])->setStyle($this->dataStyle));
                    }
                }
            }
        }

        $writer->addRow(SpoutRow::fromValues([])); // Línea vacía
        $writer->addRow(SpoutRow::fromValues(['ESTADÍSTICAS DE DOCUMENTOS'])->setStyle($this->categoryStyle));
        $writer->addRow(SpoutRow::fromValues(['Total de documentos adjuntos:', $documentCount])->setStyle($this->totalStyle));
        $writer->addRow(SpoutRow::fromValues(['Monto total con documentos:', '$' . $this->formatChileanNumber($totalWithDocs)])->setStyle($this->totalStyle));
        $writer->addRow(SpoutRow::fromValues(['Porcentaje del total general:', round(($totalWithDocs / $data['total_amount']) * 100, 1) . '%'])->setStyle($this->totalStyle));
        
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
        
        $writer->addRow(SpoutRow::fromValues(['Items con documentos:', $itemsWithDocs . ' de ' . $totalItems])->setStyle($this->totalStyle));
        $writer->addRow(SpoutRow::fromValues(['Cobertura de documentos:', round(($itemsWithDocs / $totalItems) * 100, 1) . '%'])->setStyle($this->totalStyle));
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

            // Título principal
            $writer->addRow(SpoutRow::fromValues(['INFORME DE GASTOS MENSUALES'])->setStyle($this->titleStyle));
            $writer->addRow(SpoutRow::fromValues([])); // Línea vacía
            
            // Información del informe con estilo
            $writer->addRow(SpoutRow::fromValues(['Período:', $reportInfo['start_date'] . ' al ' . $reportInfo['end_date']]));
            $writer->addRow(SpoutRow::fromValues(['Tipo:', $reportInfo['report_type'] === 'summary' ? 'Resumido' : 'Detallado']));
            $writer->addRow(SpoutRow::fromValues(['Estado:', $reportInfo['approval_status'] === 'approved_only' ? 'Solo aprobadas' : 'Todas las rendiciones']));
            if ($reportInfo['include_documents'] ?? false) {
                $writer->addRow(SpoutRow::fromValues(['Documentos:', 'Incluidos']));
            }
            $writer->addRow(SpoutRow::fromValues([])); // Línea vacía

            // Resumen general con estilos
            $writer->addRow(SpoutRow::fromValues(['RESUMEN GENERAL'])->setStyle($this->categoryStyle));
            $writer->addRow(SpoutRow::fromValues(['Total General:', '$' . $this->formatChileanNumber($data['total_amount'])])->setStyle($this->totalStyle));            
            $writer->addRow(SpoutRow::fromValues(['Total Rendiciones:', $data['total_expenses']]));
            $writer->addRow(SpoutRow::fromValues(['Total Items:', $data['total_items']]));
            $writer->addRow(SpoutRow::fromValues([])); // Línea vacía

            if ($data['report_type'] === 'summary') {
                // Informe resumido con estilos
                $writer->addRow(SpoutRow::fromValues(['RESUMEN POR CATEGORÍA'])->setStyle($this->categoryStyle));
                $writer->addRow(SpoutRow::fromValues(['Categoría', 'Total', 'Items', 'Rendiciones'])->setStyle($this->headerStyle));

                foreach ($data['categories'] as $category) {
                    $writer->addRow(SpoutRow::fromValues([
                        $category['category'],
                        '$' . $this->formatChileanNumber($category['total_amount']),
                        $category['items_count'],
                        $category['expenses_count']
                    ])->setStyle($this->dataStyle));
                }
            } else {
                // Informe detallado con estilos mejorados
                $writer->addRow(SpoutRow::fromValues(['DETALLE POR CATEGORÍA'])->setStyle($this->categoryStyle));
                $writer->addRow(SpoutRow::fromValues([])); // Línea vacía

                foreach ($data['categories'] as $categoryName => $category) {
                    // Título de categoría
                    $writer->addRow(SpoutRow::fromValues([
                        'CATEGORÍA: ' . $categoryName, 
                        '', 
                        '', 
                        '', 
                        'Total: $' . $this->formatChileanNumber($category['total_amount'])
                    ])->setStyle($this->categoryStyle));
                    
                    // Encabezados para items con estilo
                    $headers = ['N° Rendición', 'Fecha', 'Persona', 'Descripción', 'Monto', 'N° Recibo', 'Estado'];
                    if ($reportInfo['include_documents'] ?? false) {
                        $headers[] = 'Documentos';
                    }
                    $writer->addRow(SpoutRow::fromValues($headers)->setStyle($this->headerStyle));

                    foreach ($category['items'] as $item) {
                        $row = [
                            $item['expense_number'],
                            $item['expense_date'],
                            $item['submitter'],
                            $this->truncateText($item['item_description'], 50),
                            '$' . $this->formatChileanNumber($item['amount']),
                            $item['receipt_number'] ?? '-',
                            $item['expense_status'] === 'approved' ? 'Aprobada' : 'Pendiente'
                        ];

                        if ($reportInfo['include_documents'] ?? false) {
                            $row[] = $this->formatDocumentsForExcelCompact($item['documents'] ?? []);
                        }

                        $writer->addRow(SpoutRow::fromValues($row)->setStyle($this->dataStyle));
                    }

                    // Subtotal de categoría
                    $subtotalRow = ['', '', '', 'SUBTOTAL CATEGORÍA', '$' . $this->formatChileanNumber($category['total_amount']), '', ''];
                    if ($reportInfo['include_documents'] ?? false) {
                        $subtotalRow[] = '';
                    }
                    $writer->addRow(SpoutRow::fromValues($subtotalRow)->setStyle($this->totalStyle));
                    $writer->addRow(SpoutRow::fromValues([])); // Línea vacía entre categorías
                }

                // Si se incluyen documentos, agregar hoja resumen de documentos
                if ($reportInfo['include_documents'] ?? false) {
                    $this->addDocumentsSummary($writer, $data);
                }
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
