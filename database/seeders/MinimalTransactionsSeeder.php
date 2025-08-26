<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MinimalTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();
        $admin = User::where('email', 'admin@coteso.com')->first() ?? User::first();

        // Crear/asegurar cuenta de Tesorería
        $treasury = Account::firstOrCreate([
            'name' => 'Tesorería General',
            'type' => 'treasury',
        ], [
            'person_id' => null,
            'balance' => 1_000_000,
            'notes' => 'Cuenta creada para pruebas de movimientos',
            'is_enabled' => true,
        ]);

        $personalAccounts = Account::where('type', 'person')->get();

        foreach ($personalAccounts as $acc) {
            // Transferencia desde Tesorería a la cuenta (aprobada)
            $amountIn = rand(50_000, 150_000);
            $txIn = Transaction::create([
                'type' => 'transfer',
                'from_account_id' => $treasury->id,
                'to_account_id' => $acc->id,
                'amount' => $amountIn,
                'description' => 'Aporte inicial desde Tesorería',
                'notes' => null,
                'created_by' => $admin?->id,
                'approved_by' => $admin?->id,
                'status' => 'approved',
                'approved_at' => now(),
                'is_enabled' => true,
            ]);
            // Ajuste de saldos simple
            Account::whereKey($treasury->id)->decrement('balance', $amountIn);
            Account::whereKey($acc->id)->increment('balance', $amountIn);

            // Transferencia de reintegro desde la cuenta a Tesorería (pendiente o aprobado)
            $amountOut = rand(10_000, 60_000);
            $status = rand(0, 1) ? 'pending' : 'approved';
            $txOut = Transaction::create([
                'type' => 'transfer',
                'from_account_id' => $acc->id,
                'to_account_id' => $treasury->id,
                'amount' => $amountOut,
                'description' => 'Pago de reintegro a Tesorería',
                'notes' => null,
                'created_by' => $admin?->id,
                'approved_by' => $status === 'approved' ? $admin?->id : null,
                'status' => $status,
                'approved_at' => $status === 'approved' ? now() : null,
                'is_enabled' => true,
            ]);
            if ($status === 'approved') {
                Account::whereKey($acc->id)->decrement('balance', $amountOut);
                Account::whereKey($treasury->id)->increment('balance', $amountOut);
            }
        }
    }
}
