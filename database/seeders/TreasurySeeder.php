<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Person;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Hash;

class TreasurySeeder extends Seeder
{
    public function run(): void
    {
        // Create treasurer role
        Role::firstOrCreate(['name' => 'treasurer']);

        // Ensure there's a user for treasurer
        $user = User::where('email', 'treasurer@coteso.local')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Tesorero Sistema',
                'email' => 'treasurer@coteso.local',
                'password' => Hash::make('secret123'),
            ]);
            $user->assignRole('treasurer');
        }

        // Ensure person exists and is protected
        $person = Person::where('first_name', 'Tesorero')->where('last_name', 'Sistema')->first();
        if (!$person) {
            $person = Person::create([
                'first_name' => 'Tesorero',
                'last_name' => 'Sistema',
                'rut' => uniqid('RUT'),
                'email' => 'treasurer.person@coteso.local',
                'is_enabled' => true,
                'is_protected' => true,
                'role_type' => 'tesorero'
            ]);
        } else {
            $person->update([
                'is_protected' => true,
                'role_type' => 'tesorero',
                'rut' => $person->rut ?? uniqid('RUT')
            ]);
        }

        // Link user to person if not linked
        if (!$user->person_id) {
            $user->person_id = $person->id;
            $user->save();
        }

        // Ensure treasury account exists and is protected (use existing TreasuryAccountSeeder entry)
        $treasury = Account::where('type', 'treasury')->first();
        if ($treasury) {
            $treasury->update([
                'name' => $treasury->name ?? 'Tesorería',
                'is_protected' => true,
                'is_fondeo' => $treasury->is_fondeo ?? false,
            ]);
        } else {
            // fallback: create but use name 'Tesorería'
            $treasury = Account::create([
                'name' => 'Tesorería',
                'type' => 'treasury',
                'balance' => 0,
                'is_enabled' => true,
                'is_fondeo' => false,
                'is_protected' => true,
            ]);
        }

        // Ensure funding account (fondeo) exists and is protected
        $fondeo = Account::where('is_fondeo', true)->first();
        if (!$fondeo) {
            // Try to find by name 'Cuenta Fondeo' or create with is_fondeo true
            $fondeo = Account::where('name', 'Cuenta Fondeo')->orWhere('is_fondeo', true)->first();
        }

        if ($fondeo) {
            $fondeo->update(['is_protected' => true, 'is_fondeo' => true, 'name' => $fondeo->name ?? 'Cuenta Fondeo']);
        } else {
            $fondeo = Account::create([
                'name' => 'Cuenta Fondeo',
                'type' => 'treasury',
                'balance' => 0,
                'is_enabled' => true,
                'is_fondeo' => true,
                'is_protected' => true,
            ]);
        }

        // Seed initial funds into fondeo via a transaction for audit
        $initial = 100000; // CLP
        $txService = app(TransactionService::class);

        // Create and approve transaction only if fondeo has insufficient funds
        if ($fondeo->balance < $initial) {
            $tx = $txService->createTransaction([
                'from_account_id' => null,
                'to_account_id' => $fondeo->id,
                'amount' => $initial,
                'description' => 'Seed: Aporte inicial al Fondeo',
                'created_by' => $user->id,
                'status' => 'pending'
            ]);

            $txService->approveTransaction($tx, $user->id);
        }
    }
}
