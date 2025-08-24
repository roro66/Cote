<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nueva Cuenta
            </h2>
            <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('accounts.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre de la Cuenta *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Tipo de Cuenta *</label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="treasury" {{ old('type') == 'treasury' ? 'selected' : '' }}>
                                            Tesorería
                                        </option>
                                        <option value="person" {{ old('type') == 'person' ? 'selected' : '' }}>
                                            Personal
                                        </option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6" id="person-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="person_id" class="form-label">Persona Propietaria</label>
                                    <select class="form-select @error('person_id') is-invalid @enderror" 
                                            id="person_id" name="person_id">
                                        <option value="">Seleccionar persona...</option>
                                        @foreach($people as $person)
                                            <option value="{{ $person->id }}" {{ old('person_id') == $person->id ? 'selected' : '' }}>
                                                {{ $person->first_name }} {{ $person->last_name }} - {{ $person->rut }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('person_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="balance" class="form-label">Saldo Inicial *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control @error('balance') is-invalid @enderror" 
                                               id="balance" name="balance" value="{{ old('balance', 0) }}" 
                                               step="0.01" min="0" required>
                                        @error('balance')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notas</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="hidden" name="is_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="is_enabled" name="is_enabled" 
                                   value="1" {{ old('is_enabled', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_enabled">
                                Cuenta habilitada
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Cuenta
                            </button>
                            <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            const personField = document.getElementById('person-field');
            const personSelect = document.getElementById('person_id');
            
            function togglePersonField() {
                if (typeSelect.value === 'person') {
                    personField.style.display = 'block';
                    personSelect.required = true;
                } else {
                    personField.style.display = 'none';
                    personSelect.required = false;
                    personSelect.value = '';
                }
            }
            
            typeSelect.addEventListener('change', togglePersonField);
            togglePersonField(); // Ejecutar al cargar para manejar old() values
        });
    </script>
    @endpush
</x-app-layout>
