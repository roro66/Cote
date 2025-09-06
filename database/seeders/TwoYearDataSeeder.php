<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class TwoYearDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();
        config(['activitylog.enabled' => false]);

        $faker = Faker::create('es_CL');
        $admin = User::first();

        // Take a sample of personal accounts to seed against; if there are few, use them all
        $accountsQuery = Account::where('type', 'person');
        $totalAccounts = $accountsQuery->count();
        $sampleSize = min(200, max(10, $totalAccounts));
        $accounts = $accountsQuery->inRandomOrder()->take($sampleSize)->get();

        $this->command->info("Seeding TwoYearData for {$accounts->count()} person accounts (sample size)");

        $txAdded = 0;
        $expAdded = 0;
        $itemsAdded = 0;

        // Ensure there's a treasury account to use as counterparty
        $treasury = Account::firstOrCreate([
            'type' => 'treasury'
        ], [
            'name' => 'Tesorería General',
            'person_id' => null,
            'balance' => 500_000_000,
            'notes' => 'Cuenta central de tesorería para pruebas',
            'is_enabled' => true,
        ]);

        foreach ($accounts as $account) {
            DB::beginTransaction();

            // Transactions: 5-15 per account (random)
            $txCount = $faker->numberBetween(5, 15);
            for ($i = 0; $i < $txCount; $i++) {
                $amount = $faker->numberBetween(3_000, 300_000);
                $createdAt = $faker->dateTimeBetween('-2 years', 'now');

                // Only create transactions between treasury and personal accounts
                $typeRoll = $faker->numberBetween(1, 100);
                if ($typeRoll <= 60) {
                    // Treasury -> Person (transfer)
                    $from = $treasury;
                    $to = $account;
                } else {
                    // Person -> Treasury (transfer)
                    $from = $account;
                    $to = $treasury;
                }
                // The DB enforces type = 'transfer' via chk_transactions_type
                $type = 'transfer';

                Transaction::create([
                    'type' => $type,
                    'from_account_id' => $from->id,
                    'to_account_id' => $to->id,
                    'amount' => $amount,
                    'description' => $faker->sentence(6),
                    'notes' => $faker->optional()->sentence(),
                    'created_by' => $admin?->id,
                    'approved_by' => $faker->boolean(60) ? $admin?->id : null,
                    'status' => $faker->randomElement(['pending', 'approved', 'completed']),
                    'approved_at' => $faker->boolean(50) ? $faker->dateTimeBetween($createdAt, 'now') : null,
                    'is_enabled' => true,
                    'transaction_number' => 'TXN-' . date('Y') . '-' . uniqid(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
                $txAdded++;
            }

            // Expenses: 2-6 per account
            $expCount = $faker->numberBetween(2, 6);
            for ($e = 0; $e < $expCount; $e++) {
                $submittedAt = $faker->boolean(80) ? $faker->dateTimeBetween('-2 years', 'now') : null;
                $expenseDate = $faker->dateTimeBetween('-2 years', 'now');

                $submittedBy = $account->person_id ?? \App\Models\Person::inRandomOrder()->value('id');

                $expense = Expense::create([
                    'account_id' => $account->id,
                    'submitted_by' => $submittedBy,
                    'description' => $faker->sentence(6),
                    'status' => $faker->randomElement(['draft', 'submitted', 'approved', 'rejected']),
                    'expense_date' => $expenseDate,
                    'submitted_at' => $submittedAt,
                    'reviewed_by' => $faker->boolean(30) ? $admin?->id : null,
                    'reviewed_at' => $faker->boolean(30) ? $faker->dateTimeBetween($expenseDate, 'now') : null,
                    'total_amount' => 0,
                    'is_enabled' => true,
                    'expense_number' => 'RND-' . date('Y') . '-' . uniqid(),
                    'created_at' => $expenseDate,
                    'updated_at' => $expenseDate,
                ]);

                $itemsCount = $faker->numberBetween(1, 3);
                $sum = 0;
                for ($k = 0; $k < $itemsCount; $k++) {
                    $amount = $faker->numberBetween(1_000, 150_000);
                    ExpenseItem::create([
                        'expense_id' => $expense->id,
                        'document_type' => $faker->randomElement(['boleta', 'factura', 'ticket', 'vale']),
                        'document_number' => $faker->optional()->numerify('########'),
                        'vendor_name' => $faker->company(),
                        'description' => $faker->sentence(6),
                        'amount' => $amount,
                        'expense_date' => $expenseDate,
                        'category' => $faker->randomElement(['combustible', 'materiales', 'herramientas', 'viaticos', 'otros']),
                        'is_enabled' => true,
                        'created_at' => $expenseDate,
                        'updated_at' => $expenseDate,
                    ]);
                    $sum += $amount;
                    $itemsAdded++;
                }

                $expense->update(['total_amount' => $sum]);
                $expAdded++;
            }

            DB::commit();
        }

        $this->command->info("TwoYearDataSeeder finished: Transactions={$txAdded}, Expenses={$expAdded}, ExpenseItems={$itemsAdded}");
    }
}
