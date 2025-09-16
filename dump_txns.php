<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
$rows = $db->table('transactions')->orderBy('id','desc')->limit(30)->get();
foreach($rows as $r){ echo $r->id.' | '.$r->transaction_number.' | '.$r->from_account_id.' -> '.$r->to_account_id.' | '.$r->amount.' | '.$r->created_at."\n"; }
