<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detalle de Transacción #{{ $transaction->transaction_number }}
            </h2>
            <div class="flex gap-2">
                @if ($transaction->status === 'pending')
                    <a href="{{ route('transactions.edit', $transaction) }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                @endif
                <a href="{{ route('transactions.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Estado de la Transacción -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Información General</h3>
                                <p class="text-sm text-gray-600">Detalles completos de la transacción</p>
                            </div>
                            <div>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'approved' => 'bg-green-100 text-green-800 border-green-200',
                                        'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                        'completed' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    ];
                                    $statusText = [
                                        'pending' => 'Pendiente',
                                        'approved' => 'Aprobada',
                                        'rejected' => 'Rechazada',
                                        'completed' => 'Completada',
                                    ];
                                @endphp
                                <span
                                    class="px-3 py-1 rounded-full text-sm font-medium border {{ $statusColors[$transaction->status] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                    {{ $statusText[$transaction->status] ?? 'Desconocido' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <!-- Información Básica -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Información Básica</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Número de Transacción</label>
                                    <p class="text-gray-900 font-mono">{{ $transaction->transaction_number }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Tipo</label>
                                    <p class="text-gray-900">
                                        @switch($transaction->type)
                                            @case('transfer')
                                                <i class="fas fa-exchange-alt text-blue-500"></i> Transferencia
                                            @break

                                            @default
                                                {{ $transaction->type }}
                                        @endswitch
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Monto</label>
                                    <p class="text-gray-900 text-2xl font-bold">
                                        ${{ number_format($transaction->amount, 0, ',', '.') }} CLP
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Fecha de Creación</label>
                                    <p class="text-gray-900">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Cuentas -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Cuentas Involucradas</h4>
                            <div class="space-y-3">
                                @if ($transaction->fromAccount)
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Cuenta Origen</label>
                                        <div class="flex items-center mt-1">
                                            <i class="fas fa-arrow-right text-red-500 mr-2"></i>
                                            <div>
                                                <p class="text-gray-900 font-medium">
                                                    {{ $transaction->fromAccount->name }}</p>
                                                <p class="text-sm text-gray-600">
                                                    {{ $transaction->fromAccount->type_spanish }}</p>
                                                @if ($transaction->fromAccount->person)
                                                    <p class="text-xs text-gray-500">
                                                        {{ $transaction->fromAccount->person->first_name }}
                                                        {{ $transaction->fromAccount->person->last_name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($transaction->toAccount)
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Cuenta Destino</label>
                                        <div class="flex items-center mt-1">
                                            <i class="fas fa-arrow-left text-green-500 mr-2"></i>
                                            <div>
                                                <p class="text-gray-900 font-medium">
                                                    {{ $transaction->toAccount->name }}</p>
                                                <p class="text-sm text-gray-600">
                                                    {{ $transaction->toAccount->type_spanish }}</p>
                                                @if ($transaction->toAccount->person)
                                                    <p class="text-xs text-gray-500">
                                                        {{ $transaction->toAccount->person->first_name }}
                                                        {{ $transaction->toAccount->person->last_name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Descripción y Notas -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Descripción</h4>
                            <p class="text-gray-700">{{ $transaction->description ?: 'Sin descripción' }}</p>

                            @if ($transaction->notes)
                                <h4 class="font-medium text-gray-900 mb-2 mt-4">Notas</h4>
                                <p class="text-gray-700">{{ $transaction->notes }}</p>
                            @endif
                        </div>

                        <!-- Información de Aprobación -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Estado y Aprobaciones</h4>
                            <div class="space-y-3">
                                @if ($transaction->createdBy)
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">Creado por</label>
                                        <p class="text-gray-900">{{ $transaction->createdBy->name }}</p>
                                    </div>
                                @endif

                                @if ($transaction->approved_at && $transaction->approvedBy)
                                    <div>
                                        <label class="text-sm font-medium text-gray-600">
                                            {{ $transaction->status === 'approved' ? 'Aprobado por' : 'Procesado por' }}
                                        </label>
                                        <p class="text-gray-900">{{ $transaction->approvedBy->name }}</p>
                                        <p class="text-sm text-gray-600">
                                            {{ $transaction->approved_at->format('d/m/Y H:i:s') }}</p>
                                    </div>
                                @endif

                                @if ($transaction->status === 'pending')
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                                        <div class="flex">
                                            <i class="fas fa-clock text-yellow-400 mr-2 mt-0.5"></i>
                                            <div>
                                                <h5 class="text-sm font-medium text-yellow-800">Transacción Pendiente
                                                </h5>
                                                <p class="text-sm text-yellow-700">Esta transacción requiere aprobación
                                                    para que se actualicen los saldos de las cuentas.</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
