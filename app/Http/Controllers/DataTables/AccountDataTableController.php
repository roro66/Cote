<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class AccountDataTableController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Seleccionar explícitamente para evitar ambigüedades al hacer joins
            $data = Account::with(['person'])->select('accounts.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('type_spanish', function ($row) {
                    return $row->type_spanish;
                })
                ->addColumn('owner', function ($row) {
                    if ($row->person) {
                        return $row->person->full_name . ' (' . $row->person->rut_formatted . ')';
                    }
                    return $row->type === 'treasury' ? 'Tesorería General' : 'Sin propietario';
                })
                ->addColumn('balance_formatted', function ($row) {
                    return $row->balance_formatted;
                })
                ->addColumn('action', function ($row) {
                    $html  = '<div class="btn-group btn-group-sm" role="group" style="white-space:nowrap">';
                    $html .=   '<button type="button" class="btn btn-secondary" onclick="viewAccount(' . $row->id . ')" title="Ver">'
                        .     '<i class="fas fa-eye"></i>'
                        .   '</button>';
                    $html .=   '<button type="button" class="btn btn-primary" onclick="editAccount(' . $row->id . ')" title="Editar">'
                        .     '<i class="fas fa-edit"></i>'
                        .   '</button>';
                    $html .=   '<button type="button" class="btn btn-info" onclick="viewTransactions(' . $row->id . ')" title="Movimientos">'
                        .     '<i class="fas fa-list"></i>'
                        .   '</button>';
                    $html .=   '<button type="button" class="btn btn-danger" onclick="deleteAccount(' . $row->id . ')" title="Eliminar">'
                        .     '<i class="fas fa-trash"></i>'
                        .   '</button>';
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('status', function ($row) {
                    $status = $row->is_enabled ? 'Activa' : 'Inactiva';
                    $class = $row->is_enabled ? 'bg-success' : 'bg-danger';
                    return '<span class="badge ' . $class . '">' . $status . '</span>';
                })
                // Soporte para ordenar por tipo (accounts.type)
                ->orderColumn('type_spanish', 'accounts.type $1')
                // Soporte para ordenar por propietario (person's first_name, last_name)
                ->orderColumn('owner', function ($query, $order) {
                    $query->leftJoin('people', 'accounts.person_id', '=', 'people.id')
                          ->orderBy('people.first_name', $order)
                          ->orderBy('people.last_name', $order);
                })
                // Soporte para ordenar por saldo (accounts.balance)
                ->orderColumn('balance_formatted', 'accounts.balance $1')
                // Soporte para ordenar por estado (is_enabled)
                ->orderColumn('status', 'accounts.is_enabled $1')
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}
