<div>
    <!-- Search and filters -->
    <div class="mb-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <input type="text" 
                   wire:model.live.debounce.300ms="search" 
                   placeholder="Buscar cuentas..."
                  class="block w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            
            <select wire:model.live="perPage" class="block px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </select>
        </div>
    </div>

    <!-- Flash messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- Accounts table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Nombre
                        @if($sortField === 'name')
                            @if($sortDirection === 'asc')
                                ↑
                            @else
                                ↓
                            @endif
                        @endif
                    </th>
                    <th wire:click="sortBy('type')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Tipo
                        @if($sortField === 'type')
                            @if($sortDirection === 'asc')
                                ↑
                            @else
                                ↓
                            @endif
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Responsable/Equipo
                    </th>
                    <th wire:click="sortBy('balance')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Saldo
                        @if($sortField === 'balance')
                            @if($sortDirection === 'asc')
                                ↑
                            @else
                                ↓
                            @endif
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $account->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($account->type === 'treasury')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                    Tesorería
                                </span>
                            @elseif($account->type === 'team')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Equipo
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Personal
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($account->person)
                                {{ $account->person->first_name }} {{ $account->person->last_name }}
                            @elseif($account->team)
                                {{ $account->team->name }}
                            @else
                                Tesorería General
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            S/ {{ number_format($account->balance, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($account->is_enabled)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Activa
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactiva
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('accounts.edit', $account->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                Editar
                            </a>
                            @if($account->is_enabled)
                                <button wire:click="deleteAccount({{ $account->id }})" 
                                        onclick="return confirm('¿Está seguro de deshabilitar esta cuenta?')"
                                       class="text-red-600 hover:text-red-900">
                                    Deshabilitar
                                </button>
                            @else
                                <button wire:click="enableAccount({{ $account->id }})" 
                                       class="text-green-600 hover:text-green-900">
                                    Habilitar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No se encontraron cuentas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $accounts->links() }}
    </div>
</div>
