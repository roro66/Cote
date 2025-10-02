<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ReportService;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PRUEBA DE REPORTE CON DOCUMENTOS ===\n\n";

$reportService = new ReportService();

// Generar reporte de septiembre 2025 con documentos incluidos
$report = $reportService->generateMonthlyExpenseReport(
    '2025-09-01',
    '2025-09-30',
    'detailed',
    'all',
    true // includeDocuments = true
);

echo "📊 REPORTE GENERADO:\n";
echo "🔍 DEBUG - Claves del reporte: " . implode(', ', array_keys($report)) . "\n";

// Usar claves que sabemos que existen
if (isset($report['period'])) {
    echo "   📅 Período: " . (is_array($report['period']) ? json_encode($report['period']) : $report['period']) . "\n";
}
if (isset($report['total_amount'])) {
    echo "   💰 Total: $" . number_format($report['total_amount'], 0, ',', '.') . "\n";
}
if (isset($report['categories'])) {
    echo "   📋 Categorías: " . count($report['categories']) . "\n";
}
if (isset($report['include_documents'])) {
    echo "   📎 Documentos incluidos: " . ($report['include_documents'] ? 'SÍ' : 'NO') . "\n";
}
echo "\n";

// Verificar cada categoría y sus items
foreach ($report['categories'] as $categoryName => $categoryData) {
    echo "📂 CATEGORÍA: {$categoryName}\n";
    echo "   💰 Total: $" . number_format($categoryData['total_amount'], 0, ',', '.') . "\n";
    echo "   📝 Items: " . count($categoryData['items']) . "\n\n";
    
    foreach ($categoryData['items'] as $index => $item) {
        echo "      🔍 DEBUG Item {$index} - Claves: " . implode(', ', array_keys($item)) . "\n";
        
        if (isset($item['description'])) {
            echo "      📝 {$item['description']}\n";
        }
        if (isset($item['amount'])) {
            echo "         💰 Monto: $" . number_format($item['amount'], 0, ',', '.') . "\n";
        }
        if (isset($item['documents'])) {
            echo "         📎 Documentos: " . count($item['documents']) . "\n";
            
            foreach ($item['documents'] as $doc) {
                echo "            📄 {$doc['filename']} ({$doc['mime_type']}, " . number_format($doc['size']) . " B)\n";
                echo "               🔗 {$doc['url']}\n";
            }
        }
        echo "\n";
        
        // Solo mostrar los primeros 2 items para evitar spam
        if ($index >= 1) {
            echo "      ... (mostrando solo los primeros 2 items)\n\n";
            break;
        }
    }
}

echo "=== FIN DE PRUEBA ===\n";