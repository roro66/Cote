<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
$types = $db->table('accounts')->select('type', \Illuminate\Support\Facades\DB::raw('count(*) as c'))->groupBy('type')->get();
foreach($types as $t) echo "type={$t->type} count={$t->c}\n";
$rows = $db->table('accounts')->select('id','name','type','person_id','balance')->limit(30)->get();
foreach($rows as $r) echo "{$r->id} | {$r->name} | {$r->type} | person_id={$r->person_id} | balance={$r->balance}\n";
