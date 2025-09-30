<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Formatear número según notación chilena
     * Sin centavos, separador de miles con punto
     */
    private function formatChileanNumber($number): int
    {
        // Convertir a float y redondear según reglas chilenas
        $num = is_numeric($number) ? (float) $number : 0;
        
        // Redondear: >=0.5 hacia arriba, <=0.4 hacia abajo
        return (int) round($num);
    }
    /**
     * Generar informe de gastos mensuales
     */
    public function generateMonthlyExpenseReport(
        string $startDate,
        string $endDate,
        string $reportType,
        string $approvalStatus,
        bool $includeDocuments = false
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Base query para expenses
        $expenseQuery = Expense::with(['submittedBy', 'account.person', 'items.categoryObj'])
            ->whereBetween('expense_date', [$start, $end]);

        // Filtrar por estado de aprobación
        if ($approvalStatus === 'approved_only') {
            $expenseQuery->where('status', 'approved');
        }

        $expenses = $expenseQuery->get();

        if ($reportType === 'summary') {
            return $this->generateSummaryReport($expenses, $start, $end);
        } else {
            return $this->generateDetailedReport($expenses, $start, $end, $includeDocuments);
        }
    }

    /**
     * Generar informe resumido por categorías
     */
    private function generateSummaryReport($expenses, $start, $end): array
    {
        $categorySummary = [];
        $totalGeneral = 0;

        foreach ($expenses as $expense) {
            foreach ($expense->items as $item) {
                $categoryName = $item->categoryObj ? $item->categoryObj->name : 'Sin categoría';
                
                if (!isset($categorySummary[$categoryName])) {
                    $categorySummary[$categoryName] = [
                        'category' => $categoryName,
                        'total_amount' => 0,
                        'items_count' => 0,
                        'expenses_count' => 0
                    ];
                }

                $categorySummary[$categoryName]['total_amount'] += $this->formatChileanNumber($item->amount);
                $categorySummary[$categoryName]['items_count']++;
                $totalGeneral += $this->formatChileanNumber($item->amount);
            }
        }

        // Contar expenses únicos por categoría
        foreach ($expenses as $expense) {
            $categoriesInExpense = [];
            foreach ($expense->items as $item) {
                $categoryName = $item->categoryObj ? $item->categoryObj->name : 'Sin categoría';
                if (!in_array($categoryName, $categoriesInExpense)) {
                    $categoriesInExpense[] = $categoryName;
                    $categorySummary[$categoryName]['expenses_count']++;
                }
            }
        }

        // Ordenar por monto total descendente
        uasort($categorySummary, function ($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });

        return [
            'report_type' => 'summary',
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d')
            ],
            'categories' => array_values($categorySummary),
            'total_amount' => $totalGeneral,
            'total_expenses' => $expenses->count(),
            'total_items' => $expenses->sum(function ($expense) {
                return $expense->items->count();
            })
        ];
    }

    /**
     * Generar informe detallado con items por categoría
     */
    private function generateDetailedReport($expenses, $start, $end, $includeDocuments): array
    {
        $categoryDetails = [];
        $totalGeneral = 0;

        foreach ($expenses as $expense) {
            foreach ($expense->items as $item) {
                $categoryName = $item->categoryObj ? $item->categoryObj->name : 'Sin categoría';
                
                if (!isset($categoryDetails[$categoryName])) {
                    $categoryDetails[$categoryName] = [
                        'category' => $categoryName,
                        'total_amount' => 0,
                        'items' => []
                    ];
                }

                $itemData = [
                    'expense_number' => $expense->expense_number,
                    'expense_date' => $expense->expense_date->format('Y-m-d'),
                    'submitter' => $expense->submittedBy ? ($expense->submittedBy->first_name . ' ' . $expense->submittedBy->last_name) : 'N/A',
                    'item_description' => $item->description,
                    'amount' => $this->formatChileanNumber($item->amount),
                    'receipt_number' => $item->receipt_number,
                    'expense_status' => $expense->status
                ];

                // Incluir documentos si se solicita (ambos sistemas)
                if ($includeDocuments) {
                    $documentsArray = [];
                    
                    // 1. Documentos del sistema tradicional (tabla documents)
                    foreach ($item->documents as $doc) {
                        $documentsArray[] = [
                            'filename' => $doc->name,
                            'url' => asset('storage/' . $doc->file_path),
                            'mime_type' => $doc->mime_type,
                            'size' => $doc->file_size
                        ];
                    }
                    
                    // 2. Documentos de Spatie MediaLibrary (colección receipts)
                    foreach ($item->getMedia('receipts') as $media) {
                        $documentsArray[] = [
                            'filename' => $media->file_name,
                            'url' => $media->getFullUrl(),
                            'mime_type' => $media->mime_type,
                            'size' => $media->size
                        ];
                    }
                    
                    $itemData['documents'] = $documentsArray;
                } else {
                    $itemData['documents'] = [];
                }

                $categoryDetails[$categoryName]['items'][] = $itemData;
                $categoryDetails[$categoryName]['total_amount'] += $this->formatChileanNumber($item->amount);
                $totalGeneral += $this->formatChileanNumber($item->amount);
            }
        }

        // Ordenar categorías por monto total descendente
        uasort($categoryDetails, function ($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });

        // Ordenar items dentro de cada categoría por fecha
        foreach ($categoryDetails as &$category) {
            usort($category['items'], function ($a, $b) {
                return $b['expense_date'] <=> $a['expense_date'];
            });
        }

        return [
            'report_type' => 'detailed',
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d')
            ],
            'categories' => array_values($categoryDetails),
            'total_amount' => $totalGeneral,
            'total_expenses' => $expenses->count(),
            'total_items' => $expenses->sum(function ($expense) {
                return $expense->items->count();
            }),
            'include_documents' => $includeDocuments
        ];
    }
}