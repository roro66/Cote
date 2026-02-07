<?php

namespace App\Http\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserDataTableController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('roles', function (User $user) {
                return e($user->roles->pluck('name')->join(', '));
            })
            ->addColumn('actions', function (User $user) {
                $disabled = (strtolower($user->email) === 'admin@cote.com' || auth()->id() === $user->id) ? 'disabled' : '';
                $id = (int) $user->id;
                return '<div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary" onclick="openEditUserModal('.$id.')">Editar</button>
                            <button type="button" class="btn btn-danger" onclick="deleteUser('.$id.')" '.$disabled.'>Eliminar</button>
                        </div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
