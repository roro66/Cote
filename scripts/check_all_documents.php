<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExpenseItem;
use Carbon\Carbon;

echo "=== VERIFICACIÓN GENERAL DE DOCUMENTOS EN EL SISTEMA ===\n\n";

// Verificar si hay documentos en el sistema
$totalMedia = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', 'App\Models\ExpenseItem')->count();
echo "📊 Total de documentos en expense_items: {$totalMedia}\n\n";

if ($totalMedia > 0) {
    echo "🔍 DOCUMENTOS ENCONTRADOS:\n";
    $mediaItems = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', 'App\Models\ExpenseItem')
        ->with('model')
        ->take(10)
        ->get();
    
    foreach ($mediaItems as $media) {
        $item = $media->model;
        if ($item && $item->expense) {
            echo "   📄 {$media->file_name} (Colección: {$media->collection_name})\n";
            echo "      🗂️  Item: {$item->description} (ID: {$item->id})\n";
            echo "      💰 Monto: \${$item->amount}\n";
            echo "      📅 Fecha del gasto: {$item->expense->expense_date->format('Y-m-d')}\n";
            echo "      👤 Persona: {$item->expense->submittedBy->first_name} {$item->expense->submittedBy->last_name}\n";
            echo "      📊 Estado: {$item->expense->status}\n";
            echo "      🔗 URL: {$media->getFullUrl()}\n";
            echo "   ----------------------------------------\n";
        }
    }
    
    // Buscar gastos con documentos en septiembre 2025
    echo "\n📋 GASTOS CON DOCUMENTOS EN SEPTIEMBRE 2025:\n";
    $itemsWithDocsInSeptember = ExpenseItem::whereHas('media')
        ->whereHas('expense', function($query) {
            $query->whereBetween('expense_date', ['2025-09-01', '2025-09-30']);
        })
        ->with(['expense.submittedBy', 'media'])
        ->get();
    
    echo "Encontrados: {$itemsWithDocsInSeptember->count()}\n";
    
    foreach ($itemsWithDocsInSeptember as $item) {
        echo "   🗂️  Item: {$item->description} (ID: {$item->id})\n";
        echo "      💰 Monto: \${$item->amount}\n";
        echo "      📅 Fecha: {$item->expense->expense_date->format('Y-m-d')}\n";
        echo "      👤 Persona: {$item->expense->submittedBy->first_name} {$item->expense->submittedBy->last_name}\n";
        echo "      📎 Documentos: {$item->media->count()}\n";
        foreach ($item->media as $media) {
            echo "         📄 {$media->file_name} (Colección: {$media->collection_name})\n";
        }
        echo "   ----------------------------------------\n";
    }
    
} else {
    echo "❌ No hay documentos en el sistema\n";
    
    // Verificar si hay expense items sin documentos
    $totalItems = ExpenseItem::count();
    echo "📊 Total de expense_items en el sistema: {$totalItems}\n";
    
    if ($totalItems > 0) {
        echo "\n🔍 ALGUNOS EXPENSE ITEMS (sin documentos):\n";
        $items = ExpenseItem::with(['expense.submittedBy', 'categoryObj'])
            ->take(5)
            ->get();
        
        foreach ($items as $item) {
            echo "   📝 Item: {$item->description} (ID: {$item->id})\n";
            echo "      💰 Monto: \${$item->amount}\n";
            echo "      📅 Fecha: {$item->expense->expense_date->format('Y-m-d')}\n";
            echo "      👤 Persona: {$item->expense->submittedBy->first_name} {$item->expense->submittedBy->last_name}\n";
            echo "      🗂️  Categoría: " . ($item->categoryObj ? $item->categoryObj->name : 'Sin categoría') . "\n";
            echo "   ----------------------------------------\n";
        }
    }
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";