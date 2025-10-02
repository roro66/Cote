<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ReportService;
use App\Services\ExcelExportService;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== GENERACIÓN DE ARCHIVO EXCEL CON DOCUMENTOS ===\n\n";

$reportService = new ReportService();
$excelService = new ExcelExportService();

// Generar reporte
echo "📊 Generando datos del reporte...\n";
$report = $reportService->generateMonthlyExpenseReport(
    '2025-09-01',
    '2025-09-30',
    'detailed',
    'all',
    true // includeDocuments = true
);

// Información del reporte
$reportInfo = [
    'start_date' => '01/09/2025',
    'end_date' => '30/09/2025', 
    'report_type' => 'detailed',
    'approval_status' => 'all',
    'include_documents' => true
];

$filename = __DIR__ . '/../public/gastos_septiembre_2025_con_documentos.xlsx';

echo "📁 Generando archivo Excel en: {$filename}\n";

try {
    // Crear el archivo directamente usando la clase
    $writer = new \OpenSpout\Writer\XLSX\Writer();
    $writer->openToFile($filename);

    // Información del informe
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['INFORME DE GASTOS MENSUALES']));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([])); // Línea vacía
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Período:', $reportInfo['start_date'] . ' al ' . $reportInfo['end_date']]));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Tipo:', 'Detallado']));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Estado:', 'Todas las rendiciones']));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Documentos:', 'Incluidos']));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([])); // Línea vacía

    // Resumen general
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['RESUMEN GENERAL']));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total General:', '$' . number_format($report['total_amount'], 0, ',', '.')]));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total Rendiciones:', $report['total_expenses']]));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total Items:', $report['total_items']]));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([])); // Línea vacía

    // Solo mostrar categorías con documentos para la prueba
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['DETALLE DE ITEMS CON DOCUMENTOS']));
    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([])); // Línea vacía

    $categoriesProcessed = 0;
    foreach ($report['categories'] as $categoryName => $category) {
        $hasDocuments = false;
        foreach ($category['items'] as $item) {
            if (!empty($item['documents'])) {
                $hasDocuments = true;
                break;
            }
        }
        
        if ($hasDocuments && $categoriesProcessed < 3) { // Solo 3 categorías para prueba
            $categoriesProcessed++;
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['CATEGORÍA: ' . $categoryName]));
            
            // Encabezados
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'N° Rendición', 'Fecha', 'Persona', 'Descripción', 'Monto', 'N° Recibo', 'Estado', 'Documentos'
            ]));

            foreach ($category['items'] as $item) {
                if (!empty($item['documents'])) {
                    // Formatear documentos
                    $documentsInfo = [];
                    foreach ($item['documents'] as $doc) {
                        $size = isset($doc['size']) ? round($doc['size']/1024, 1) . ' KB' : '';
                        $type = isset($doc['mime_type']) ? match($doc['mime_type']) {
                            'image/jpeg', 'image/jpg' => 'JPG',
                            'image/png' => 'PNG',
                            'application/pdf' => 'PDF',
                            default => 'Archivo'
                        } : '';
                        
                        $info = $doc['filename'];
                        if ($type) $info .= " ({$type})";
                        if ($size) $info .= " - {$size}";
                        $info .= " - URL: " . $doc['url'];
                        
                        $documentsInfo[] = $info;
                    }

                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                        $item['expense_number'],
                        $item['expense_date'],
                        $item['submitter'],
                        $item['item_description'],
                        '$' . number_format($item['amount'], 0, ',', '.'),
                        $item['receipt_number'] ?? '',
                        $item['expense_status'] === 'approved' ? 'Aprobada' : 'Pendiente',
                        implode("\n", $documentsInfo)
                    ]));
                }
            }
            
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([])); // Línea vacía
        }
    }

    $writer->close();
    
    echo "✅ Archivo Excel generado exitosamente!\n";
    echo "📁 Ubicación: {$filename}\n";
    
    // Verificar el archivo
    if (file_exists($filename)) {
        $size = filesize($filename);
        echo "📊 Tamaño del archivo: " . round($size/1024, 1) . " KB\n";
        echo "🔍 El archivo contiene:\n";
        echo "   - Información general del reporte\n";
        echo "   - Items con documentos adjuntos\n";
        echo "   - URLs clickeables a cada documento\n";
        echo "   - Información de tipo y tamaño de archivos\n";
    }

} catch (Exception $e) {
    echo "❌ Error generando Excel: " . $e->getMessage() . "\n";
}

echo "\n💡 INSTRUCCIONES DE USO:\n";
echo "   1. Descarga el archivo desde /tmp/gastos_septiembre_2025_con_documentos.xlsx\n";
echo "   2. Abre con Excel o LibreOffice\n";
echo "   3. Los enlaces en la columna 'Documentos' son clickeables\n";
echo "   4. Cada documento muestra: nombre, tipo, tamaño y URL\n";

echo "\n=== FIN DE GENERACIÓN ===\n";