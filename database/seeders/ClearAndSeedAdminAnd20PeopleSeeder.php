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

class ClearAndSeedAdminAnd20PeopleSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();
        config(['activitylog.enabled' => false]);

        // Asegurar datos base para llaves foráneas opcionales
        $this->call([
            BankSeeder::class,
            AccountTypeSeeder::class,
        ]);

        $faker = Faker::create('es_CL');
        $bankIds = Bank::query()->pluck('id')->all();
        $acctTypeIds = AccountType::query()->pluck('id')->all();

        // Crear usuario administrador
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@coteso.com',
            'password' => 'password', // hash por cast del modelo
            'is_enabled' => true,
        ]);

        // Crear 20 personas y sus cuentas personales
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
                'name' => 'Cuenta de ' . $first,
                'type' => 'person',
                'person_id' => $person->id,
                'balance' => $faker->numberBetween(50_000, 300_000),
                'notes' => $faker->optional()->sentence(),
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
