<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Person;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Str;

class PersonTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $people = Person::all();
        $accounts = Account::all();
        $users = User::all();

        if ($people->isEmpty() || $accounts->count() < 2 || $users->isEmpty()) {
            $this->command->warn('No hay suficientes datos para generar transacciones por persona.');
            return;
        }

        $this->command->info('Generando transacciones por persona...');

        // Determinar cuenta de tesorería y cuentas personales
        $treasury = $accounts->where('type', 'tesoreria')->first();
        $personalAccounts = $accounts->where('type', 'personal');

        $totalCreated = 0;
        foreach ($people as $person) {
            // Número aleatorio entre 12 y 50 por persona
            $count = rand(12, 50);
            $createdForPerson = 0;

            for ($i = 0; $i < $count; $i++) {
                // Seleccionar cuenta personal del person si existe, sino una personal aleatoria
                $personAccount = $accounts->where('person_id', $person->id)->first();
                if (!$personAccount) {
                    if ($personalAccounts->isEmpty()) {
                        // si no hay cuentas personales, saltar
                        continue;
                    }
                    $personAccount = $personalAccounts->random();
                }

                // Crear transferencias válidas: entre tesorería y cuenta personal
                if (!$treasury) {
                    // si no hay tesorería registrada, saltar
                    continue;
                }

                // Alternar dirección de la transferencia
                if (rand(0,1) === 0) {
                    $from = $treasury;
                    $to = $personAccount;
                } else {
                    $from = $personAccount;
                    $to = $treasury;
                }

                $user = $users->random();

                $amount = rand(1000, 500000); // montos razonables

                $date = now()->subMonths(rand(0, 24))->subDays(rand(0, 30))->subHours(rand(0,23));

                $record = [
                    'transaction_number' => 'PTX-' . $person->id . '-' . Str::upper(Str::random(6)) . '-' . time() . rand(100,999),
                    'type' => 'transfer',
                    'from_account_id' => $from->id,
                    'to_account_id' => $to->id,
                    'amount' => $amount,
                    'description' => 'Transacción generada para ' . $person->full_name,
                    'notes' => null,
                    'created_by' => $user->id,
                    'approved_by' => $user->id,
                    'status' => 'approved',
                    'approved_at' => $date,
                    'created_at' => $date,
                    'updated_at' => $date,
                    'is_enabled' => true,
                ];

                // Log de depuración: mostrar intento
                $this->command->line("Intentando insert: person={$person->id} from={$from->id} to={$to->id} amount={$amount} date={$date}");
                try {
                    DB::table('transactions')->insert($record);
                    $createdForPerson++;
                    $totalCreated++;
                } catch (\Throwable $e) {
                    // mostrar advertencia y seguir
                    $this->command->warn('No se pudo insertar transacción para persona '.$person->id.': '.$e->getMessage());
                    continue;
                }
            }
            $this->command->info("Persona {$person->id} ({$person->full_name}): transacciones creadas: {$createdForPerson}");
        }

        $this->command->info('Transacciones por persona generadas. Total intentadas (approx): '.$totalCreated);
    }
}
