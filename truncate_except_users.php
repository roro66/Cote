<?php
require __DIR__.'/vendor/autoload.php';
putenv('APP_ENV=development');
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
$tables = collect($db->select("SELECT tablename FROM pg_tables WHERE schemaname = current_schema()"))
    ->pluck('tablename')
    ->filter(function($t){ return !in_array($t, ['users','migrations']); })
    ->values()
    ->all();
foreach($tables as $table){
    echo "Truncating: $table\n";
    $db->statement("ALTER TABLE \"$table\" DISABLE TRIGGER ALL");
    $db->table($table)->truncate();
    $db->statement("ALTER TABLE \"$table\" ENABLE TRIGGER ALL");
}
echo "DONE\n";
