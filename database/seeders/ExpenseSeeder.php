<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = Account::all();
        $users = User::all();
        $people = \App\Models\Person::all();
        
        if ($accounts->count() < 1 || $users->count() < 1 || $people->count() < 1) {
            $this->command->warn('Necesitas al menos 1 cuenta, 1 usuario y 1 persona para crear gastos.');
            return;
        }

        $cuadrillaAccounts = $accounts->where('type', 'cuadrilla');
        $adminUser = $users->first();
        $firstPerson = $people->first();

        // Gastos de ejemplo
        $expenses = [
            [
                'expense_number' => 'RND-' . date('Y') . '-001',
                'account_id' => $cuadrillaAccounts->first() ? $cuadrillaAccounts->first()->id : $accounts->first()->id,
                'submitted_by' => $firstPerson->id,
                'description' => 'Rendición de gastos - Cuadrilla Norte',
                'total_amount' => 85000.00,
                'reviewed_by' => null,
                'status' => 'submitted',
                'expense_date' => now()->subDays(3),
                'submitted_at' => now()->subDays(1),
            ],
            [
                'expense_number' => 'RND-' . date('Y') . '-002',
                'account_id' => $cuadrillaAccounts->skip(1)->first() ? $cuadrillaAccounts->skip(1)->first()->id : $accounts->skip(1)->first()->id,
                'submitted_by' => $people->skip(1)->first() ? $people->skip(1)->first()->id : $firstPerson->id,
                'description' => 'Rendición de gastos - Cuadrilla Sur',
                'total_amount' => 120000.00,
                'reviewed_by' => $adminUser->id,
                'status' => 'approved',
                'expense_date' => now()->subDays(7),
                'submitted_at' => now()->subDays(6),
                'reviewed_at' => now()->subDays(5),
            ],
        ];

        $i = 0;
        foreach ($expenses as $expenseData) {
            $expense = Expense::firstOrCreate(
                ['expense_number' => $expenseData['expense_number']], // Unique field to check
                $expenseData // Data to create if not exists
            );
            
            // Solo crear items si el gasto no tenía items previamente
            if ($expense->items()->count() == 0) {
                // Crear items de gasto para cada rendición
                if ($i % 2 == 0) {
                $items = [
                    [
                        'expense_id' => $expense->id,
                        'document_type' => 'factura',
                        'document_number' => '001-00' . ($i + 100),
                        'vendor_name' => 'Copec',
                        'description' => 'Combustible',
                        'amount' => 45000.00,
                        'expense_date' => now()->subDays(rand(1, 30)),
                        'category' => 'combustible',
                    ],
                    [
                        'expense_id' => $expense->id,
                        'document_type' => 'boleta',
                        'document_number' => '002-00' . ($i + 200),
                        'vendor_name' => 'Ferretería Los Andes',
                        'description' => 'Materiales de construcción',
                        'amount' => 40000.00,
                        'expense_date' => now()->subDays(rand(1, 30)),
                        'category' => 'materiales',
                    ],
                ];
            } else {
                $items = [
                    [
                        'expense_id' => $expense->id,
                        'document_type' => 'factura',
                        'document_number' => '003-00' . ($i + 300),
                        'vendor_name' => 'Sodimac',
                        'description' => 'Herramientas',
                        'amount' => 80000.00,
                        'expense_date' => now()->subDays(rand(1, 30)),
                        'category' => 'herramientas',
                    ],
                    [
                        'expense_id' => $expense->id,
                        'document_type' => 'ticket',
                        'document_number' => null,
                        'vendor_name' => 'Restaurant El Buen Sabor',
                        'description' => 'Viáticos',
                        'amount' => 40000.00,
                        'expense_date' => now()->subDays(rand(1, 30)),
                        'category' => 'viaticos',
                    ],
                ];
            }
            
            foreach ($items as $itemData) {
                ExpenseItem::create($itemData);
            }
            } // Cerrar la condición de items count == 0
            
            $i++; // Increment counter
        }

        $this->command->info('Gastos de ejemplo creados exitosamente.');
    }
}
