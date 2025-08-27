<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class FundingAccountSeeder extends Seeder
{
    public function run(): void
    {
        Account::firstOrCreate(
            [
                'type' => 'person',
                'name' => 'Fondeo del Sistema',
            ],
            [
                'person_id' => null,
                'balance' => 100000000, // 100 millones CLP para pruebas
                'notes' => 'Cuenta institucional para fondear Tesorería en entornos de prueba',
                'is_enabled' => true,
            ]
        );
    }
}
