<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccountType;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountTypes = [
            [
                'name' => 'Cuenta Corriente',
                'description' => 'Cuenta bancaria que permite realizar múltiples operaciones como depósitos, giros y transferencias sin límite'
            ],
            [
                'name' => 'Cuenta Vista',
                'description' => 'Cuenta de depósito simple que permite ahorrar dinero con acceso inmediato a los fondos'
            ],
            [
                'name' => 'Cuenta de Ahorro',
                'description' => 'Cuenta diseñada para el ahorro con posibles beneficios en tasas de interés'
            ],
            [
                'name' => 'Cuenta RUT',
                'description' => 'Cuenta básica gratuita asociada al RUT, ideal para recibir sueldos y realizar operaciones básicas'
            ],
            [
                'name' => 'Chequera Electrónica',
                'description' => 'Cuenta corriente que opera completamente de forma digital, sin chequeras físicas'
            ]
        ];

        foreach ($accountTypes as $accountType) {
            AccountType::firstOrCreate(
                ['name' => $accountType['name']], // Campo único para verificar
                [
                    'description' => $accountType['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
