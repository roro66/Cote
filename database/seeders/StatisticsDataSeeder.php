<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Person;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatisticsDataSeeder extends Seeder
{
    /**
     * Seed realistic approved expenses across the last 24 months for existing people.
     */
    public function run(): void
    {
        DB::disableQueryLog();
        $faker = Faker::create('es_CL');

        $adminUser = User::first();
        if (!$adminUser) {
            $this->command->warn('No hay usuarios. Ejecuta DatabaseSeeder primero.');
            return;
        }

        $people = Person::query()
            ->enabled()
            ->orderBy('id')
            ->take(50)
            ->get();

        if ($people->isEmpty()) {
            $this->command->warn('No hay personas habilitadas. Ejecuta los seeders base primero.');
            return;
        }

        $categories = ['combustible', 'materiales', 'herramientas', 'viaticos', 'otros'];

        $this->command->info('Sembrando datos de 24 meses para estadísticas (gastos aprobados por persona)...');

        foreach ($people as $person) {
            DB::transaction(function () use ($person, $faker, $adminUser, $categories) {
                // Ensure the person has a personal account
                $account = $person->accounts()->where('type', 'person')->first();
                if (!$account) {
                    $account = Account::create([
                        'name' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                        'type' => 'person',
                        'person_id' => $person->id,
                        'balance' => 0,
                        'notes' => null,
                        'is_enabled' => true,
                    ]);
                }

                // For each of the last 24 months (including current), create 0-4 expenses with 1-5 items
                for ($m = 23; $m >= 0; $m--) {
                    $monthStart = now()->startOfMonth()->subMonths($m);
                    $monthEnd = $monthStart->copy()->endOfMonth();

                    $expensesThisMonth = $faker->numberBetween(0, 4);
                    for ($e = 0; $e < $expensesThisMonth; $e++) {
                        $expenseDate = $faker->dateTimeBetween($monthStart, $monthEnd);

                        $expense = Expense::create([
                            'account_id' => $account->id,
                            'submitted_by' => $person->id,
                            'description' => 'Rendición de ' . $person->first_name . ' - ' . $faker->words(3, true),
                            'status' => 'approved',
                            'expense_date' => $expenseDate,
                            'submitted_at' => $faker->dateTimeBetween($monthStart, $expenseDate),
                            'reviewed_by' => $adminUser->id,
                            'reviewed_at' => $faker->dateTimeBetween($expenseDate, $monthEnd),
                            'total_amount' => 0,
                            'is_enabled' => true,
                        ]);

                        $itemsCount = $faker->numberBetween(1, 5);
                        $total = 0;
                        for ($i = 0; $i < $itemsCount; $i++) {
                            $amount = $faker->numberBetween(3000, 150000);
                            $total += $amount;
                            ExpenseItem::create([
                                'expense_id' => $expense->id,
                                'document_type' => $faker->randomElement(['boleta', 'factura', 'ticket', 'vale']),
                                'document_number' => $faker->boolean(80) ? $faker->numerify('########') : null,
                                'vendor_name' => $faker->company(),
                                'description' => $faker->sentence(),
                                'amount' => $amount,
                                'expense_date' => $expenseDate,
                                'category' => $faker->randomElement($categories),
                                'is_enabled' => true,
                            ]);
                        }

                        $expense->update(['total_amount' => $total]);
                    }
                }
            });
        }

        $this->command->info('Estadísticas: datos de 24 meses sembrados.');
    }
}
