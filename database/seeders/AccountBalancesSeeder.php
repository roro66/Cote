<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountBalancesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Number of accounts to set with non-zero balance
        $target = 10;

        $accounts = Account::where('is_enabled', true)
            ->where('type', '!=', 'treasury')
            ->where('balance', 0)
            ->take($target)
            ->get();

        if ($accounts->isEmpty()) {
            $this->command->info('AccountBalancesSeeder: no matching accounts found to update.');
            return;
        }

        foreach ($accounts as $acc) {
            // random balance between 10.000 and 1.000.000 CLP
            $amount = rand(10000, 1000000);
            $acc->update(['balance' => $amount]);
            $this->command->info("AccountBalancesSeeder: account {$acc->id} ({$acc->name}) set to {$amount}");
        }

        $this->command->info('AccountBalancesSeeder: done.');
    }
}
