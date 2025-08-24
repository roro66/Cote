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
        // Crear usuario admin por defecto
        \App\Models\User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@coteso.com',
            'is_enabled' => true,
        ]);

        // Ejecutar seeders en orden correcto
        $this->call([
            BankSeeder::class,      // Crea bancos
            AccountTypeSeeder::class, // Crea tipos de cuenta
            PersonSeeder::class,    // Crea personas (sin teams)
            AccountSeeder::class,   // Crea cuentas (depende de personas)
            TransactionSeeder::class, // Crea transacciones (depende de cuentas y usuarios)
            ExpenseSeeder::class,   // Crea gastos (depende de cuentas y usuarios)
        ]);
    }
}
