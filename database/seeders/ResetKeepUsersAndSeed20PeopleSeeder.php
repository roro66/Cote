<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Bank;
use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ResetKeepUsersAndSeed20PeopleSeeder extends Seeder
{
    public function run(): void
    {
        // Optimización y logs
        DB::disableQueryLog();
        config(['activitylog.enabled' => false]);

        // 1) TRUNCATE a todas las tablas excepto users y migrations (PostgreSQL)
        $tables = collect(DB::select(<<<SQL
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
              AND tablename NOT IN ('users', 'migrations')
              AND tablename NOT LIKE 'pg\_%'
              AND tablename NOT LIKE 'sql\_%'
        SQL))->pluck('tablename')->all();

        if (!empty($tables)) {
            $quoted = collect($tables)->map(fn($t) => '"' . $t . '"')->implode(', ');
            DB::statement("TRUNCATE TABLE {$quoted} RESTART IDENTITY CASCADE;");
        }

        // 2) Semillas base necesarias para referencias
        $this->call([
            BankSeeder::class,
            AccountTypeSeeder::class,
        ]);

        $faker = Faker::create('es_CL');
        $bankIds = Bank::query()->pluck('id')->all();
        $acctTypeIds = AccountType::query()->pluck('id')->all();

        // Crear cuenta de Tesorería general (para transferir a personas)
        Account::firstOrCreate([
            'name' => 'Tesorería General',
            'type' => 'treasury',
        ], [
            'person_id' => null,
            'balance' => 200_000_000,
            'notes' => 'Cuenta central para transferencias',
            'is_enabled' => true,
        ]);

        // 3) Crear 20 personas con sus cuentas personales
        for ($i = 1; $i <= 20; $i++) {
            $first = $faker->firstName();
            $last = $faker->lastName() . ' ' . $faker->lastName();
            $rutBase = 25_000_000 + $i; // único y simple

            $person = Person::create([
                'first_name' => $first,
                'last_name' => $last,
                'rut' => $this->formatRut($rutBase),
                'email' => sprintf('persona%02d@coteso.test', $i),
                'phone' => $faker->optional(0.7)->phoneNumber(),
                'bank_id' => !empty($bankIds) ? $faker->randomElement($bankIds) : null,
                'account_type_id' => !empty($acctTypeIds) ? $faker->randomElement($acctTypeIds) : null,
                'account_number' => $faker->numerify('##########'),
                'address' => $faker->optional(0.6)->address(),
                'role_type' => 'trabajador',
                'is_enabled' => true,
            ]);

            Account::create([
                'name' => $first,
                'type' => 'person',
                'person_id' => $person->id,
                'balance' => $faker->numberBetween(50_000, 300_000),
                'notes' => $faker->optional()->sentence(),
                'is_enabled' => true,
            ]);
        }

        // 4) Resumen
        if (property_exists($this, 'command') && $this->command) {
            $this->command->info('Reseteo completo (excepto users). Creadas 20 personas con cuentas.');
            $this->command->info('Personas: ' . Person::count());
            $this->command->info('Cuentas: ' . Account::count());
        }
    }

    private function formatRut(int $number): string
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
