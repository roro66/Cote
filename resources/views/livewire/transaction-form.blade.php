<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Transaction Number -->
        <div>
            <label for="transaction_number" class="block text-sm font-medium text-gray-700">
                Número de Transacción
            </label>
            <input type="text" 
                   wire:model="transaction_number" 
                   id="transaction_number"
                   readonly
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <!-- Transaction Type -->
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700">
                Tipo de Transacción
            </label>
            <select wire:model="type" 
                    id="type"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="transfer">Transferencia</option>
                <option value="payment">Pago</option>
                <option value="adjustment">Ajuste</option>
            </select>
            @error('type') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- From Account -->
        <div>
            <label for="from_account_id" class="block text-sm font-medium text-gray-700">
                Cuenta de Origen
            </label>
            <select wire:model="from_account_id" 
                    id="from_account_id"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Seleccionar cuenta de origen</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">
                        {{ $account->name }}
                        @if($account->person)
                            ({{ $account->person->full_name }})
                        @endif
                        - {{ $account->balance_formatted }}
                    </option>
                @endforeach
            </select>
            @error('from_account_id') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- To Account -->
        <div>
            <label for="to_account_id" class="block text-sm font-medium text-gray-700">
                Cuenta de Destino
            </label>
            <select wire:model="to_account_id" 
                    id="to_account_id"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Seleccionar cuenta de destino</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">
                        {{ $account->name }}
                        @if($account->person)
                            ({{ $account->person->full_name }})
                        @endif
                        - {{ $account->balance_formatted }}
                    </option>
                @endforeach
            </select>
            @error('to_account_id') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Amount -->
        <div>
            <label for="amount" class="block text-sm font-medium text-gray-700">
                Monto (CLP)
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input type="number" 
                       wire:model="amount" 
                       id="amount"
                       step="1"
                       min="1"
                       placeholder="0"
                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm" id="currency">CLP</span>
                </div>
            </div>
            @error('amount') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">
                Descripción
            </label>
            <textarea wire:model="description" 
                      id="description"
                      rows="3"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            @error('description') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">
                Notas Adicionales (Opcional)
            </label>
            <textarea wire:model="notes" 
                      id="notes"
                      rows="2"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            @error('notes') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Status (for editing) -->
        @if($transactionId)
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">
                Estado
            </label>
            <select wire:model="status" 
                    id="status"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="pending">Pendiente</option>
                <option value="approved">Aprobada</option>
                <option value="rejected">Rechazada</option>
                <option value="completed">Completada</option>
            </select>
            @error('status') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>
        @endif

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                @if($transactionId)
                    Actualizar Transacción
                @else
                    Crear Transacción
                @endif
            </button>
        </div>
    </form>
</div>
