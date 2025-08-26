<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Bank;
use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Person;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class MassiveDataSeeder extends Seeder
{
    private int $txCounterStart = 0;
    private int $expCounterStart = 0;

    private function bootCounters(): void
    {
        $year = date('Y');
        // Transactions
        $lastTx = Transaction::where('transaction_number', 'like', "TXN-{$year}-%")
            ->orderBy('transaction_number', 'desc')
            ->value('transaction_number');
        $this->txCounterStart = $this->extractSuffix($lastTx);

        // Expenses
        $lastExp = Expense::where('expense_number', 'like', "RND-{$year}-%")
            ->orderBy('expense_number', 'desc')
            ->value('expense_number');
        $this->expCounterStart = $this->extractSuffix($lastExp);
    }

    private function extractSuffix(?string $code): int
    {
        if (!$code) return 0;
        $parts = explode('-', $code);
        $suffix = end($parts);
        return ctype_digit($suffix) ? intval($suffix) : 0;
    }

    private function nextTxNumber(): string
    {
        $this->txCounterStart++;
        return sprintf('TXN-%s-%06d', date('Y'), $this->txCounterStart);
    }

    private function nextExpNumber(): string
    {
        $this->expCounterStart++;
        return sprintf('RND-%s-%06d', date('Y'), $this->expCounterStart);
    }

    public function run(): void
    {
        // Performance tweaks
        DB::disableQueryLog();
        config(['activitylog.enabled' => false]);

        $faker = Faker::create('es_CL');

        // Initialize counters for sequential numbers
        $this->bootCounters();

        // Ensure base data: rely on migration seeded data; do not insert invalid types
        // Banks migration already inserted many valid rows with type in ['banco','tarjeta_prepago','cooperativa']
        // Account types migration already inserted several rows

        $banks = Bank::active()->pluck('id')->all();
        $acctTypes = AccountType::active()->pluck('id')->all();

        // Create (or find) a treasury account
        $treasury = Account::firstOrCreate([
            'name' => 'Tesorería General',
            'type' => 'treasury',
        ], [
            'person_id' => null,
            'balance' => 500_000_000,
            'notes' => 'Cuenta central de tesorería para pruebas',
            'is_enabled' => true,
        ]);

        // Pick a user to attribute actions
        $adminUser = User::first();

        $totalPeople = 3000;
        $batch = 300; // create in batches to reduce memory pressure

        // Keep a growing list of personal account IDs for cross-account transfers
        $personAccountIds = Account::where('type', 'person')->pluck('id')->all();

        $this->command->info("Creando {$totalPeople} personas con cuentas...");

        for ($offset = 0; $offset < $totalPeople; $offset += $batch) {
            DB::beginTransaction();
            $toCreate = min($batch, $totalPeople - $offset);

            for ($i = 0; $i < $toCreate; $i++) {
                // Generate deterministic unique RUT in a high range to avoid collisions
                $rutBase = 30_000_000 + ($offset * 1000) + $i; // ensures uniqueness per batch
                $rut = self::formatRut($rutBase);

                $person = Person::create([
                    'first_name' => $faker->firstName(),
                    'last_name' => $faker->lastName() . ' ' . $faker->lastName(),
                    'rut' => $rut,
                    'email' => $faker->safeEmail(),
                    'phone' => $faker->optional(0.7)->phoneNumber(),
                    'bank_id' => $faker->randomElement($banks),
                    'account_type_id' => $faker->randomElement($acctTypes),
                    'account_number' => $faker->numerify('##########'),
                    'address' => $faker->optional(0.6)->address(),
                    'role_type' => $faker->randomElement(['tesorero', 'trabajador']),
                    'is_enabled' => $faker->boolean(90),
                    'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => now(),
                ]);

                // Create personal account
                $personAccount = Account::create([
                    'name' => 'Cuenta de ' . $person->first_name,
                    'type' => 'person',
                    'person_id' => $person->id,
                    'balance' => $faker->numberBetween(0, 2_000_000),
                    'notes' => $faker->optional()->sentence(),
                    'is_enabled' => true,
                ]);

                $personAccountIds[] = $personAccount->id;

                // Create transactions (5-15 per person)
                $txCount = $faker->numberBetween(5, 15);
                for ($t = 0; $t < $txCount; $t++) {
                    $typeRoll = $faker->numberBetween(1, 100);
                    if ($typeRoll <= 60) {
                        $from = $treasury;
                        $to = $personAccount;
                        $type = 'transfer';
                    } elseif ($typeRoll <= 90) {
                        $from = $personAccount;
                        $to = $treasury;
                        $type = 'payment';
                    } else {
                        // between two personal accounts using sampled list
                        if (count($personAccountIds) > 1) {
                            $randIndex = array_rand($personAccountIds);
                            $randId = $personAccountIds[$randIndex];
                            if ($randId === $personAccount->id && count($personAccountIds) > 1) {
                                $randIndex = ($randIndex + 1) % count($personAccountIds);
                                $randId = $personAccountIds[$randIndex];
                            }
                            $to = Account::find($randId);
                        } else {
                            $to = $treasury;
                        }
                        $from = $personAccount;
                        $type = 'transfer';
                    }

                    $amount = $faker->numberBetween(10_000, 200_000);

                    Transaction::create([
                        'type' => $type,
                        'from_account_id' => $from->id,
                        'to_account_id' => $to->id,
                        'amount' => $amount,
                        'description' => $faker->sentence(),
                        'notes' => $faker->optional()->sentence(),
                        'created_by' => $adminUser?->id,
                        'approved_by' => $faker->boolean(70) ? ($adminUser?->id) : null,
                        'status' => $faker->randomElement(['pending', 'approved', 'completed']),
                        'approved_at' => $faker->boolean(60) ? now()->subDays($faker->numberBetween(0, 90)) : null,
                        'is_enabled' => true,
                        'transaction_number' => $this->nextTxNumber(),
                    ]);

                    // Update balances quickly
                    Account::whereKey($from->id)->decrement('balance', $amount);
                    Account::whereKey($to->id)->increment('balance', $amount);
                }

                // Create expenses (rendiciones) 5-30 per person
                $expCount = $faker->numberBetween(5, 30);
                for ($e = 0; $e < $expCount; $e++) {
                    $expense = Expense::create([
                        'account_id' => $personAccount->id,
                        'submitted_by' => $person->id,
                        'description' => 'Rendición de ' . $person->first_name . ' ' . $faker->word(),
                        'status' => $faker->randomElement(['draft', 'submitted', 'approved', 'rejected']),
                        'expense_date' => now()->subDays($faker->numberBetween(0, 180)),
                        'submitted_at' => $faker->boolean(80) ? now()->subDays($faker->numberBetween(0, 180)) : null,
                        'reviewed_by' => $faker->boolean(50) ? ($adminUser?->id) : null,
                        'reviewed_at' => $faker->boolean(50) ? now()->subDays($faker->numberBetween(0, 180)) : null,
                        'total_amount' => 0,
                        'is_enabled' => true,
                        'expense_number' => $this->nextExpNumber(),
                    ]);

                    $itemsCount = $faker->numberBetween(1, 4);
                    $sum = 0;
                    for ($k = 0; $k < $itemsCount; $k++) {
                        $amount = $faker->numberBetween(3_000, 80_000);
                        $sum += $amount;
                        ExpenseItem::create([
                            'expense_id' => $expense->id,
                            'document_type' => $faker->randomElement(['boleta', 'factura', 'ticket', 'vale']),
                            'document_number' => $faker->boolean(80) ? $faker->numerify('########') : null,
                            'vendor_name' => $faker->company(),
                            'description' => $faker->sentence(),
                            'amount' => $amount,
                            'expense_date' => $expense->expense_date,
                            'category' => $faker->randomElement(['combustible', 'materiales', 'herramientas', 'viaticos', 'otros']),
                            'is_enabled' => true,
                        ]);
                    }
                    // Set total
                    $expense->update(['total_amount' => $sum]);
                }
            }

            DB::commit();
            $this->command->info("Progreso: " . min($totalPeople, $offset + $toCreate) . "/{$totalPeople} personas");
        }

        $this->command->info('Datos masivos generados exitosamente.');
    }

    private static function formatRut(int $number): string
    {
        $n = (string) $number;
        $sum = 0;
        $mult = 2;
        for ($i = strlen($n) - 1; $i >= 0; $i--) {
            $sum += intval($n[$i]) * $mult;
            $mult = $mult == 7 ? 2 : $mult + 1;
        }
        $dv = 11 - ($sum % 11);
        $dvChar = $dv == 11 ? '0' : ($dv == 10 ? 'K' : (string)$dv);
        return number_format($number, 0, '', '.') . '-' . $dvChar;
    }
}
