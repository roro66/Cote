<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Expense;
use App\Services\TransactionService;
use App\Services\ExpenseService;
use Illuminate\Console\Command;

class GenerateTreasuryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:report {--start-date=} {--end-date=} {--format=table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un reporte de tesorería para un período específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('start-date') ?? now()->startOfMonth()->toDateString();
        $endDate = $this->option('end-date') ?? now()->endOfMonth()->toDateString();
        $format = $this->option('format');

        $this->info("Generando reporte de tesorería del {$startDate} al {$endDate}");
        $this->newLine();

        // Resumen de cuentas
        $this->generateAccountSummary();
        
        // Resumen de transacciones
        $this->generateTransactionSummary($startDate, $endDate);
        
        // Resumen de gastos
        $this->generateExpenseSummary($startDate, $endDate);
        
        $this->newLine();
        $this->info('Reporte generado exitosamente.');
    }

    private function generateAccountSummary()
    {
        $this->info('=== RESUMEN DE CUENTAS ===');
        
        $accounts = Account::where('is_enabled', true)
            ->selectRaw('type, COUNT(*) as count, SUM(balance) as total_balance')
            ->groupBy('type')
            ->get();

        $headers = ['Tipo', 'Cantidad', 'Saldo Total'];
        $rows = [];

        foreach ($accounts as $account) {
            $typeName = match($account->type) {
                'tesoreria' => 'Tesorería',
                'cuadrilla' => 'Cuadrillas',
                'personal' => 'Personal',
                default => ucfirst($account->type)
            };

            $rows[] = [
                $typeName,
                $account->count,
                '$' . number_format($account->total_balance, 0, ',', '.')
            ];
        }

        $this->table($headers, $rows);
    }

    private function generateTransactionSummary($startDate, $endDate)
    {
        $this->info('=== RESUMEN DE TRANSACCIONES ===');
        
        $transactionService = new TransactionService();
        $summary = $transactionService->getTransactionSummary($startDate, $endDate);

        $this->info("Total de transacciones: {$summary['total_transactions']}");
        $this->info("Transacciones pendientes: {$summary['pending_transactions']}");
        $this->info("Transacciones aprobadas: {$summary['approved_transactions']}");
        $this->info("Monto total aprobado: $" . number_format($summary['total_amount'], 0, ',', '.'));
        $this->info("Monto pendiente: $" . number_format($summary['pending_amount'], 0, ',', '.'));
        
        $this->newLine();
    }

    private function generateExpenseSummary($startDate, $endDate)
    {
        $this->info('=== RESUMEN DE GASTOS ===');
        
        $expenseService = new ExpenseService();
        $summary = $expenseService->getExpenseSummary($startDate, $endDate);

        $this->info("Total de rendiciones: {$summary['total_expenses']}");
        $this->info("En borrador: {$summary['draft_expenses']}");
        $this->info("Enviadas: {$summary['submitted_expenses']}");
        $this->info("Aprobadas: {$summary['approved_expenses']}");
        $this->info("Rechazadas: {$summary['rejected_expenses']}");
        $this->info("Monto total aprobado: $" . number_format($summary['total_amount'], 0, ',', '.'));
        $this->info("Monto pendiente: $" . number_format($summary['pending_amount'], 0, ',', '.'));
    }
}
