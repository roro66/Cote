<x-app-layout>
    <style>
        .users-page-wrapper { margin-top: 16px; }
        @media (max-width: 640px) { .users-page-wrapper { margin-top: 20px; } }
    </style>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Usuario</h2>
        </div>
    </x-slot>

    <div class="py-8 users-page-wrapper">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @if(strtolower($user->email) === 'admin@cote.com')
                    <div class="p-3 rounded bg-blue-50 text-blue-800 mb-2">Este usuario es el Administrador y no puede ser eliminado.</div>
                @endif
                <form id="userEditForm" method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium">Nombre</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full border rounded px-3 py-2" required>
                        @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full border rounded px-3 py-2" required>
                        @error('email') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nueva contraseña (opcional)</label>
                        <input type="password" name="password" class="mt-1 w-full border rounded px-3 py-2">
                        @error('password') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" class="mt-1 w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Activo</label>
                        <input type="checkbox" name="is_enabled" value="1" class="mt-1" @checked(old('is_enabled', $user->is_enabled))> Sí
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Roles</label>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            @foreach($roles as $role)
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked($user->hasRole($role->name))> {{ $role->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded">Cancelar</a>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 focus:bg-red-700 active:bg-red-800">Grabar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
