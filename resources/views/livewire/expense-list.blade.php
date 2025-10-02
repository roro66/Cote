<div>
    <!-- Search and filters -->
    <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="text" 
               wire:model.live.debounce.300ms="search" 
               placeholder="Buscar rendiciones..."
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
        
        <select wire:model.live="teamFilter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Todos los equipos</option>
            @foreach($teams as $team)
                <option value="{{ $team->id }}">{{ $team->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="statusFilter" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Todos los estados</option>
            <option value="pending">Pendiente</option>
            <option value="approved">Aprobada</option>
            <option value="rejected">Rechazada</option>
        </select>

        <select wire:model.live="perPage" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            <option value="10">10 por página</option>
            <option value="25">25 por página</option>
            <option value="50">50 por página</option>
        </select>
    </div>

    <!-- Flash messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- Expenses table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th wire:click="sortBy('created_at')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Fecha
                        @if($sortField === 'created_at')
                            @if($sortDirection === 'asc')
                                ↑
                            @else
                                ↓
                            @endif
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Equipo
                    </th>
                    <th wire:click="sortBy('total_amount')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Monto Total
                        @if($sortField === 'total_amount')
                            @if($sortDirection === 'asc')
                                ↑
                            @else
                                ↓
                            @endif
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Descripción
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Items
                    </th>
                    <th wire:click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Estado
                        @if($sortField === 'status')
                            @if($sortDirection === 'asc')
                                ↑
                            @else
                                ↓
                            @endif
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $expense->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $expense->team ? $expense->team->name : 'Sin equipo' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $expense->currency }} {{ number_format($expense->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ Str::limit($expense->description, 40) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $expense->expenseItems->count() }} items
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($expense->status === 'pending')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pendiente
                                </span>
                            @elseif($expense->status === 'approved')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Aprobada
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Rechazada
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('expenses.edit', $expense->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                Editar
                            </a>
                            @if($expense->status === 'pending')
                                <button wire:click="approveExpense({{ $expense->id }})" 
                                       class="text-green-600 hover:text-green-900">
                                    Aprobar
                                </button>
                                <button wire:click="rejectExpense({{ $expense->id }})" 
                                        onclick="return confirm('¿Está seguro de rechazar esta rendición?')"
                                       class="text-red-600 hover:text-red-900">
                                    Rechazar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No se encontraron rendiciones.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</div>
