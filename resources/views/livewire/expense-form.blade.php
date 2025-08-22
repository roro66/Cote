<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Team -->
        <div>
            <label for="team_id" class="block text-sm font-medium text-gray-700">
                Equipo/Cuadrilla
            </label>
            <select wire:model="team_id" 
                    id="team_id"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Seleccionar equipo</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
            @error('team_id') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">
                Descripción General
            </label>
            <textarea wire:model="description" 
                      id="description"
                      rows="3"
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            @error('description') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Reference -->
        <div>
            <label for="reference" class="block text-sm font-medium text-gray-700">
                Referencia (Opcional)
            </label>
            <input type="text" 
                   wire:model="reference" 
                   id="reference"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            @error('reference') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Currency -->
        <div>
            <label for="currency" class="block text-sm font-medium text-gray-700">
                Moneda
            </label>
            <select wire:model="currency" 
                    id="currency"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="PEN">Soles (PEN)</option>
                <option value="USD">Dólares (USD)</option>
                <option value="EUR">Euros (EUR)</option>
            </select>
            @error('currency') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Status (for editing) -->
        @if($expenseId)
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
            </select>
            @error('status') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>
        @endif

        <!-- Expense Items Section -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Items de Rendición</h3>
            
            <!-- Add New Item Form -->
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Agregar Nuevo Item</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <input type="text" 
                               wire:model="newItem.description" 
                               placeholder="Descripción del item"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('newItem.description') 
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>
                    <div>
                        <input type="number" 
                               wire:model="newItem.amount" 
                               placeholder="Monto"
                               step="0.01"
                               min="0.01"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        @error('newItem.amount') 
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p> 
                        @enderror
                    </div>
                    <div>
                        <select wire:model="newItem.currency" 
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="PEN">PEN</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="flex space-x-2">
                        <input type="text" 
                               wire:model="newItem.receipt_number" 
                               placeholder="Nº Recibo"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="button" 
                                wire:click="addItem"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                            Agregar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Items List -->
            @if(!empty($items))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descripción
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Monto
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Moneda
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nº Recibo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($items as $index => $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $item['description'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($item['amount'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $item['currency'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $item['receipt_number'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button type="button" 
                                        wire:click="removeItem({{ $index }})"
                                        class="text-red-600 hover:text-red-900">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <!-- Total -->
                <div class="mt-4 text-right">
                    <span class="text-lg font-semibold text-gray-900">
                        Total: {{ $currency }} {{ number_format(collect($items)->sum('amount'), 2) }}
                    </span>
                </div>
            </div>
            @else
            <p class="text-gray-500 text-center py-4">No hay items agregados aún.</p>
            @endif

            @error('items') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end pt-6 border-t">
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                @if($expenseId)
                    Actualizar Rendición
                @else
                    Crear Rendición
                @endif
            </button>
        </div>
    </form>
</div>
