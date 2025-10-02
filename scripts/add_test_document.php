<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExpenseItem;
use Illuminate\Http\UploadedFile;

echo "=== AGREGAR DOCUMENTO DE PRUEBA ===\n\n";

// Buscarle el expense item de Abagail Ebert Ortiz en septiembre
$targetItem = ExpenseItem::whereHas('expense', function($query) {
    $query->whereHas('submittedBy', function($subquery) {
        $subquery->where('first_name', 'LIKE', '%Abagail%')
                 ->where('last_name', 'LIKE', '%Ebert%');
    })
    ->whereBetween('expense_date', ['2025-09-01', '2025-09-30']);
})
->first();

if (!$targetItem) {
    echo "❌ No se encontró expense item de Abagail Ebert\n";
    exit;
}

echo "✅ Encontrado expense item: {$targetItem->description} (ID: {$targetItem->id})\n";
echo "   💰 Monto: \${$targetItem->amount}\n";
echo "   📅 Fecha: {$targetItem->expense->expense_date->format('Y-m-d')}\n";
echo "   👤 Persona: {$targetItem->expense->submittedBy->first_name} {$targetItem->expense->submittedBy->last_name}\n\n";

// Crear una imagen de prueba simple (1x1 pixel PNG)
$tempFile = tempnam(sys_get_temp_dir(), 'test_receipt_') . '.png';

// Crear una imagen PNG simple de 100x100 píxeles
$image = imagecreate(200, 150);
$background = imagecolorallocate($image, 255, 255, 255); // Blanco
$textColor = imagecolorallocate($image, 0, 0, 0); // Negro
$borderColor = imagecolorallocate($image, 0, 0, 0); // Negro

// Dibujar borde
imagerectangle($image, 0, 0, 199, 149, $borderColor);

// Agregar texto
imagestring($image, 3, 10, 20, "COMPROBANTE PRUEBA", $textColor);
imagestring($image, 2, 10, 50, "Item: " . substr($targetItem->description, 0, 15), $textColor);
imagestring($image, 2, 10, 70, "Monto: $" . number_format($targetItem->amount), $textColor);
imagestring($image, 2, 10, 90, "Fecha: " . date('Y-m-d'), $textColor);
imagestring($image, 1, 10, 120, "Documento de prueba", $textColor);

// Guardar imagen
imagepng($image, $tempFile);
imagedestroy($image);

try {
    // Agregar el archivo usando Spatie Media Library
    $media = $targetItem
        ->addMedia($tempFile)
        ->usingName('Comprobante de Prueba')
        ->usingFileName('comprobante_prueba.png')
        ->toMediaCollection('receipts');
    
    echo "✅ Documento agregado exitosamente:\n";
    echo "   📄 Nombre: {$media->name}\n";
    echo "   📁 Archivo: {$media->file_name}\n";
    echo "   🗂️  Colección: {$media->collection_name}\n";
    echo "   📊 Tamaño: {$media->human_readable_size}\n";
    echo "   🔗 URL: {$media->getFullUrl()}\n\n";
    
    // Verificar que se guardó correctamente
    $documentsCount = $targetItem->getMedia('receipts')->count();
    echo "📎 Total documentos en el item: {$documentsCount}\n";
    
    echo "\n🎉 ¡Documento de prueba agregado! Ahora puedes probar el informe con documentos.\n";
    
} catch (Exception $e) {
    echo "❌ Error al agregar documento: " . $e->getMessage() . "\n";
} finally {
    // Limpiar archivo temporal
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
}

echo "\n=== FIN ===\n";