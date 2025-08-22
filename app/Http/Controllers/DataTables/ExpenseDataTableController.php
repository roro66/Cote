<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class ExpenseDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Expense::with(['account', 'submitter', 'reviewedBy'])
                ->select('expenses.*');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('title', function($row) {
                    return $row->description;
                })
                ->addColumn('submitter_name', function($row) {
                    return $row->submitter ? $row->submitter->full_name : 'N/A';
                })
                ->addColumn('account_name', function($row) {
                    return $row->account ? $row->account->name : 'N/A';
                })
                ->addColumn('total_amount_formatted', function($row) {
                    return '$' . number_format($row->total_amount, 0, ',', '.');
                })
                ->addColumn('status_spanish', function($row) {
                    $statusText = match($row->status) {
                        'draft' => 'Borrador',
                        'submitted' => 'Enviado',
                        'reviewed' => 'Revisado',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'paid' => 'Pagado',
                        default => ucfirst($row->status)
                    };
                    $class = match($row->status) {
                        'draft' => 'bg-secondary',
                        'submitted' => 'bg-warning text-dark',
                        'reviewed' => 'bg-info',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'paid' => 'bg-primary',
                        default => 'bg-secondary'
                    };
                    return '<span class="badge '.$class.'">'.$statusText.'</span>';
                })
                ->addColumn('submitted_at_formatted', function($row) {
                    return $row->submitted_at ? $row->submitted_at->format('d/m/Y H:i') : '-';
                })
                ->addColumn('action', function($row) {
                    $actionBtn = '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-info btn-sm" onclick="viewExpense('.$row->id.')">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="editExpense('.$row->id.')">
                            <i class="fas fa-edit"></i> Editar
                        </button>';
                    
                    if ($row->status === 'submitted') {
                        $actionBtn .= '<button type="button" class="btn btn-success btn-sm" onclick="approveExpense('.$row->id.')">
                            <i class="fas fa-check"></i> Aprobar
                        </button>';
                    }
                    
                    $actionBtn .= '<button type="button" class="btn btn-danger btn-sm" onclick="deleteExpense('.$row->id.')">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                    </div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'status_spanish'])
                ->make(true);
        }
        
        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
