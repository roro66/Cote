<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class TransactionDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Transaction::with(['fromAccount', 'toAccount', 'creator'])
                ->select('transactions.*');
            
            // Filtrar por estado si se especifica
            if ($request->has('status') && $request->status != 'all') {
                $data->where('status', $request->status);
            }
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('type_spanish', function($row) {
                    return match($row->type) {
                        'transfer' => 'Transferencia',
                        'payment' => 'Pago',
                        'adjustment' => 'Ajuste',
                        default => ucfirst($row->type)
                    };
                })
                ->addColumn('from_account_name', function($row) {
                    return $row->fromAccount ? $row->fromAccount->name : 'N/A';
                })
                ->addColumn('to_account_name', function($row) {
                    return $row->toAccount ? $row->toAccount->name : 'N/A';
                })
                ->addColumn('amount_formatted', function($row) {
                    return '$' . number_format($row->amount, 0, ',', '.');
                })
                ->addColumn('creator_name', function($row) {
                    return $row->creator ? $row->creator->name : 'N/A';
                })
                ->addColumn('status_spanish', function($row) {
                    $statusText = match($row->status) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'completed' => 'Completada',
                        default => ucfirst($row->status)
                    };
                    $class = match($row->status) {
                        'pending' => 'bg-warning text-dark',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'completed' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    return '<span class="badge '.$class.'">'.$statusText.'</span>';
                })
                ->addColumn('created_at_formatted', function($row) {
                    return $row->created_at->format('d/m/Y H:i');
                })
                ->addColumn('action', function($row) {
                    $actionBtn = '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-info btn-sm" onclick="viewTransaction('.$row->id.')">
                            <i class="fas fa-eye"></i> Ver
                        </button>';
                    
                    if ($row->status === 'pending') {
                        $actionBtn .= '<button type="button" class="btn btn-success btn-sm" onclick="approveTransaction('.$row->id.')">
                            <i class="fas fa-check"></i> Aprobar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectTransaction('.$row->id.')">
                            <i class="fas fa-times"></i> Rechazar
                        </button>';
                    }
                    
                    $actionBtn .= '</div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'status_spanish'])
                ->make(true);
        }
        
        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
