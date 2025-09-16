<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
use Illuminate\Support\Str;

$treasury = $db->table('accounts')->where('type','treasury')->first();
if (!$treasury) {
    echo "No hay cuenta de tesorería. Abortando.\n";
    exit(1);
}
$admin = $db->table('users')->first();
$personAccounts = $db->table('accounts')->where('type','person')->get();
$createdTotal = 0;

foreach ($personAccounts as $acc) {
    $id = $acc->id;
    $count = (int) $db->table('transactions')
        ->where('from_account_id', $id)
        ->orWhere('to_account_id', $id)
        ->count();
    if ($count >= 12) {
        echo "Cuenta {$id} ({$acc->name}): ya tiene {$count} transacciones.\n";
        continue;
    }
    $needed = 12 - $count;
    // don't create past 60 total
    $maxAllowed = max(0, 60 - $count);
    $toCreate = min($needed, $maxAllowed);
    if ($toCreate <= 0) {
        echo "Cuenta {$id} ({$acc->name}): límite 60 alcanzado o nada por crear. current={$count}\n";
        continue;
    }
    echo "Cuenta {$id} ({$acc->name}): tiene {$count}, creando {$toCreate} transacciones...\n";
    for ($i=0;$i<$toCreate;$i++) {
        // alternate direction
        $dir = ($i % 2 === 0) ? 'in' : 'out';
        if ($dir === 'in') {
            $from = $treasury->id; $to = $id;
        } else {
            $from = $id; $to = $treasury->id;
        }
        $amount = rand(1000, 250000);
        $date = date('Y-m-d H:i:s', strtotime('-'.rand(0,720).' days'));
        $record = [
            'transaction_number' => 'ENS-' . $id . '-' . strtoupper(Str::random(6)) . '-' . time() . rand(10,99),
            'type' => 'transfer',
            'from_account_id' => $from,
            'to_account_id' => $to,
            'amount' => $amount,
            'description' => 'Ensanchamiento automático de transacciones',
            'notes' => null,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'status' => 'approved',
            'approved_at' => $date,
            'created_at' => $date,
            'updated_at' => $date,
            'is_enabled' => true,
        ];
        try {
            $db->table('transactions')->insert($record);
            $createdTotal++;
        } catch (\Throwable $e) {
            echo "  ERROR al insertar para cuenta {$id}: " . $e->getMessage() . "\n";
        }
    }
    $newCount = (int) $db->table('transactions')
        ->where('from_account_id', $id)
        ->orWhere('to_account_id', $id)
        ->count();
    echo "Cuenta {$id} ({$acc->name}): ahora tiene {$newCount} transacciones.\n";
}

echo "Resumen: transacciones creadas: {$createdTotal}\n";
