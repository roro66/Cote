<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
$tables = ['transactions','expenses','expense_items','media'];
foreach($tables as $t){ echo $t.': '.(int)$db->table($t)->count()."\n"; }
