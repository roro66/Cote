<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class TreasuryAccountSeeder extends Seeder
{
    public function run(): void
    {
        Account::firstOrCreate(
            [
                'type' => 'treasury',
                'name' => 'Tesorería',
            ],
            [
                'person_id' => null,
                'balance' => 0,
                'notes' => 'Cuenta central de tesorería',
                'is_enabled' => true,
            ]
        );
    }
}
