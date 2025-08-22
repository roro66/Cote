<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = Account::all();
        $users = User::all();
        
        if ($accounts->count() < 2 || $users->count() < 1) {
            $this->command->warn('Necesitas al menos 2 cuentas y 1 usuario para crear transacciones.');
            return;
        }

        $tesoreriaAccount = $accounts->where('type', 'tesoreria')->first();
        $cuadrillaAccounts = $accounts->where('type', 'cuadrilla');
        $personalAccounts = $accounts->where('type', 'personal');
        
        $adminUser = $users->first();

        // Transacciones de ejemplo
        $transactions = [
            [
                'transaction_number' => 'TXN-' . date('Y') . '-001',
                'type' => 'transfer',
                'from_account_id' => $tesoreriaAccount ? $tesoreriaAccount->id : $accounts->first()->id,
                'to_account_id' => $cuadrillaAccounts->first() ? $cuadrillaAccounts->first()->id : $accounts->skip(1)->first()->id,
                'amount' => 500000.00,
                'description' => 'Transferencia para gastos de cuadrilla Norte',
                'notes' => 'Fondos para materiales y viáticos',
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
                'status' => 'approved',
                'approved_at' => now(),
            ],
            [
                'transaction_number' => 'TXN-' . date('Y') . '-002',
                'type' => 'transfer',
                'from_account_id' => $tesoreriaAccount ? $tesoreriaAccount->id : $accounts->first()->id,
                'to_account_id' => $cuadrillaAccounts->skip(1)->first() ? $cuadrillaAccounts->skip(1)->first()->id : $accounts->skip(2)->first()->id,
                'amount' => 300000.00,
                'description' => 'Transferencia para gastos de cuadrilla Sur',
                'notes' => 'Fondos para proyecto específico',
                'created_by' => $adminUser->id,
                'approved_by' => null,
                'status' => 'pending',
                'approved_at' => null,
            ],
            [
                'transaction_number' => 'TXN-' . date('Y') . '-003',
                'type' => 'payment',
                'from_account_id' => $cuadrillaAccounts->first() ? $cuadrillaAccounts->first()->id : $accounts->skip(1)->first()->id,
                'to_account_id' => $personalAccounts->first() ? $personalAccounts->first()->id : $accounts->skip(3)->first()->id,
                'amount' => 150000.00,
                'description' => 'Pago a trabajador por servicios',
                'notes' => 'Pago de honorarios',
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
                'status' => 'approved',
                'approved_at' => now()->subDays(5),
            ],
            [
                'transaction_number' => 'TXN-' . date('Y') . '-004',
                'type' => 'adjustment',
                'from_account_id' => $tesoreriaAccount ? $tesoreriaAccount->id : $accounts->first()->id,
                'to_account_id' => $cuadrillaAccounts->first() ? $cuadrillaAccounts->first()->id : $accounts->skip(1)->first()->id,
                'amount' => 50000.00,
                'description' => 'Ajuste contable',
                'notes' => 'Corrección de saldo',
                'created_by' => $adminUser->id,
                'approved_by' => $adminUser->id,
                'status' => 'completed',
                'approved_at' => now()->subDays(2),
            ],
        ];

        foreach ($transactions as $transactionData) {
            Transaction::firstOrCreate(
                ['transaction_number' => $transactionData['transaction_number']], // Unique field to check
                $transactionData // Data to create if not exists
            );
        }

        $this->command->info('Transacciones de ejemplo creadas exitosamente.');
    }
}
