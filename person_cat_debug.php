<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$db = Illuminate\Support\Facades\DB::getFacadeRoot();
$personId = 1; // Carlos Mendoza
$months = 6;
$start = (new \DateTime())->modify('first day of this month')->modify('-'.($months-1).' months')->format('Y-m-d 00:00:00');
$end = (new \DateTime())->format('Y-m-d 23:59:59');

echo "Diagnóstico para person_id={$personId} (últimos {$months} meses)\n";
$person = $db->table('people')->where('id',$personId)->first();
if ($person) echo "Persona: {$person->first_name} {$person->last_name}\n";

$totalExpenses = (int)$db->table('expenses')->where('submitted_by',$personId)->count();
$approvedAll = (int)$db->table('expenses')->where('submitted_by',$personId)->where('status','approved')->count();
$approvedRecent = (int)$db->table('expenses')
    ->where('submitted_by',$personId)
    ->where('status','approved')
    ->whereBetween('expense_date', [$start, $end])->count();

$itemsAll = (int)$db->table('expense_items')
    ->join('expenses','expense_items.expense_id','=','expenses.id')
    ->where('expenses.submitted_by',$personId)
    ->count();

$itemsRecent = (int)$db->table('expense_items')
    ->join('expenses','expense_items.expense_id','=','expenses.id')
    ->where('expenses.submitted_by',$personId)
    ->where('expenses.status','approved')
    ->whereBetween('expense_items.expense_date', [$start, $end])
    ->count();

$itemsWithCat = (int)$db->table('expense_items')
    ->join('expenses','expense_items.expense_id','=','expenses.id')
    ->where('expenses.submitted_by',$personId)
    ->where('expenses.status','approved')
    ->whereNotNull('expense_items.expense_category_id')
    ->whereBetween('expense_items.expense_date', [$start, $end])
    ->count();

echo "Total rendiciones (all): {$totalExpenses}\n";
echo "Rendiciones aprobadas (all time): {$approvedAll}\n";
echo "Rendiciones aprobadas (últimos {$months} meses): {$approvedRecent}\n";
echo "Ítems totales en rendiciones (all): {$itemsAll}\n";
echo "Ítems aprobados y recientes (últimos {$months} meses): {$itemsRecent}\n";
echo "Ítems con categoría (aprobados y recientes): {$itemsWithCat}\n";

// Sumas por categoría
$rows = $db->table('expense_items')
    ->select('expense_items.expense_category_id', $db->raw('SUM(expense_items.amount) as total'))
    ->join('expenses','expense_items.expense_id','=','expenses.id')
    ->where('expenses.submitted_by',$personId)
    ->where('expenses.status','approved')
    ->whereBetween('expense_items.expense_date', [$start, $end])
    ->whereNotNull('expense_items.expense_category_id')
    ->groupBy('expense_items.expense_category_id')
    ->orderByDesc('total')
    ->get();

echo "Suma por categoría (aprobados, últimos {$months} meses):\n";
if (count($rows)===0) echo "  (sin datos)\n";
foreach($rows as $r) {
    $cat = $db->table('expense_categories')->where('id',$r->expense_category_id)->first();
    $name = $cat ? $cat->name : '#'.$r->expense_category_id;
    echo "  {$name}: {$r->total}\n";
}

// Mostrar últimas rendiciones aprobadas
$approvedList = $db->table('expenses')
    ->where('submitted_by',$personId)
    ->where('status','approved')
    ->orderByDesc('expense_date')
    ->limit(10)
    ->get();

echo "Últimas rendiciones aprobadas (hasta 10):\n";
if (count($approvedList)===0) echo "  (ninguna)\n";
foreach($approvedList as $a) echo "  id={$a->id} date={$a->expense_date} total={$a->total_amount}\n";

// Mostrar ítems recientes con categoría
$itemsList = $db->table('expense_items')
    ->select('expense_items.*','expenses.status')
    ->join('expenses','expense_items.expense_id','=','expenses.id')
    ->where('expenses.submitted_by',$personId)
    ->where('expenses.status','approved')
    ->whereBetween('expense_items.expense_date', [$start, $end])
    ->whereNotNull('expense_items.expense_category_id')
    ->orderByDesc('expense_items.expense_date')
    ->limit(20)
    ->get();

echo "Ítems aprobados y recientes (muestra hasta 20):\n";
if (count($itemsList)===0) echo "  (sin ítems con categoría)\n";
foreach($itemsList as $it) echo "  item id={$it->id} expense_id={$it->expense_id} cat_id={$it->expense_category_id} amt={$it->amount} date={$it->expense_date}\n";

