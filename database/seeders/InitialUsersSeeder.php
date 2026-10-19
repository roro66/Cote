<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea el jefe y el tesorero si no existen. La contraseña se toma de COTE_BOSS_PASSWORD /
 * COTE_TREASURER_PASSWORD o se genera al azar y se muestra una sola vez en la consola.
 */
class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureUser('boss', env('COTE_BOSS_EMAIL', 'admin@cote.com'), 'Administrador', env('COTE_BOSS_PASSWORD'));
        $this->ensureUser('treasurer', env('COTE_TREASURER_EMAIL', 'tesorero@cote.com'), 'Tesorero', env('COTE_TREASURER_PASSWORD'));
    }

    private function ensureUser(string $role, string $email, string $name, ?string $password): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->syncRoles([$role]);
            $this->command?->info("Usuario {$email} ya existe (rol {$role}).");
            return;
        }

        $generated = $password ?: Str::password(14, symbols: false);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($generated),
            'email_verified_at' => now(),
            'is_enabled' => true,
        ]);
        $user->assignRole($role);

        $this->command?->warn($password
            ? "Creado {$email} ({$role})."
            : "Creado {$email} ({$role}) con contraseña: {$generated}  ← guárdala, no se vuelve a mostrar.");
    }
}
