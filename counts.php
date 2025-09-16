<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
$tables = ['users','people','accounts','transactions','expenses','expense_categories'];
foreach($tables as $t){ echo $t.': '.(int)$db->table($t)->count()."\n"; }
