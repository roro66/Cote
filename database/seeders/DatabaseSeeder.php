<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Datos base, seguros para producción y repetibles: no crea movimientos ni saldos.
 * Para datos de demostración en local: php artisan db:seed --class=DemoDataSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            BankSeeder::class,
            AccountTypeSeeder::class,
            ExpenseCategorySeeder::class,
            TreasuryAccountSeeder::class,
            InitialUsersSeeder::class,
        ]);
    }
}
