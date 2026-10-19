<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Solo local: personas de ejemplo con su cuenta en $0. Los movimientos se crean usando la
 * aplicación (ingreso a caja chica, entregas, rendiciones) para que todo cuadre.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDataSeeder no se ejecuta en producción.');
            return;
        }

        $this->call([
            DatabaseSeeder::class,
            PersonSeeder::class,
            AccountSeeder::class,
        ]);
    }
}
