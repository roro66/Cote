<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ReportService;
use App\Services\ExcelExportService;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== GENERACIÓN DE EXCEL PROFESIONAL CON DOCUMENTOS ===\n\n";

$reportService = new ReportService();
$excelService = new ExcelExportService();

// Generar reporte completo
echo "📊 Generando reporte completo de septiembre 2025...\n";
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

$filename = 'informe_gastos_septiembre_2025_profesional.xlsx';

echo "📁 Generando archivo Excel profesional: {$filename}\n";

try {
    // Usar el servicio mejorado para generar el streaming response
    // Pero lo vamos a capturar en un archivo
    $response = $excelService->exportMonthlyExpenseReport($report, $reportInfo, $filename);
    
    // Simular el streaming a un archivo
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();
    
    $filePath = __DIR__ . '/../public/' . $filename;
    file_put_contents($filePath, $content);
    
    echo "✅ Archivo Excel profesional generado!\n";
    echo "📁 Ubicación: {$filePath}\n";
    
    if (file_exists($filePath)) {
        $size = filesize($filePath);
        echo "📊 Tamaño: " . round($size/1024, 1) . " KB\n";
        
        // Contar elementos
        $documentCount = 0;
        $itemsWithDocs = 0;
        foreach ($report['categories'] as $category) {
            foreach ($category['items'] as $item) {
                if (!empty($item['documents'])) {
                    $itemsWithDocs++;
                    $documentCount += count($item['documents']);
                }
            }
        }
        
        echo "\n📋 CONTENIDO DEL ARCHIVO:\n";
        echo "   📊 Total general: $" . number_format($report['total_amount'], 0, ',', '.') . "\n";
        echo "   📂 Categorías: " . count($report['categories']) . "\n";
        echo "   📝 Items totales: " . $report['total_items'] . "\n";
        echo "   📎 Items con documentos: {$itemsWithDocs}\n";
        echo "   🗂️  Total documentos: {$documentCount}\n";
        
        echo "\n🎨 CARACTERÍSTICAS DEL FORMATO:\n";
        echo "   ✅ Títulos con formato profesional\n";
        echo "   ✅ Encabezados destacados en negrita\n";
        echo "   ✅ Categorías organizadas visualmente\n";
        echo "   ✅ Datos alineados correctamente\n";
        echo "   ✅ Totales destacados\n";
        echo "   ✅ Sección resumen de documentos\n";
        echo "   ✅ Estadísticas completas\n";
        echo "   ✅ Columnas con texto truncado para legibilidad\n";
        
        echo "\n🔗 ACCESO AL ARCHIVO:\n";
        echo "   URL: http://localhost/{$filename}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n💡 MEJORAS IMPLEMENTADAS:\n";
echo "   📐 Estructura organizada con secciones claras\n";
echo "   🎯 Información de documentos compacta pero completa\n";
echo "   📊 Estadísticas detalladas al final\n";
echo "   🔤 Texto truncado para evitar celdas muy anchas\n";
echo "   💼 Formato profesional para presentaciones\n";

echo "\n=== ARCHIVO LISTO PARA USO ===\n";