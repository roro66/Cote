<?php
// Lista rendiciones que tienen archivos asociados (media en expense items)
require __DIR__ . '/../vendor/autoload.php';

use App\Models\Expense;
use App\Models\ExpenseItem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$itemsWithMedia = ExpenseItem::whereHas('media')->with(['media', 'expense'])->get();

$grouped = [];
foreach ($itemsWithMedia as $item) {
    $exp = $item->expense;
    $expId = $exp ? $exp->id : null;
    $expNum = $exp ? $exp->expense_number : null;
    if (!isset($grouped[$expId])) {
        $grouped[$expId] = [
            'expense_id' => $expId,
            'expense_number' => $expNum,
            'items' => []
        ];
    }
    foreach ($item->getMedia('receipts') as $m) {
        $grouped[$expId]['items'][] = [
            'item_id' => $item->id,
            'media_id' => $m->id,
            'file_name' => $m->file_name,
            'mime_type' => $m->mime_type,
            'size' => $m->size,
            'url' => $m->getFullUrl()
        ];
    }
}

if (empty($grouped)) {
    echo "No se encontraron rendiciones con archivos asociados.\n";
    exit(0);
}

foreach ($grouped as $g) {
    echo sprintf("Rendición: id=%s, número=%s\n", $g['expense_id'], $g['expense_number']);
    foreach ($g['items'] as $it) {
        echo sprintf("  Item %s -> Media %s: %s (%s, %d bytes) URL: %s\n", $it['item_id'], $it['media_id'], $it['file_name'], $it['mime_type'], $it['size'], $it['url']);
    }
    echo "\n";
}
