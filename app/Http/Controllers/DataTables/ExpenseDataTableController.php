<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Helpers\DatabaseHelper;
use App\Models\Expense;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class ExpenseDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Calificar is_enabled con el nombre de la tabla para evitar
            // ambigüedades cuando se hacen joins (por ejemplo people tiene is_enabled)
            $data = Expense::with(['account', 'submitter', 'reviewedBy'])
                ->where('expenses.is_enabled', true)
                ->select('expenses.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('title', function ($row) {
                    return $row->description;
                })
                // Soporte para ordenar por título (description)
                ->orderColumn('title', 'expenses.description $1')
                ->addColumn('submitter_name', function ($row) {
                    return $row->submitter ? $row->submitter->full_name : 'N/A';
                })
                // Orden por solicitante (submitter)
                ->orderColumn('submitter_name', function ($query, $order) {
                    $query->leftJoin('people', 'expenses.submitted_by', '=', 'people.id')
                          ->orderBy('people.first_name', $order)
                          ->orderBy('people.last_name', $order);
                })
                ->addColumn('account_name', function ($row) {
                    return $row->account ? $row->account->name : 'N/A';
                })
                ->orderColumn('account_name', function ($query, $order) {
                    $query->leftJoin('accounts', 'expenses.account_id', '=', 'accounts.id')
                          ->orderBy('accounts.name', $order);
                })
                ->addColumn('total_amount_formatted', function ($row) {
                    return '$' . number_format($row->total_amount, 0, ',', '.');
                })
                ->orderColumn('total_amount_formatted', 'expenses.total_amount $1')
                ->addColumn('status_spanish', function ($row) {
                    $statusText = match ($row->status) {
                        'draft' => 'Borrador',
                        'submitted' => 'Enviado',
                        'reviewed' => 'Revisado',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'paid' => 'Pagado',
                        default => ucfirst($row->status)
                    };
                    $class = match ($row->status) {
                        'draft' => 'bg-secondary',
                        'submitted' => 'bg-warning text-dark',
                        'reviewed' => 'bg-info',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'paid' => 'bg-primary',
                        default => 'bg-secondary'
                    };
                    return '<span class="badge ' . $class . '">' . $statusText . '</span>';
                })
                // Orden por estado (campo real: expenses.status)
                ->orderColumn('status_spanish', 'expenses.status $1')
                ->addColumn('submitted_at_formatted', function ($row) {
                    return $row->submitted_at ? $row->submitted_at->format('d/m/Y H:i') : '-';
                })
                ->orderColumn('submitted_at_formatted', 'expenses.submitted_at $1')
                // Global case-insensitive search
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $s = $request->search['value'];
                        $query->where(function ($q) use ($s) {
                            $q->whereRaw(DatabaseHelper::likeExpression('expenses.description'), ["%{$s}%"])
                              ->orWhereRaw(DatabaseHelper::likeExpression('expenses.expense_number'), ["%{$s}%"])
                              ->orWhereHas('submitter', function ($sq) use ($s) {
                                  $sq->whereRaw(DatabaseHelper::likeExpression("CONCAT(first_name,' ',last_name)"), ["%{$s}%"]);
                              })
                              ->orWhereHas('account', function ($aq) use ($s) {
                                  $aq->whereRaw(DatabaseHelper::likeExpression('accounts.name'), ["%{$s}%"]);
                              });
                        });
                    }
                })
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group" role="group">
                        <a href="' . route('expenses.show', $row->id) . '" class="btn btn-info btn-sm" title="Ver" aria-label="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="' . route('expenses.edit', $row->id) . '" class="btn btn-primary btn-sm" title="Editar" aria-label="Editar">
                            <i class="fas fa-edit"></i>
                        </a>';

                    if ($row->status === 'submitted') {
                        $actionBtn .= '<form action="' . route('approvals.expenses.approve', $row->id) . '" method="POST" style="display:inline;">
                            <input type="hidden" name="_token" value="' . csrf_token() . '">
                            <button type="submit" class="btn btn-success btn-sm" title="Aprobar" aria-label="Aprobar" onclick="return confirm(\'¿Aprobar esta rendición?\')">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>';
                    }

                    $actionBtn .= '<button type="button" class="btn btn-danger btn-sm" title="Eliminar" aria-label="Eliminar" onclick="deleteExpense(' . $row->id . ')">
                        <i class="fas fa-trash"></i>
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
