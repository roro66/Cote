<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\Bank;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ClearAndSeed20Seeder extends Seeder
{
    public function run(): void
    {
        // Deshabilitar logs y optimizar inserts
        DB::disableQueryLog();
        config(['activitylog.enabled' => false]);

        // Asegurar datos base necesarios
        $this->call([
            BankSeeder::class,
            AccountTypeSeeder::class,
        ]);

        $faker = Faker::create('es_CL');

        $bankIds = Bank::query()->pluck('id')->all();
        $acctTypeIds = AccountType::query()->pluck('id')->all();

        // Crear cuenta de Tesorería básica
        $treasury = Account::firstOrCreate([
            'name' => 'Tesorería General',
            'type' => 'treasury',
        ], [
            'person_id' => null,
            'balance' => 200_000_000,
            'notes' => 'Cuenta central para pruebas mínimas',
            'is_enabled' => true,
        ]);

        // Crear 20 personas, usuarios y cuentas personales
        for ($i = 1; $i <= 20; $i++) {
            $first = $faker->firstName();
            $last = $faker->lastName() . ' ' . $faker->lastName();
            $rutBase = 20_000_000 + $i; // rango simple y único

            $person = Person::create([
                'first_name' => $first,
                'last_name' => $last,
                'rut' => $this->formatRut($rutBase),
                'email' => sprintf('user%02d@cote.test', $i),
                'phone' => $faker->optional(0.7)->phoneNumber(),
                'bank_id' => !empty($bankIds) ? $faker->randomElement($bankIds) : null,
                'account_type_id' => !empty($acctTypeIds) ? $faker->randomElement($acctTypeIds) : null,
                'account_number' => $faker->numerify('##########'),
                'address' => $faker->optional(0.6)->address(),
                'role_type' => 'trabajador',
                'is_enabled' => true,
            ]);

            // Cuenta personal
            Account::create([
                'name' => 'Cuenta Personal de ' . $first,
                'type' => 'person',
                'person_id' => $person->id,
                'balance' => $faker->numberBetween(100_000, 500_000),
                'notes' => $faker->optional()->sentence(),
                'is_enabled' => true,
            ]);

            // Usuario vinculado
            User::create([
                'name' => $first . ' ' . $last,
                'email' => sprintf('user%02d@cote.test', $i),
                'password' => 'password', // se hashea por el cast del modelo
                'person_id' => $person->id,
                'is_enabled' => true,
            ]);
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
