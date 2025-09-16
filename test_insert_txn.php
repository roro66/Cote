<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
try {
    $treasury = $db->table('accounts')->where('type','treasury')->first();
    $personal = $db->table('accounts')->where('type','person')->first();
    $user = $db->table('users')->first();
    if (!$treasury || !$personal || !$user) { echo "Missing treasury/person/user\n"; exit(1); }
    $rec = [
        'transaction_number' => 'TST-' . time() . rand(100,999),
        'type'=>'transfer',
        'from_account_id'=>$treasury->id,
        'to_account_id'=>$personal->id,
        'amount'=>12345,
        'description'=>'Test insert',
        'notes'=>null,
        'created_by'=>$user->id,
        'approved_by'=>$user->id,
        'status'=>'approved',
        'approved_at'=>now(),
        'created_at'=>now(),
        'updated_at'=>now(),
        'is_enabled'=>true,
    ];
    $db->table('transactions')->insert($rec);
    echo "Insert OK\n";
} catch (\Throwable $e) {
    echo "ERROR: ". $e->getMessage() ."\n";
}
