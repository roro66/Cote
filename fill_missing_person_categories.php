<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
use Illuminate\Support\Str;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Account;

$months = 6;
$start = (new DateTime())->modify('first day of this month')->modify('-'.($months-1).' months')->format('Y-m-d 00:00:00');
$end = (new DateTime())->format('Y-m-d 23:59:59');

$people = $db->table('people')->where('is_enabled', true)->get();
$categories = $db->table('expense_categories')->pluck('id')->all();
$admin = $db->table('users')->first();
$createdExpenses = 0;
$createdItems = 0;
$personsFixed = 0;

foreach ($people as $person) {
    $personId = $person->id;
    $count = (int)$db->table('expense_items')
        ->join('expenses','expense_items.expense_id','=','expenses.id')
        ->where('expenses.submitted_by',$personId)
        ->where('expenses.status','approved')
        ->whereNotNull('expense_items.expense_category_id')
        ->whereBetween('expense_items.expense_date', [$start, $end])
        ->count();
    if ($count > 0) {
        // ya tiene datos recientes
        continue;
    }
    // encontrar cuenta personal
    $account = $db->table('accounts')->where('person_id',$personId)->where('type','person')->first();
    if (!$account) {
        // crear cuenta personal mínima
        $accId = $db->table('accounts')->insertGetId(['name'=>trim($person->first_name.' '.$person->last_name),'type'=>'person','person_id'=>$personId,'is_enabled'=>true]);
    } else {
        $accId = $account->id;
    }
    // crear 1 rendición aprobada con 1-2 items usando modelos Eloquent
    $expenseDate = now();
    $expense = Expense::create([
        'account_id' => $accId,
        'submitted_by' => $personId,
        'total_amount' => 0,
        'description' => 'Rendición generada para gráficos',
        'expense_date' => $expenseDate,
        'status' => 'submitted',
        'submitted_at' => $expenseDate,
        'is_enabled' => true,
    ]);
    $itemsToCreate = rand(1,2);
    $sum = 0;
    for ($i=0;$i<$itemsToCreate;$i++) {
        $amt = rand(1000, 50000);
        $catId = !empty($categories) ? $categories[array_rand($categories)] : null;
        $item = ExpenseItem::create([
            'expense_id' => $expense->id,
            'document_type' => 'ticket',
            'document_number' => Str::upper(Str::random(6)),
            'vendor_name' => 'Proveedor Seed',
            'description' => 'Item seed',
            'amount' => $amt,
            'expense_date' => $expenseDate,
            'category' => null,
            'expense_category_id' => $catId,
            'is_enabled' => true,
        ]);
        $sum += $amt;
        $createdItems++;
    }
    // actualizar total y aprobar mediante método de modelo para consistencia
    $expense->update(['total_amount' => $sum]);
    $expense->approve($admin->id);
    $createdExpenses++;
    $personsFixed++;
    echo "Persona {$personId} - {$person->first_name} {$person->last_name}: creada expense {$expense->id} con {$itemsToCreate} items\n";
}

echo "Resumen: personas ajustadas: {$personsFixed}, expenses creadas: {$createdExpenses}, items creados: {$createdItems}\n";
