<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Asegurar roles y permisos base
        $this->call([RolePermissionSeeder::class]);

        // Crear/actualizar usuario admin por defecto y asignar rol 'boss'
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@coteso.com'],
            [
                'name' => 'Administrador',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'email_verified_at' => now(),
                'is_enabled' => true,
            ]
        );
        if (!$admin->hasRole('boss')) {
            $admin->assignRole('boss');
        }

        // Crear/actualizar usuario tesorero por defecto y asignar rol 'treasurer'
        $treasurer = \App\Models\User::firstOrCreate(
            ['email' => 'tesorero@coteso.com'],
            [
                'name' => 'Tesorero Principal',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'email_verified_at' => now(),
                'is_enabled' => true,
            ]
        );
        if (!$treasurer->hasRole('treasurer')) {
            $treasurer->assignRole('treasurer');
        }

        // Ejecutar seeders en orden correcto
    $this->call([
            TreasuryAccountSeeder::class, // Asegura cuenta Tesorería
            FundingAccountSeeder::class, // Cuenta institucional de fondeo para pruebas
            BankSeeder::class,      // Crea bancos
            AccountTypeSeeder::class, // Crea tipos de cuenta
            PersonSeeder::class,    // Crea personas (sin teams)
            AccountSeeder::class,   // Crea cuentas (depende de personas)
            TransactionSeeder::class, // Crea transacciones (depende de cuentas y usuarios)
            ExpenseSeeder::class,   // Crea gastos (depende de cuentas y usuarios)
            \Database\Seeders\TreasurySeeder::class,
        ]);

        // Opcional: sembrar datos amplios para estadísticas (24 meses por persona)
        // Actívalo exportando STATS_DEMO=1 para evitar sobrecargar entornos por defecto
        if (env('STATS_DEMO', false)) {
            $this->call([StatisticsDataSeeder::class]);
        }
    }
}
