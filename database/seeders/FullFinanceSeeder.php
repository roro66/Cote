<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Person;
use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\User;
use Illuminate\Support\Str;

class FullFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $people = Person::all();
        $accounts = Account::all();
        $users = User::all();

        $treasury = $accounts->where('type','treasury')->first();
        if (!$treasury) {
            $this->command->warn('No hay cuenta Tesorería. Abortando FullFinanceSeeder.');
            return;
        }

        $categories = DB::table('expense_categories')->pluck('id')->all();
        $imagePool = [];
        // recolectar jpeg desde el host (ruta absoluta)
        $imgDir = '/home/rodrigo/Imágenes';
        if (is_dir($imgDir)) {
            foreach (glob($imgDir.'/*.{jpg,jpeg,JPG,JPEG}', GLOB_BRACE) as $f) {
                if (is_file($f)) $imagePool[] = $f;
            }
        }

        $admin = $users->first();
        $totalTx = 0;
        $totalExp = 0;

        foreach ($people as $person) {
            try {
                $personAccount = $accounts->where('person_id', $person->id)->first();
                if (!$personAccount) {
                    // si no hay, crear una cuenta personal mínima
                    $personAccount = Account::create([
                        'name' => trim($person->first_name . ' ' . $person->last_name),
                        'type' => 'person',
                        'person_id' => $person->id,
                        'is_enabled' => true,
                    ]);
                    // actualizar colección local
                    $accounts = $accounts->push($personAccount);
                }

                // Crear entre 3 y 7 transferencias Tesorería -> persona para darle fondos
                $transfers = rand(3,7);
                for ($t=0;$t<$transfers;$t++) {
                    $amount = rand(20000, 300000);
                    $date = now()->subMonths(rand(0,12))->subDays(rand(0,28));
                    DB::table('transactions')->insert([
                        'transaction_number' => 'FTX-' . $person->id . '-' . Str::upper(Str::random(6)) . time() . rand(10,99),
                        'type' => 'transfer',
                        'from_account_id' => $treasury->id,
                        'to_account_id' => $personAccount->id,
                        'amount' => $amount,
                        'description' => 'Adelanto Tesorería a ' . $person->full_name,
                        'notes' => null,
                        'created_by' => $admin->id,
                        'approved_by' => $admin->id,
                        'status' => 'approved',
                        'approved_at' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                        'is_enabled' => true,
                    ]);
                    $totalTx++;
                }

                // Crear entre 1 y 4 rendiciones para la persona
                $renditions = rand(1,4);
                for ($r=0;$r<$renditions;$r++) {
                    $expenseDate = now()->subMonths(rand(0,12))->subDays(rand(0,28));
                    $expense = Expense::create([
                        'account_id' => $personAccount->id,
                        'submitted_by' => $person->id,
                        'total_amount' => 0,
                        'description' => 'Rendición automática para ' . $person->full_name,
                        'expense_date' => $expenseDate,
                        'status' => 'submitted',
                        'submitted_at' => $expenseDate,
                        'is_enabled' => true,
                    ]);

                    $itemsCount = rand(1,5);
                    $sum = 0;
                    for ($it=0;$it<$itemsCount;$it++) {
                        $amt = rand(1000, 120000);
                        $catId = $categories ? $categories[array_rand($categories)] : null;
                        $item = ExpenseItem::create([
                            'expense_id' => $expense->id,
                            'document_type' => 'ticket',
                            'document_number' => 'D-' . rand(1000,9999),
                            'vendor_name' => 'Proveedor ' . substr(Str::slug($person->last_name),0,8),
                            'description' => 'Gasto '.$it.' para '.$person->first_name,
                            'amount' => $amt,
                            'expense_date' => $expenseDate,
                            'category' => null,
                            'expense_category_id' => $catId,
                            'is_enabled' => true,
                        ]);
                        $sum += $amt;

                        // Adjuntar imagen aleatoria si existe
                        if (!empty($imagePool) && rand(0,1) === 1) {
                            $path = $imagePool[array_rand($imagePool)];
                            try {
                                $item->addMedia($path)->toMediaCollection('receipts');
                            } catch (\Throwable $e) {
                                $this->command->warn('No se pudo adjuntar imagen '.$path.' a item '.$item->id.': '.$e->getMessage());
                            }
                        }
                    }

                    // actualizar total y aprobar la rendición al azar
                    $expense->update(['total_amount' => $sum]);
                    if (rand(0,1) === 1) {
                        $expense->approve($admin->id);
                    }
                    $totalExp++;
                }

                // Posible devolución: con probabilidad 30% la persona devuelve saldo sobrante
                if (rand(1,100) <= 30) {
                    $amountReturn = rand(5000, 50000);
                    $date = now()->subMonths(rand(0,6))->subDays(rand(0,20));
                    DB::table('transactions')->insert([
                        'transaction_number' => 'RTX-' . $person->id . '-' . Str::upper(Str::random(6)) . time() . rand(10,99),
                        'type' => 'transfer',
                        'from_account_id' => $personAccount->id,
                        'to_account_id' => $treasury->id,
                        'amount' => $amountReturn,
                        'description' => 'Devolución a Tesorería por ' . $person->full_name,
                        'notes' => null,
                        'created_by' => $admin->id,
                        'approved_by' => $admin->id,
                        'status' => 'approved',
                        'approved_at' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                        'is_enabled' => true,
                    ]);
                    $totalTx++;
                }

            } catch (\Throwable $e) {
                $this->command->warn('Error en persona '.$person->id.': '.$e->getMessage());
                continue;
            }
        }

        $this->command->info('FullFinanceSeeder completado. Transacciones creadas: '.$totalTx.'. Rendiciones creadas: '.$totalExp);
    }
}
