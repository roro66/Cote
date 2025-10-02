<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Expense;
use App\Models\ExpenseItem;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE RENDICIÓN RND-2025-000092 ===\n\n";

// Buscar la rendición específica
$expense = Expense::where('expense_number', 'RND-2025-000092')->first();

if (!$expense) {
    echo "❌ No se encontró la rendición RND-2025-000092\n";
    
    // Mostrar las rendiciones disponibles
    echo "\n🔍 Rendiciones disponibles:\n";
    $expenses = Expense::orderBy('expense_date', 'desc')->take(10)->get();
    foreach ($expenses as $exp) {
        echo "   📋 {$exp->expense_number} - {$exp->expense_date} - " . ($exp->person->name ?? 'Sin persona') . "\n";
    }
    exit;
}

echo "✅ Encontrada rendición: {$expense->expense_number}\n";
echo "   📅 Fecha: {$expense->expense_date}\n";
echo "   👤 Rendido por: " . ($expense->submittedBy ? $expense->submittedBy->name : 'Sin persona') . "\n";
echo "   📊 Estado: {$expense->status}\n";
echo "   💰 Total: $" . number_format($expense->total_amount, 0, ',', '.') . "\n";
echo "   🧾 Items: " . $expense->items->count() . "\n\n";

echo "📋 DETALLE DE ITEMS:\n";
foreach ($expense->items as $item) {
    echo "   📝 Item {$item->id}: {$item->description}\n";
    echo "      💰 Monto: $" . number_format($item->amount, 0, ',', '.') . "\n";
    echo "      🗂️  Categoría: {$item->category}\n";
    
    // Verificar documentos en colección 'receipts'
    $receiptsCount = $item->getMedia('receipts')->count();
    echo "      📎 Documentos (receipts): {$receiptsCount}\n";
    
    if ($receiptsCount > 0) {
        foreach ($item->getMedia('receipts') as $media) {
            echo "         📄 {$media->file_name} ({$media->mime_type}, " . number_format($media->size) . " B)\n";
            echo "         🔗 URL: " . $media->getUrl() . "\n";
        }
    }
    
    // Verificar TODOS los documentos (todas las colecciones)
    $allMedia = $item->getMedia();
    $allMediaCount = $allMedia->count();
    echo "      📎 Total documentos (todas las colecciones): {$allMediaCount}\n";
    
    // Debug: mostrar la diferencia
    if ($receiptsCount != $allMediaCount) {
        echo "      ⚠️  INCONSISTENCIA: receipts={$receiptsCount}, total={$allMediaCount}\n";
        echo "         Esto podría indicar un problema en el método getMedia()\n";
    }
    
    if ($allMediaCount > 0) {
        echo "         🗂️  Detalle por colección:\n";
        $collections = $allMedia->groupBy('collection_name');
        foreach ($collections as $collectionName => $mediaItems) {
            echo "            📁 {$collectionName}: " . $mediaItems->count() . " documentos\n";
            foreach ($mediaItems as $media) {
                echo "               📄 {$media->file_name} ({$media->mime_type})\n";
            }
        }
    }
    
    echo "\n";
}

// Verificar directamente en la tabla media
echo "🔍 VERIFICACIÓN DIRECTA EN TABLA MEDIA:\n";
$allMediaForExpense = \DB::table('media')
    ->join('expense_items', 'media.model_id', '=', 'expense_items.id')
    ->where('media.model_type', 'App\\Models\\ExpenseItem')
    ->where('expense_items.expense_id', $expense->id)
    ->select('media.*', 'expense_items.description as item_description')
    ->get();

echo "Media encontrados para esta rendición: " . $allMediaForExpense->count() . "\n";
foreach ($allMediaForExpense as $media) {
    echo "   📄 {$media->file_name} (ID: {$media->id}, Colección: {$media->collection_name})\n";
    echo "      🗂️  Item: {$media->item_description}\n";
    echo "      📊 Tamaño: " . number_format($media->size) . " B\n";
    echo "      🔗 Disco: {$media->disk}\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";