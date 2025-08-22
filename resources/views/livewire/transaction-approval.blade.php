<div>
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Aprobación de Transacciones</h3>
            </div>

            <!-- Filtros -->
            <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="text" wire:model.live="search" placeholder="Buscar transacciones..." 
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <select wire:model.live="statusFilter" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all">Todos los estados</option>
                        <option value="pending">Pendientes</option>
                        <option value="approved">Aprobadas</option>
                        <option value="rejected">Rechazadas</option>
                        <option value="completed">Completadas</option>
                    </select>
                </div>
            </div>

            <!-- Tabla de transacciones -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Número
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Desde / Hacia
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Monto
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $transaction->transaction_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        @switch($transaction->type)
                                            @case('transfer') Transferencia @break
                                            @case('payment') Pago @break
                                            @case('adjustment') Ajuste @break
                                            @default {{ ucfirst($transaction->type) }}
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>
                                        <div class="text-xs text-gray-500">Desde:</div>
                                        <div>{{ $transaction->fromAccount->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500 mt-1">Hacia:</div>
                                        <div>{{ $transaction->toAccount->name ?? 'N/A' }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ${{ number_format($transaction->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($transaction->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($transaction->status === 'approved') bg-green-100 text-green-800
                                        @elseif($transaction->status === 'rejected') bg-red-100 text-red-800
                                        @else bg-blue-100 text-blue-800 @endif">
                                        @switch($transaction->status)
                                            @case('pending') Pendiente @break
                                            @case('approved') Aprobada @break
                                            @case('rejected') Rechazada @break
                                            @case('completed') Completada @break
                                            @default {{ ucfirst($transaction->status) }}
                                        @endswitch
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button wire:click="showDetails({{ $transaction->id }})" 
                                            class="text-indigo-600 hover:text-indigo-900 mr-2">
                                        Ver Detalles
                                    </button>
                                    @if($transaction->status === 'pending')
                                        <button wire:click="approve({{ $transaction->id }})" 
                                                class="text-green-600 hover:text-green-900 mr-2">
                                            Aprobar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    No se encontraron transacciones.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

    <!-- Modal de detalles -->
    @if($showingDetails && $selectedTransaction)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" id="modal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Detalles de Transacción: {{ $selectedTransaction->transaction_number }}
                        </h3>
                        <button wire:click="hideDetails" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipo</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @switch($selectedTransaction->type)
                                    @case('transfer') Transferencia @break
                                    @case('payment') Pago @break
                                    @case('adjustment') Ajuste @break
                                    @default {{ ucfirst($selectedTransaction->type) }}
                                @endswitch
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Monto</label>
                            <p class="mt-1 text-sm text-gray-900 font-medium">
                                ${{ number_format($selectedTransaction->amount, 0, ',', '.') }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cuenta Origen</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->fromAccount->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cuenta Destino</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->toAccount->name ?? 'N/A' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Descripción</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->description }}</p>
                        </div>
                        @if($selectedTransaction->notes)
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Notas</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->notes }}</p>
                            </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Creado por</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->creator->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha de creación</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($selectedTransaction->status === 'pending')
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1 mr-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de rechazo (opcional)</label>
                                    <textarea wire:model="rejectionReason" rows="3" 
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                              placeholder="Escriba el motivo del rechazo..."></textarea>
                                    @error('rejectionReason') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div class="flex flex-col space-y-2">
                                    <button wire:click="approve({{ $selectedTransaction->id }})" 
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        Aprobar
                                    </button>
                                    <button wire:click="reject({{ $selectedTransaction->id }})" 
                                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
