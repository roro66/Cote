<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800  leading-tight">
            {{ __('Editar Persona') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white  overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 ">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Editar Persona: {{ $person->first_name }} {{ $person->last_name }}</h3>
                        <a href="{{ route('people.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>

                    <form action="{{ route('people.update', $person) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                           id="first_name" name="first_name" value="{{ old('first_name', $person->first_name) }}" required>
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Apellido <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                           id="last_name" name="last_name" value="{{ old('last_name', $person->last_name) }}" required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rut" class="form-label">RUT <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('rut') is-invalid @enderror" 
                                           id="rut" name="rut" value="{{ old('rut', $person->rut) }}" placeholder="12.345.678-9" required>
                                    @error('rut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $person->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $person->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role_type" class="form-label">Tipo de Rol <span class="text-danger">*</span></label>
                                    <select class="form-select @error('role_type') is-invalid @enderror" id="role_type" name="role_type" required>
                                        <option value="">Seleccionar rol...</option>
                                        <option value="tesorero" {{ old('role_type', $person->role_type) == 'tesorero' ? 'selected' : '' }}>Tesorero</option>
                                        <option value="trabajador" {{ old('role_type', $person->role_type) == 'trabajador' ? 'selected' : '' }}>Trabajador</option>
                                    </select>
                                    @error('role_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="bank_name" class="form-label">Banco</label>
                                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror" 
                                           id="bank_name" name="bank_name" value="{{ old('bank_name', $person->bank_name) }}">
                                    @error('bank_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="account_type" class="form-label">Tipo de Cuenta</label>
                                    <select class="form-select @error('account_type') is-invalid @enderror" id="account_type" name="account_type">
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="corriente" {{ old('account_type', $person->account_type) == 'corriente' ? 'selected' : '' }}>Cuenta Corriente</option>
                                        <option value="vista" {{ old('account_type', $person->account_type) == 'vista' ? 'selected' : '' }}>Cuenta Vista</option>
                                        <option value="ahorro" {{ old('account_type', $person->account_type) == 'ahorro' ? 'selected' : '' }}>Cuenta de Ahorro</option>
                                    </select>
                                    @error('account_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Número de Cuenta</label>
                                    <input type="text" class="form-control @error('account_number') is-invalid @enderror" 
                                           id="account_number" name="account_number" value="{{ old('account_number', $person->account_number) }}">
                                    @error('account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-10">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Dirección</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" 
                                            id="address" name="address" rows="2">{{ old('address', $person->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_enabled" 
                                               name="is_enabled" {{ old('is_enabled', $person->is_enabled) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_enabled">
                                            Persona Activa
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('people.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Persona
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function() {
        // Formatear RUT mientras se escribe
        $('#rut').on('input', function() {
            let rut = $(this).val().replace(/\D/g, '');
            if (rut.length >= 7) {
                let dv = rut.slice(-1);
                let number = rut.slice(0, -1);
                let formatted = number.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '-' + dv;
                $(this).val(formatted);
            }
        });

        // Mostrar mensajes de sesión como tostadas
        @if (session('success'))
            toastr.success('{{ session('success') }}');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}');
        @endif
    });
    </script>
    @endpush
</x-app-layout>
