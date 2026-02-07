<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Person;
use App\Models\Bank;
use App\Models\AccountType;

class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener algunos bancos y tipos de cuenta para asignar a las personas
        $bankChile = Bank::where('name', 'Banco de Chile')->first();
        $bankEstado = Bank::where('name', 'Banco del Estado de Chile')->first();
        $bankSantander = Bank::where('name', 'Banco Santander Chile')->first();
        
        $cuentaCorriente = AccountType::where('name', 'Cuenta Corriente')->first();
        $cuentaVista = AccountType::where('name', 'Cuenta Vista')->first();
        $cuentaRut = AccountType::where('name', 'Cuenta RUT')->first();

        $people = [
            [
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'rut' => '12345678-5',
                'email' => 'carlos.mendoza@cote.com',
                'phone' => '987654321',
                'role_type' => 'tesorero',
                'bank_id' => $bankChile?->id,
                'account_type_id' => $cuentaCorriente?->id,
                'account_number' => '123456789',
                'is_enabled' => true,
            ],
            [
                'first_name' => 'María',
                'last_name' => 'García',
                'rut' => '23456789-6',
                'email' => 'maria.garcia@cote.com',
                'phone' => '987654322',
                'role_type' => 'trabajador',
                'bank_id' => $bankEstado?->id,
                'account_type_id' => $cuentaRut?->id,
                'account_number' => '987654321',
                'is_enabled' => true,
            ],
            [
                'first_name' => 'Luis',
                'last_name' => 'Rodríguez',
                'rut' => '34567890-7',
                'email' => 'luis.rodriguez@cote.com',
                'phone' => '987654323',
                'role_type' => 'trabajador',
                'bank_id' => $bankSantander?->id,
                'account_type_id' => $cuentaVista?->id,
                'account_number' => '555666777',
                'is_enabled' => true,
            ],
            [
                'first_name' => 'Ana',
                'last_name' => 'López',
                'rut' => '45678901-8',
                'email' => 'ana.lopez@cote.com',
                'phone' => '987654324',
                'role_type' => 'tesorero',
                'bank_id' => $bankChile?->id,
                'account_type_id' => $cuentaCorriente?->id,
                'account_number' => '111222333',
                'is_enabled' => true,
            ],
            [
                'first_name' => 'Pedro',
                'last_name' => 'Sánchez',
                'rut' => '56789012-9',
                'email' => 'pedro.sanchez@cote.com',
                'phone' => '987654325',
                'role_type' => 'trabajador',
                'bank_id' => $bankEstado?->id,
                'account_type_id' => $cuentaRut?->id,
                'account_number' => '444555666',
                'is_enabled' => true,
            ],
        ];

        foreach ($people as $personData) {
            Person::firstOrCreate(
                ['rut' => $personData['rut']], // Campo único para verificar
                $personData // Datos a crear si no existe
            );
        }
    }
}
