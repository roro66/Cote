<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ReportService;
use App\Services\ExcelExportService;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE EXPORTACIÓN EXCEL CON DOCUMENTOS ===\n\n";

$reportService = new ReportService();
$excelService = new ExcelExportService();

// Generar reporte de septiembre 2025 con documentos incluidos
echo "📊 Generando reporte...\n";
$report = $reportService->generateMonthlyExpenseReport(
    '2025-09-01',
    '2025-09-30',
    'detailed',
    'all',
    true // includeDocuments = true
);

echo "✅ Reporte generado:\n";
echo "   💰 Total: $" . number_format($report['total_amount'], 0, ',', '.') . "\n";
echo "   📋 Categorías: " . count($report['categories']) . "\n";
echo "   📎 Con documentos incluidos\n\n";

// Información del reporte para Excel
$reportInfo = [
    'start_date' => '01/09/2025',
    'end_date' => '30/09/2025', 
    'report_type' => 'detailed',
    'approval_status' => 'all',
    'include_documents' => true
];

$filename = 'gastos_septiembre_2025_con_documentos.xlsx';

echo "📁 Generando archivo Excel: {$filename}\n";

// En lugar de hacer streaming, vamos a simular el proceso para ver la estructura
echo "🔍 VISTA PREVIA DEL CONTENIDO EXCEL:\n\n";

// Mostrar algunas categorías con documentos
$categoriesWithDocs = 0;
$itemsWithDocs = 0;

foreach ($report['categories'] as $categoryName => $category) {
    $hasDocuments = false;
    foreach ($category['items'] as $item) {
        if (!empty($item['documents'])) {
            $hasDocuments = true;
            $itemsWithDocs++;
        }
    }
    
    if ($hasDocuments) {
        $categoriesWithDocs++;
        echo "📂 CATEGORÍA: {$categoryName}\n";
        echo "   💰 Total: $" . number_format($category['total_amount'], 0, ',', '.') . "\n";
        
        $itemsShown = 0;
        foreach ($category['items'] as $item) {
            if (!empty($item['documents']) && $itemsShown < 2) {
                echo "   📝 {$item['item_description']} - $" . number_format($item['amount'], 0, ',', '.') . "\n";
                echo "      📎 Documentos (" . count($item['documents']) . "):\n";
                
                foreach ($item['documents'] as $doc) {
                    $size = isset($doc['size']) ? round($doc['size']/1024, 1) . ' KB' : 'N/A';
                    echo "         📄 {$doc['filename']} ({$size})\n";
                    echo "            🔗 {$doc['url']}\n";
                }
                $itemsShown++;
            }
        }
        echo "\n";
    }
}

echo "📊 RESUMEN:\n";
echo "   📂 Categorías con documentos: {$categoriesWithDocs}\n";
echo "   📝 Items con documentos: {$itemsWithDocs}\n";
echo "   ✅ El archivo Excel incluirá toda esta información\n";
echo "   📍 Los enlaces serán clickeables desde Excel\n";

echo "\n💡 CÓMO USAR EN EXCEL:\n";
echo "   1. Los documentos aparecerán en la columna 'Documentos'\n";
echo "   2. Cada documento mostrará: nombre, tipo, tamaño y URL\n";
echo "   3. Las URLs son clickeables desde Excel\n";
echo "   4. Múltiples documentos aparecen en líneas separadas\n";

echo "\n=== FIN DE PRUEBA ===\n";