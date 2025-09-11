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

    <!-- Resumen de Cuentas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Cuentas</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $totalAccounts }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Saldo Total</dt>
                            <dd class="text-lg font-medium text-gray-900">${{ number_format($totalBalance, 0, ',', '.') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Transacciones Pendientes</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $pendingTransactions }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Rendiciones Pendientes</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $pendingExpenses }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen por tipo de cuenta -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Cuentas por Tipo</h3>
                <div class="space-y-3">
                    @foreach($accountsByType as $type => $data)
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm font-medium text-gray-900">
                                    @switch($type)
                                        @case('tesoreria') Tesorería @break
                                        @case('treasury') Tesorería @break
                                        @case('cuadrilla') Cuadrillas @break
                                        @case('personal') Personal @break
                                        @case('person') Personal @break
                                        @default {{ ucfirst($type) }}
                                    @endswitch
                                </span>
                                <p class="text-xs text-gray-500">{{ $data->count }} cuenta(s)</p>
                            </div>
                            <span class="text-sm font-medium text-gray-900">
                                ${{ number_format($data->total_balance, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Transacciones Recientes -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Transacciones Recientes</h3>
                <div class="space-y-3">
                    @forelse($recentTransactions as $transaction)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $transaction->transaction_number }}</p>
                                <p class="text-xs text-gray-500">{{ $transaction->description }}</p>
                                <p class="text-xs text-gray-400">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">${{ number_format($transaction->amount, 0, ',', '.') }}</p>
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
                                @if($transaction->status === 'pending')
                                    <div class="mt-1 flex space-x-1">
                                        <button wire:click="approveTransaction({{ $transaction->id }})" 
                                                class="text-xs bg-green-500 hover:bg-green-600 text-white px-2 py-1 rounded">
                                            Aprobar
                                        </button>
                                        <button wire:click="rejectTransaction({{ $transaction->id }})" 
                                                class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">
                                            Rechazar
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No hay transacciones recientes.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Rendiciones Recientes -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Rendiciones Recientes</h3>
            <div class="space-y-3">
                @forelse($recentExpenses as $expense)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $expense->expense_number }}</p>
                            <p class="text-xs text-gray-500">{{ $expense->description }}</p>
                            <p class="text-xs text-gray-400">{{ $expense->expense_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">${{ number_format($expense->total_amount, 0, ',', '.') }}</p>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($expense->status === 'draft') bg-gray-100 text-gray-800
                                @elseif($expense->status === 'submitted') bg-yellow-100 text-yellow-800
                                @elseif($expense->status === 'reviewed') bg-blue-100 text-blue-800
                                @elseif($expense->status === 'approved') bg-green-100 text-green-800
                                @elseif($expense->status === 'rejected') bg-red-100 text-red-800
                                @endif">
                                @switch($expense->status)
                                    @case('draft') Borrador @break
                                    @case('submitted') Enviada @break
                                    @case('reviewed') En Revisión @break
                                    @case('approved') Aprobada @break
                                    @case('rejected') Rechazada @break
                                    @default {{ ucfirst($expense->status) }}
                                @endswitch
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No hay rendiciones recientes.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
