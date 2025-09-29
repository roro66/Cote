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
        $expenseQuery = Expense::with(['submitter.person', 'account.person', 'items.category'])
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
                $categoryName = $item->category ? $item->category->name : 'Sin categoría';
                
                if (!isset($categorySummary[$categoryName])) {
                    $categorySummary[$categoryName] = [
                        'category' => $categoryName,
                        'total_amount' => 0,
                        'items_count' => 0,
                        'expenses_count' => 0
                    ];
                }

                $categorySummary[$categoryName]['total_amount'] += $item->amount;
                $categorySummary[$categoryName]['items_count']++;
                $totalGeneral += $item->amount;
            }
        }

        // Contar expenses únicos por categoría
        foreach ($expenses as $expense) {
            $categoriesInExpense = [];
            foreach ($expense->items as $item) {
                $categoryName = $item->category ? $item->category->name : 'Sin categoría';
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
                $categoryName = $item->category ? $item->category->name : 'Sin categoría';
                
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
                    'submitter' => $expense->submitter->person->first_name . ' ' . $expense->submitter->person->last_name,
                    'item_description' => $item->description,
                    'amount' => $item->amount,
                    'receipt_number' => $item->receipt_number,
                    'expense_status' => $expense->status
                ];

                // Incluir documentos si se solicita
                if ($includeDocuments && $item->hasMedia('receipts')) {
                    $itemData['documents'] = $item->getMedia('receipts')->map(function ($media) {
                        return [
                            'filename' => $media->file_name,
                            'url' => $media->getFullUrl(),
                            'mime_type' => $media->mime_type,
                            'size' => $media->size
                        ];
                    })->toArray();
                } else {
                    $itemData['documents'] = [];
                }

                $categoryDetails[$categoryName]['items'][] = $itemData;
                $categoryDetails[$categoryName]['total_amount'] += $item->amount;
                $totalGeneral += $item->amount;
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