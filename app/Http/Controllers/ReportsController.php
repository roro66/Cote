<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\ExcelExportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportsController extends Controller
{
    protected $reportService;
    protected $excelService;

    public function __construct(ReportService $reportService, ExcelExportService $excelService)
    {
        $this->reportService = $reportService;
        $this->excelService = $excelService;
    }

    /**
     * Mostrar la página principal de informes
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Endpoint de prueba para verificar filtros
     */
    public function testFilters()
    {
        $startDate = '2024-08-01';
        $endDate = '2025-01-31';
        
        $approvedOnly = $this->reportService->generateMonthlyExpenseReport(
            $startDate, $endDate, 'summary', 'approved_only', false
        );
        
        $all = $this->reportService->generateMonthlyExpenseReport(
            $startDate, $endDate, 'summary', 'all', false
        );
        
        return response()->json([
            'period' => "$startDate al $endDate",
            'approved_only' => [
                'total_expenses' => $approvedOnly['total_expenses'],
                'total_amount' => $approvedOnly['total_amount']
            ],
            'all' => [
                'total_expenses' => $all['total_expenses'],
                'total_amount' => $all['total_amount']
            ],
            'difference' => [
                'expenses' => $all['total_expenses'] - $approvedOnly['total_expenses'],
                'amount' => $all['total_amount'] - $approvedOnly['total_amount']
            ]
        ]);
    }

    /**
     * Generar informe de gastos mensuales
     */
    public function monthlyExpenses(Request $request)
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'report_type' => 'required|in:summary,detailed',
                'approval_status' => 'required|in:approved_only,all',
                'include_documents' => 'boolean'
            ]);

            $data = $this->reportService->generateMonthlyExpenseReport(
                $request->start_date,
                $request->end_date,
                $request->report_type,
                $request->approval_status,
                $request->boolean('include_documents', false)
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'report_info' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'report_type' => $request->report_type,
                    'approval_status' => $request->approval_status,
                    'include_documents' => $request->boolean('include_documents', false)
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error generating monthly expense report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar informe de gastos mensuales a Excel
     */
    public function exportMonthlyExpenses(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'required|in:summary,detailed',
            'approval_status' => 'required|in:approved_only,all',
            'include_documents' => 'boolean'
        ]);

        $data = $this->reportService->generateMonthlyExpenseReport(
            $request->start_date,
            $request->end_date,
            $request->report_type,
            $request->approval_status,
            $request->boolean('include_documents', false)
        );

        $filename = 'gastos_mensuales_' . 
                   Carbon::parse($request->start_date)->format('Y-m-d') . '_a_' . 
                   Carbon::parse($request->end_date)->format('Y-m-d') . '.xlsx';

        return $this->excelService->exportMonthlyExpenseReport($data, $request->all(), $filename);
    }
}