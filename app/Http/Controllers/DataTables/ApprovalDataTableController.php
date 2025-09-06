<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Expense;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ApprovalDataTableController extends Controller
{
    public function transactions(Request $request)
    {
        $query = Transaction::with(['fromAccount.person', 'toAccount.person', 'createdBy'])
            ->where('status', 'pending')
            ->where('is_enabled', true)
            ->select('*');

    return DataTables::of($query)
            ->addColumn('transaction_number', fn($row) => $row->transaction_number)
            ->editColumn('created_at', fn($row) => $row->created_at)
            ->addColumn('from_account', function ($row) {
                $account = $row->fromAccount;
                $personName = $account?->person?->name;
                $accountName = $account?->name;
                $accountType = $account?->type === 'treasury' ? 'Tesorería' : 'Personal';
                $displayName = $personName ?: $accountName ?: ($accountType === 'Tesorería' ? 'Tesorería' : 'N/A');
                return '<div class="d-flex align-items-center">'
                    . '<i class="fas fa-university text-success me-2"></i>'
                    . '<div><strong>' . e($displayName) . '</strong><br>'
                    . '<small class="text-muted">' . e($accountType) . '</small></div>'
                    . '</div>';
            })
            ->addColumn('to_account', function ($row) {
                $account = $row->toAccount;
                $personName = $account?->person?->name;
                $accountName = $account?->name;
                $accountType = $account?->type === 'treasury' ? 'Tesorería' : 'Personal';
                $displayName = $personName ?: $accountName ?: ($accountType === 'Tesorería' ? 'Tesorería' : 'N/A');
                return '<div class="d-flex align-items-center">'
                    . '<i class="fas fa-user text-primary me-2"></i>'
                    . '<div><strong>' . e($displayName) . '</strong><br>'
                    . '<small class="text-muted">' . e($accountType) . '</small></div>'
                    . '</div>';
            })
            ->addColumn('amount', function ($row) {
                return (float) $row->amount;
            })
            ->addColumn('description', function ($row) {
                return $row->description ?? '';
            })
            ->addColumn('created_by', function ($row) {
                return $row->createdBy?->name ?: 'Sistema';
            })
            ->addColumn('action', function ($row) {
                return '<div class="btn-group" role="group">'
                    . '<button type="button" class="btn btn-success btn-sm" onclick="approveTransaction(' . $row->id . ')" title="Aprobar">'
                    . '<i class="fas fa-check"></i>'
                    . '</button>'
                    . '<button type="button" class="btn btn-danger btn-sm" onclick="rejectTransaction(' . $row->id . ')" title="Rechazar">'
                    . '<i class="fas fa-times"></i>'
                    . '</button>'
                    . '</div>';
            })
            ->rawColumns(['from_account', 'to_account', 'action'])
            // Map DataTables column names to real DB columns so server-side ordering works
            ->orderColumn('transaction_number', 'transactions.transaction_number $1')
            ->orderColumn('created_at', 'transactions.created_at $1')
            ->orderColumn('amount', 'transactions.amount $1')
            ->orderColumn('description', 'transactions.description $1')
            // created_by is a relation (user id stored on transactions); order by the id as fallback
            ->orderColumn('created_by', 'transactions.created_by $1')
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('transaction_number', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('fromAccount.person', function ($qp) use ($search) {
                                $qp->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('toAccount.person', function ($qp) use ($search) {
                                $qp->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('createdBy', function ($qc) use ($search) {
                                $qc->where('name', 'like', "%{$search}%");
                            });
                    });
                }
            })
            ->make(true);
    }

    public function expenses(Request $request)
    {
        $query = Expense::with(['account.person', 'submittedBy', 'items'])
            ->where('status', 'submitted')
            ->where('is_enabled', true)
            ->select('*');

    return DataTables::of($query)
            ->editColumn('expense_date', fn($row) => $row->expense_date)
            ->addColumn('person_name', function ($row) {
                $account = $row->account;
                $personName = $account?->person?->name;
                $accountName = $account?->name;
                $accountType = $account?->type === 'treasury' ? 'Tesorería' : 'Personal';
                return $personName ?: $accountName ?: ($accountType === 'Tesorería' ? 'Tesorería' : 'N/A');
            })
            ->addColumn('items_count', fn($row) => $row->items ? $row->items->count() : 0)
            ->editColumn('total_amount', fn($row) => (float) $row->total_amount)
            ->editColumn('status', fn($row) => $row->status)
            ->addColumn('action', function ($row) {
                return '<div class="btn-group" role="group">'
                    . '<button type="button" class="btn btn-info btn-sm" onclick="viewExpenseDetails(' . $row->id . ')" title="Ver Detalles">'
                    . '<i class="fas fa-eye"></i>'
                    . '</button>'
                    . '<button type="button" class="btn btn-success btn-sm" onclick="approveExpense(' . $row->id . ')" title="Aprobar">'
                    . '<i class="fas fa-check"></i>'
                    . '</button>'
                    . '<button type="button" class="btn btn-danger btn-sm" onclick="rejectExpense(' . $row->id . ')" title="Rechazar">'
                    . '<i class="fas fa-times"></i>'
                    . '</button>'
                    . '</div>';
            })
            ->rawColumns(['action'])
            // Map virtual column names to real DB fields for ordering
            ->orderColumn('expense_date', 'expenses.expense_date $1')
            ->orderColumn('description', 'expenses.description $1')
            ->orderColumn('total_amount', 'expenses.total_amount $1')
            ->orderColumn('status', 'expenses.status $1')
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $search = $request->search['value'];
                    $query->where(function ($q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                            ->orWhereHas('account.person', function ($qp) use ($search) {
                                $qp->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhere('status', 'like', "%{$search}%");
                    });
                }
            })
            ->make(true);
    }
}
