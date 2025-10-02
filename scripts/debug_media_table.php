<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Expense;
use App\Models\ExpenseItem;

// Configurar la aplicación Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG COMPLETO DE TABLA MEDIA ===\n\n";

// Buscar la rendición específica
$expense = Expense::where('expense_number', 'RND-2025-000092')->first();

if (!$expense) {
    echo "❌ No se encontró la rendición\n";
    exit;
}

echo "✅ Rendición: {$expense->expense_number}\n";
echo "   Items: " . $expense->items->count() . "\n\n";

// Verificar TODA la tabla media
echo "🔍 VERIFICACIÓN COMPLETA DE TABLA MEDIA:\n";
$allMedia = \DB::table('media')->get();
echo "Total de registros en tabla media: " . $allMedia->count() . "\n\n";

foreach ($allMedia as $media) {
    echo "📄 Media ID: {$media->id}\n";
    echo "   🏷️  Nombre: {$media->name}\n";
    echo "   📁 Archivo: {$media->file_name}\n";
    echo "   🗂️  Colección: {$media->collection_name}\n";
    echo "   🎯 Modelo: {$media->model_type}\n";
    echo "   🔗 Model ID: {$media->model_id}\n";
    echo "   💾 Disco: {$media->disk}\n";
    echo "   📊 Tamaño: " . number_format($media->size) . " B\n";
    echo "   🕒 Creado: {$media->created_at}\n";
    
    // Si es ExpenseItem, mostrar detalles del item
    if ($media->model_type === 'App\\Models\\ExpenseItem') {
        $item = ExpenseItem::find($media->model_id);
        if ($item) {
            echo "   📝 Item: {$item->description} (Expense ID: {$item->expense_id})\n";
            
            // Verificar si pertenece a nuestra rendición
            if ($item->expense_id == $expense->id) {
                echo "   ✅ PERTENECE A RND-2025-000092\n";
            } else {
                echo "   ❌ No pertenece a RND-2025-000092 (pertenece a expense {$item->expense_id})\n";
            }
        }
    }
    echo "\n";
}

// Verificar específicamente los items de nuestra rendición
echo "🔍 VERIFICACIÓN ESPECÍFICA DE ITEMS DE LA RENDICIÓN:\n";
foreach ($expense->items as $item) {
    echo "📝 Item {$item->id}: {$item->description}\n";
    
    // Consulta directa a la tabla media
    $mediaForItem = \DB::table('media')
        ->where('model_type', 'App\\Models\\ExpenseItem')
        ->where('model_id', $item->id)
        ->get();
    
    echo "   📎 Media directo en BD: " . $mediaForItem->count() . "\n";
    foreach ($mediaForItem as $media) {
        echo "      📄 {$media->file_name} (Colección: {$media->collection_name})\n";
    }
    
    // Método Spatie
    echo "   📎 getMedia('receipts'): " . $item->getMedia('receipts')->count() . "\n";
    echo "   📎 getMedia(): " . $item->getMedia()->count() . "\n";
    
    // hasMedia
    echo "   📎 hasMedia('receipts'): " . ($item->hasMedia('receipts') ? 'SÍ' : 'NO') . "\n";
    
    echo "\n";
}

echo "=== FIN DEBUG ===\n";