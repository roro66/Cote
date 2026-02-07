<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Helpers\DatabaseHelper;
use App\Models\Person;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class PersonDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Seleccionar explícitamente las columnas de la tabla people para que
            // podamos aplicar joins en orderColumn sin ambigüedades
            $data = Person::with(['bank', 'accountType'])->select('people.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('full_name', function ($row) {
                    return $row->first_name . ' ' . $row->last_name;
                })
                ->editColumn('rut', function ($row) {
                    // Formatear RUT con puntos si no los tiene
                    if (strpos($row->rut, '.') === false && strlen($row->rut) >= 8) {
                        $rut = str_replace('-', '', $row->rut);
                        $dv = substr($rut, -1);
                        $number = substr($rut, 0, -1);
                        $formatted = number_format($number, 0, '', '.') . '-' . $dv;
                        return $formatted;
                    }
                    return $row->rut;
                })
                ->addColumn('bank_info', function ($row) {
                    if ($row->bank) {
                        $badge = match ($row->bank->type) {
                            'banco' => 'bg-primary',
                            'tarjeta_prepago' => 'bg-info',
                            'cooperativa' => 'bg-warning',
                            default => 'bg-secondary'
                        };
                        return '<span class="badge ' . $badge . '">' . $row->bank->name . '</span>';
                    }
                    return '<span class="text-muted">Sin banco</span>';
                })
                ->addColumn('account_info', function ($row) {
                    if ($row->accountType) {
                        return '<span class="badge bg-secondary">' . $row->accountType->name . '</span>';
                    }
                    return '<span class="text-muted">Sin tipo</span>';
                })
                ->addColumn('status', function ($row) {
                    $status = $row->is_enabled ? 'Activo' : 'Inactivo';
                    $class = $row->is_enabled ? 'bg-success' : 'bg-danger';
                    return '<span class="badge ' . $class . '">' . $status . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $html  = '<div class="btn-group btn-group-sm" role="group" style="white-space:nowrap">';
                    $html .=   '<button type="button" class="btn btn-secondary" onclick="viewPerson(' . $row->id . ')" title="Ver">'
                        .     '<i class="fas fa-eye"></i>'
                        .   '</button>';
                    $html .=   '<button type="button" class="btn btn-primary" onclick="editPerson(' . $row->id . ')" title="Editar">'
                        .     '<i class="fas fa-edit"></i>'
                        .   '</button>';
                    $html .=   '<button type="button" class="btn btn-danger" onclick="deletePerson(' . $row->id . ')" title="Eliminar">'
                        .     '<i class="fas fa-trash"></i>'
                        .   '</button>';
                    $html .= '</div>';
                    return $html;
                })
                // Soporte para ordenar por nombre completo (first_name, last_name)
                ->orderColumn('full_name', 'first_name $1, last_name $1')
                ->filterColumn('full_name', function ($query, $keyword) {
                    $like = DatabaseHelper::likeExpression("CONCAT(first_name,' ',last_name)");
                    $query->whereRaw($like, ["%{$keyword}%"]);
                })
                // Soporte para ordenar por el nombre del banco asociado
                ->orderColumn('bank_info', function ($query, $order) {
                    // Hacer left join con banks y ordenar por banks.name
                    $query->leftJoin('banks', 'people.bank_id', '=', 'banks.id')
                          ->orderBy('banks.name', $order);
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->search['value'])) {
                        $searchValue = $request->search['value'];
                        $op = DatabaseHelper::likeOperator();
                        $query->where(function ($q) use ($searchValue, $op) {
                            $q->where('first_name', $op, "%{$searchValue}%")
                                ->orWhere('last_name', $op, "%{$searchValue}%")
                                ->orWhere('rut', $op, "%{$searchValue}%")
                                ->orWhere('email', $op, "%{$searchValue}%")
                                ->orWhere('phone', $op, "%{$searchValue}%")
                                ->orWhereRaw(DatabaseHelper::likeExpression("CONCAT(first_name,' ',last_name)"), ["%{$searchValue}%"])
                                ->orWhereHas('bank', function ($bankQuery) use ($searchValue, $op) {
                                    $bankQuery->where('name', $op, "%{$searchValue}%");
                                })
                                ->orWhereHas('accountType', function ($typeQuery) use ($searchValue, $op) {
                                    $typeQuery->where('name', $op, "%{$searchValue}%");
                                });
                        });
                    }
                })
                ->rawColumns(['action', 'status', 'rut', 'bank_info', 'account_info'])
                ->make(true);
        }

        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
