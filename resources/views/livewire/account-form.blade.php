<div>
    <form wire:submit="save" class="space-y-6">
        <!-- Account Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
                Nombre de la Cuenta
            </label>
            <input type="text" 
                   wire:model="name" 
                   id="name"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            @error('name') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Account Type -->
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700">
                Tipo de Cuenta
            </label>
            <select wire:model.live="type" 
                    id="type"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="treasury">Tesorería</option>
                <option value="person">Personal</option>
            </select>
            @error('type') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Person (only for personal accounts) -->
        @if($type === 'person')
        <div>
            <label for="person_id" class="block text-sm font-medium text-gray-700">
                Persona Responsable
            </label>
            <select wire:model="person_id" 
                    id="person_id"
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Seleccionar persona</option>
                @foreach($people as $person)
                    <option value="{{ $person->id }}">{{ $person->first_name }} {{ $person->last_name }}</option>
                @endforeach
            </select>
            @error('person_id') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>
        @endif

        <!-- Balance -->
        <div>
            <label for="balance" class="block text-sm font-medium text-gray-700">
                Saldo Inicial (CLP)
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input type="number" 
                       wire:model="balance" 
                       id="balance"
                       step="1"
                       min="0"
                       placeholder="0"
                      class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">CLP</span>
                </div>
            </div>
            @error('balance') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">
                Notas (Opcional)
            </label>
            <textarea wire:model="notes" 
                      id="notes"
                      rows="3"
                     class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            @error('notes') 
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p> 
            @enderror
        </div>

        <!-- Status -->
        <div>
            <label class="flex items-center">
                <input type="checkbox" 
                       wire:model="is_enabled" 
                      class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Cuenta Habilitada</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <button type="submit" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                @if($accountId)
                    Actualizar Cuenta
                @else
                    Crear Cuenta
                @endif
            </button>
        </div>
    </form>
</div>
