<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Person;
use App\Models\Expense;
use App\Models\ExpenseItem;
use Carbon\Carbon;

echo "=== VERIFICACIÓN DE DOCUMENTOS PARA ABAGAIL EBERT ORTIZ ===\n\n";

// Buscar a Abagail Ebert Ortiz
$abigail = Person::where('first_name', 'LIKE', '%Abagail%')
    ->where('last_name', 'LIKE', '%Ebert%')
    ->first();

if (!$abigail) {
    echo "❌ No se encontró exactamente 'Abagail Ebert'. Buscando nombres similares...\n";
    
    // Buscar por Abagail
    $abigails = Person::where('first_name', 'LIKE', '%Abagail%')->get();
    echo "Personas con 'Abagail': " . $abigails->count() . "\n";
    foreach ($abigails as $person) {
        echo "   👤 {$person->first_name} {$person->last_name} (ID: {$person->id})\n";
    }
    
    // Buscar por Ebert
    $eberts = Person::where('last_name', 'LIKE', '%Ebert%')->get();
    echo "Personas con 'Ebert': " . $eberts->count() . "\n";
    foreach ($eberts as $person) {
        echo "   👤 {$person->first_name} {$person->last_name} (ID: {$person->id})\n";
    }
    
    // Buscar cualquier persona que haya rendido gastos en septiembre 2025
    echo "\n📋 PERSONAS QUE RINDIERON GASTOS EN SEPTIEMBRE 2025:\n";
    $expensesInSeptember = Expense::with('submittedBy')
        ->whereBetween('expense_date', ['2025-09-01', '2025-09-30'])
        ->get();
    
    $peopleWhoSubmitted = $expensesInSeptember->pluck('submittedBy')->unique('id');
    foreach ($peopleWhoSubmitted as $person) {
        if ($person) {
            echo "   👤 {$person->first_name} {$person->last_name} (ID: {$person->id})\n";
        }
    }
    
    // Tomar la primera persona que encontremos para continuar el análisis
    if ($peopleWhoSubmitted->isNotEmpty()) {
        $abigail = $peopleWhoSubmitted->first();
        echo "\n🔍 Continuando análisis con: {$abigail->first_name} {$abigail->last_name}\n";
    } else {
        echo "\n❌ No hay gastos en septiembre 2025\n";
        exit;
    }
}

echo "✅ Encontrada: {$abigail->first_name} {$abigail->last_name} (ID: {$abigail->id})\n\n";

// Buscar sus gastos en septiembre 2025
$expenses = Expense::with(['items.media', 'submittedBy'])
    ->where('submitted_by', $abigail->id)
    ->whereBetween('expense_date', ['2025-09-01', '2025-09-30'])
    ->get();

echo "📋 GASTOS DE SEPTIEMBRE 2025:\n";
echo "Total de gastos encontrados: " . $expenses->count() . "\n\n";

foreach ($expenses as $expense) {
    echo "🗂️  GASTO: {$expense->expense_number}\n";
    echo "   📅 Fecha: {$expense->expense_date->format('Y-m-d')}\n";
    echo "   👤 Rendido por: {$expense->submittedBy->first_name} {$expense->submittedBy->last_name}\n";
    echo "   📊 Estado: {$expense->status}\n";
    echo "   🧾 Items: {$expense->items->count()}\n";
    
    foreach ($expense->items as $item) {
        echo "      📝 Item {$item->id}: {$item->description}\n";
        echo "         💰 Monto: \${$item->amount}\n";
        echo "         🗂️  Categoría: " . ($item->categoryObj ? $item->categoryObj->name : 'Sin categoría') . "\n";
        
        // Verificar documentos usando Spatie Media Library
        $mediaCount = $item->getMedia('receipts')->count();
        echo "         📎 Documentos (receipts): {$mediaCount}\n";
        
        if ($mediaCount > 0) {
            foreach ($item->getMedia('receipts') as $media) {
                echo "            📄 {$media->file_name} ({$media->mime_type}, {$media->human_readable_size})\n";
                echo "            🔗 URL: {$media->getFullUrl()}\n";
            }
        }
        
        // También verificar si tiene media en otras colecciones
        $allMedia = $item->getMedia();
        echo "         📎 Total documentos (todas las colecciones): {$allMedia->count()}\n";
        
        if ($allMedia->count() > 0) {
            foreach ($allMedia as $media) {
                echo "            📄 {$media->file_name} (colección: {$media->collection_name})\n";
            }
        }
        
        echo "\n";
    }
    echo "----------------------------------------\n\n";
}

// Verificar si hay documentos directamente en la tabla media
echo "🔍 VERIFICACIÓN DIRECTA EN TABLA MEDIA:\n";
$mediaItems = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', 'App\Models\ExpenseItem')
    ->whereIn('model_id', function($query) use ($abigail) {
        $query->select('expense_items.id')
            ->from('expense_items')
            ->join('expenses', 'expenses.id', '=', 'expense_items.expense_id')
            ->where('expenses.submitted_by', $abigail->id)
            ->whereBetween('expenses.expense_date', ['2025-09-01', '2025-09-30']);
    })
    ->get();

echo "Media encontrados: " . $mediaItems->count() . "\n";
foreach ($mediaItems as $media) {
    echo "   📄 {$media->file_name} (ID: {$media->id}, Colección: {$media->collection_name})\n";
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";