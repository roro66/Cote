<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class FundingAccountSeeder extends Seeder
{
    public function run(): void
    {
        $fondeoName = config('coteso.fondeo_account_name');
        Account::firstOrCreate(
            [
                'type' => 'person',
                'name' => $fondeoName,
            ],
            [
                'person_id' => null,
                'balance' => 100000000, // 100 millones CLP para pruebas
                'notes' => 'Cuenta institucional para fondear Tesorería en entornos de prueba',
                'is_enabled' => true,
                'is_fondeo' => true,
            ]
        );
    }
}
