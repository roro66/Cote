<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'role:boss']);
    }

    public function index()
    {
        // La vista usa DataTables para cargar usuarios; solo necesita los roles para los modales
        $roles = Role::orderBy('name')->get();
        return view('users.index', compact('roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_enabled' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_enabled' => $user->is_enabled,
                    'roles' => $user->roles->pluck('name')->values(),
                ],
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_enabled' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        // Evitar cambiar el email del administrador a otro email? Permitimos editar, pero protegemos borrado abajo.

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_enabled = $request->boolean('is_enabled');
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $user->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles'] ?? []);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_enabled' => $user->is_enabled,
                    'roles' => $user->roles->pluck('name')->values(),
                ],
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        // Nadie puede borrar al usuario administrador (admin@coteso.com)
        if (strtolower($user->email) === 'admin@coteso.com') {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'No se puede eliminar al usuario Administrador.'], 400);
            }
            return redirect()->route('users.index')->with('error', 'No se puede eliminar al usuario Administrador.');
        }

        // Evitar que un usuario se elimine a sí mismo por accidente (opcional)
        if (auth()->id() === $user->id) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'No puedes eliminar tu propio usuario.'], 400);
            }
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        }

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente');
    }

    /**
     * Show the specified resource (JSON for AJAX modals)
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_enabled' => (bool) $user->is_enabled,
            'roles' => $user->roles->pluck('name')->values(),
        ]);
    }
}
