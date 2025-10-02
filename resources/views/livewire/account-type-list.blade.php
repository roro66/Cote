<div>
    <!-- Filtros y controles -->
    <div class="mb-6 flex justify-between items-center">
        <div class="flex-1 max-w-md">
            <input type="text" wire:model.live="search" placeholder="Buscar tipos de cuenta..."
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="ml-4">
            <button type="button" onclick="openModal('accountTypeModal')" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Nuevo Tipo de Cuenta
            </button>
        </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" 
                        wire:click="sortBy('name')">
                        Nombre
                        @if($sortField === 'name')
                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Personas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($accountTypes as $accountType)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $accountType->name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="max-w-xs truncate" title="{{ $accountType->description }}">
                            {{ $accountType->description }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            {{ $accountType->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $accountType->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $accountType->people()->count() }} personas
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button type="button" wire:click="edit({{ $accountType->id }})" onclick="openModal('accountTypeModal')"
                           class="text-indigo-600 hover:text-indigo-900 mr-3">
                            Editar
                        </button>
                        <button type="button" wire:click="delete({{ $accountType->id }})" 
                            onclick="confirm('¿Está seguro de eliminar este tipo de cuenta?') || event.stopImmediatePropagation()"
                           class="text-red-600 hover:text-red-900">
                            Eliminar
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $accountTypes->links() }}
    </div>

    <!-- Modal para crear/editar tipo de cuenta -->
    <div id="accountTypeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    {{ $accountTypeId ? 'Editar Tipo de Cuenta' : 'Nuevo Tipo de Cuenta' }}
                </h3>
                
                <form wire:submit.prevent="save">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                        <input type="text" wire:model="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                        <textarea wire:model="description" rows="3" maxlength="500"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Describe las características de este tipo de cuenta..."></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="is_active" class="rounded">
                            <span class="ml-2 text-sm text-gray-700">Activo</span>
                        </label>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('accountTypeModal')" wire:click="resetForm"
                           class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancelar
                        </button>
                        <button type="submit"
                           class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            {{ $accountTypeId ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</div>
