<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Cuenta Principal Tesorería',
                'type' => 'treasury',
                'person_id' => null,
                'team_id' => null,
                'balance' => 25000.00,
                'notes' => 'Cuenta principal de la tesorería para manejo de fondos centrales',
                'is_enabled' => true,
            ],
            [
                'name' => 'Cuenta Operaciones Norte',
                'type' => 'team',
                'person_id' => null,
                'team_id' => 1, // Cuadrilla Norte
                'balance' => 5000.00,
                'notes' => 'Cuenta asignada para operaciones del equipo norte',
                'is_enabled' => true,
            ],
            [
                'name' => 'Cuenta Operaciones Sur',
                'type' => 'team',
                'person_id' => null,
                'team_id' => 2, // Cuadrilla Sur
                'balance' => 4500.00,
                'notes' => 'Cuenta asignada para operaciones del equipo sur',
                'is_enabled' => true,
            ],
            [
                'name' => 'Cuenta Mantenimiento',
                'type' => 'team',
                'person_id' => null,
                'team_id' => 4, // Equipo Mantenimiento
                'balance' => 3000.00,
                'notes' => 'Cuenta para gastos de mantenimiento de equipos',
                'is_enabled' => true,
            ],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }
}
