<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador de prueba
        User::firstOrCreate([
            'email' => 'admin@cote.com'
        ], [
            'name' => 'Administrador',
            'email' => 'admin@cote.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_enabled' => true,
        ]);

        // Crear usuario tesorero de prueba
        User::firstOrCreate([
            'email' => 'tesorero@cote.com'
        ], [
            'name' => 'Tesorero Principal',
            'email' => 'tesorero@cote.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_enabled' => true,
        ]);

        $this->command->info('Usuarios de prueba creados exitosamente.');
        $this->command->info('Email: admin@cote.com | Password: password123');
        $this->command->info('Email: tesorero@cote.com | Password: password123');
    }
}
