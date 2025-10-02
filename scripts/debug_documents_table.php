<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Expense;
use App\Models\Document;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE TABLA DOCUMENTS ===\n\n";

// Buscar la rendición específica
$expense = Expense::where('expense_number', 'RND-2025-000092')->first();

if (!$expense) {
    echo "❌ No se encontró la rendición\n";
    exit;
}

echo "✅ Rendición: {$expense->expense_number}\n";
echo "   Items: " . $expense->items->count() . "\n\n";

// Verificar TODA la tabla documents
echo "🔍 VERIFICACIÓN COMPLETA DE TABLA DOCUMENTS:\n";
$allDocuments = Document::all();
echo "Total de registros en tabla documents: " . $allDocuments->count() . "\n\n";

foreach ($allDocuments as $doc) {
    echo "📄 Document ID: {$doc->id}\n";
    echo "   🏷️  Nombre: {$doc->name}\n";
    echo "   📁 Archivo: {$doc->file_path}\n";
    echo "   🎯 Tipo: {$doc->document_type}\n";
    echo "   🔗 ExpenseItem ID: {$doc->expense_item_id}\n";
    echo "   💾 MIME: {$doc->mime_type}\n";
    echo "   📊 Tamaño: " . number_format($doc->file_size) . " B\n";
    echo "   🕒 Creado: {$doc->created_at}\n";
    
    // Verificar si pertenece a nuestra rendición
    if ($doc->expenseItem && $doc->expenseItem->expense_id == $expense->id) {
        echo "   ✅ PERTENECE A RND-2025-000092\n";
        echo "   📝 Item: {$doc->expenseItem->description}\n";
    } else {
        echo "   ❌ No pertenece a RND-2025-000092\n";
    }
    echo "\n";
}

// Verificar específicamente los items de nuestra rendición
echo "🔍 VERIFICACIÓN ESPECÍFICA DE DOCUMENTS POR ITEM:\n";
foreach ($expense->items as $item) {
    echo "📝 Item {$item->id}: {$item->description}\n";
    
    $documentsForItem = $item->documents;
    echo "   📎 Documents count: " . $documentsForItem->count() . "\n";
    
    foreach ($documentsForItem as $doc) {
        echo "      📄 {$doc->name} ({$doc->file_path})\n";
    }
    echo "\n";
}

echo "=== FIN DEBUG ===\n";