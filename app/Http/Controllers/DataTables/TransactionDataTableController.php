<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Helpers\DatabaseHelper;
use App\Models\Transaction;
use App\Models\Expense;
use App\Models\Account;
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

            // Filtrar por cuenta si se especifica (movimientos de una cuenta)
            if ($request->filled('account_id')) {
                $accountId = (int) $request->get('account_id');
                $account = Account::find($accountId);
                $startingBalance = (float) ($account?->balance ?? 0);

                // 1) Obtener transacciones de la cuenta
                $tx = (clone $data)
                    ->where(function ($q) use ($accountId) {
                        $q->where('from_account_id', $accountId)
                            ->orWhere('to_account_id', $accountId);
                    })
                    ->get();

                // Mapear transacciones a filas unificadas
                $txRows = $tx->map(function ($row) use ($accountId) {
                    $statusText = match ($row->status) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'completed' => 'Completada',
                        default => ucfirst($row->status)
                    };
                    $class = match ($row->status) {
                        'pending' => 'bg-warning text-dark',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'completed' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    $action = '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-info btn-sm" title="Ver" aria-label="Ver" onclick="viewTransaction(' . $row->id . ')">
                            <i class="fas fa-eye"></i>
                        </button>';
                    if ($row->status === 'pending') {
                        $action .= '<button type="button" class="btn btn-success btn-sm" title="Aprobar" aria-label="Aprobar" onclick="approveTransaction(' . $row->id . ')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" title="Rechazar" aria-label="Rechazar" onclick="rejectTransaction(' . $row->id . ')">
                            <i class="fas fa-times"></i>
                        </button>';
                    }
                    $action .= '</div>';

                    // Delta para esta cuenta (ingreso/egreso)
                    $delta = 0.0;
                    if ((int) $row->to_account_id === $accountId) {
                        $delta = (float) $row->amount; // ingreso
                    } elseif ((int) $row->from_account_id === $accountId) {
                        $delta = -(float) $row->amount; // egreso
                    }

                    $typeText = match ($row->type) {
                        'transfer' => 'Transferencia',
                        default => ucfirst($row->type)
                    };
                    $typeBadge = '<span class="badge bg-primary">' . e($typeText) . '</span>';

                    return [
                        'id' => 'T' . $row->id,
                        'transaction_number' => $row->transaction_number,
                        'type_spanish' => $typeText,
                        'movement_type_badge' => $typeBadge,
                        'from_account_name' => $row->fromAccount?->name ?: 'N/A',
                        'to_account_name' => $row->toAccount?->name ?: 'N/A',
                        'amount' => (float) $row->amount,
                        'amount_formatted' => '$' . number_format((float) $row->amount, 0, ',', '.'),
                        'status_spanish' => '<span class="badge ' . $class . '">' . $statusText . '</span>',
                        'creator_name' => $row->creator?->name ?: 'N/A',
                        'created_at_formatted' => $row->created_at->format('d/m/Y H:i'),
                        'action' => $action,
                        'delta' => $delta,
                        'sort_ts' => $row->created_at->timestamp,
                    ];
                });

                // 2) Obtener rendiciones aprobadas de la cuenta
                $exp = Expense::with(['account', 'submittedBy'])
                    ->where('account_id', $accountId)
                    ->where('status', 'approved')
                    ->get();

                $expRows = $exp->map(function ($row) {
                    $statusText = 'Aprobada';
                    $class = 'bg-success';
                    $action = '<div class="btn-group" role="group">
                        <a href="' . route('expenses.show', $row->id) . '" class="btn btn-info btn-sm" title="Ver rendición" aria-label="Ver rendición">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>';

                    // Usamos reviewed_at como fecha del movimiento (cuando afectó saldo)
                    $dt = $row->reviewed_at ?: $row->submitted_at ?: $row->created_at;

                    $typeText = 'Rendición';
                    $typeBadge = '<span class="badge bg-warning text-dark">' . e($typeText) . '</span>';

                    return [
                        'id' => 'E' . $row->id,
                        'transaction_number' => $row->expense_number,
                        'type_spanish' => $typeText,
                        'movement_type_badge' => $typeBadge,
                        'from_account_name' => $row->account?->name ?: 'N/A',
                        'to_account_name' => 'Gasto',
                        'amount' => (float) $row->total_amount,
                        'amount_formatted' => '$' . number_format((float) $row->total_amount, 0, ',', '.'),
                        'status_spanish' => '<span class="badge ' . $class . '">' . $statusText . '</span>',
                        'creator_name' => $row->submittedBy?->full_name ?? $row->submittedBy?->name ?? 'N/A',
                        'created_at_formatted' => $dt?->format('d/m/Y H:i'),
                        'action' => $action,
                        'delta' => -(float) $row->total_amount,
                        'sort_ts' => $dt?->timestamp ?? 0,
                    ];
                });

                // 3) Unir y ordenar por fecha desc
                $rows = $txRows->concat($expRows)->sortByDesc('sort_ts')->values();

                // Calcular saldo acumulado descendente, partiendo del saldo actual de la cuenta
                $running = $startingBalance;
                $rows = $rows->map(function ($r) use (&$running) {
                    $r['running_balance_formatted'] = '$' . number_format((float) $running, 0, ',', '.');
                    $running -= (float) ($r['delta'] ?? 0);
                    return $r;
                });

                return DataTables::of($rows)
                    ->addIndexColumn()
                    // Nota: CollectionDataTable no soporta orderColumn; se usa el orden por defecto (sort_ts desc)
                    ->rawColumns(['action', 'status_spanish', 'movement_type_badge'])
                    ->make(true);
            }

            // Sin filtro por cuenta: comportamiento original
            // Unir cuentas para permitir ordenar por nombre de cuenta destino/origen
            $data->leftJoin('accounts as to_acc', 'to_acc.id', '=', 'transactions.to_account_id')
                ->leftJoin('accounts as from_acc', 'from_acc.id', '=', 'transactions.from_account_id')
                ->addSelect([
                    'to_acc.name as to_acc_name',
                    'from_acc.name as from_acc_name',
                ]);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('type_spanish', function ($row) {
                    return match ($row->type) {
                        'transfer' => 'Transferencia',
                        default => ucfirst($row->type)
                    };
                })
                ->addColumn('movement_type_badge', function ($row) {
                    $text = match ($row->type) {
                        'transfer' => 'Transferencia',
                        default => ucfirst($row->type)
                    };
                    return '<span class="badge bg-primary">' . e($text) . '</span>';
                })
                ->addColumn('from_account_name', function ($row) {
                    // Preferir alias cuando exista por join (mejor para ordenar)
                    return $row->from_acc_name ?? ($row->fromAccount ? $row->fromAccount->name : 'N/A');
                })
                ->addColumn('to_account_name', function ($row) {
                    return $row->to_acc_name ?? ($row->toAccount ? $row->toAccount->name : 'N/A');
                })
                ->addColumn('amount_formatted', function ($row) {
                    return '$' . number_format($row->amount, 0, ',', '.');
                })
                ->addColumn('running_balance_formatted', function ($row) {
                    return '';
                })
                ->addColumn('creator_name', function ($row) {
                    return $row->creator ? $row->creator->name : 'N/A';
                })
                ->addColumn('status_spanish', function ($row) {
                    $statusText = match ($row->status) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'completed' => 'Completada',
                        default => ucfirst($row->status)
                    };
                    $class = match ($row->status) {
                        'pending' => 'bg-warning text-dark',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'completed' => 'bg-info',
                        default => 'bg-secondary'
                    };
                    return '<span class="badge ' . $class . '">' . $statusText . '</span>';
                })
                ->addColumn('created_at_formatted', function ($row) {
                    return $row->created_at->format('d/m/Y H:i');
                })
                // Mapear orden para columnas calculadas
                ->orderColumn('to_account_name', function ($query, $order) {
                    $query->orderBy('to_acc_name', $order);
                })
                ->orderColumn('from_account_name', function ($query, $order) {
                    $query->orderBy('from_acc_name', $order);
                })
                ->orderColumn('amount_formatted', 'amount $1')
                ->orderColumn('created_at_formatted', 'transactions.created_at $1')
                // Case-insensitive global search
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $s = $request->search['value'];
                        $query->where(function ($q) use ($s) {
                            $q->whereRaw(DatabaseHelper::likeExpression('transactions.transaction_number'), ["%{$s}%"])
                              ->orWhereRaw(DatabaseHelper::likeExpression('transactions.type'), ["%{$s}%"])
                              ->orWhereRaw(DatabaseHelper::likeExpression('to_acc.name'), ["%{$s}%"])
                              ->orWhereRaw(DatabaseHelper::likeExpression('from_acc.name'), ["%{$s}%"])
                              ->orWhereHas('creator', function ($cq) use ($s) {
                                  $cq->whereRaw(DatabaseHelper::likeExpression('name'), ["%{$s}%"]);
                              });
                        });
                    }
                })
                ->addColumn('action', function ($row) {
                    $actionBtn = '<div class="btn-group" role="group">
                        <button type="button" class="btn btn-info btn-sm" title="Ver" aria-label="Ver" onclick="viewTransaction(' . $row->id . ')">
                            <i class="fas fa-eye"></i>
                        </button>';

                    if ($row->status === 'pending') {
                        $actionBtn .= '<button type="button" class="btn btn-success btn-sm" title="Aprobar" aria-label="Aprobar" onclick="approveTransaction(' . $row->id . ')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" title="Rechazar" aria-label="Rechazar" onclick="rejectTransaction(' . $row->id . ')">
                            <i class="fas fa-times"></i>
                        </button>';
                    }

                    $actionBtn .= '</div>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'status_spanish', 'movement_type_badge'])
                ->make(true);
        }

        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
