<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="h4 text-gray-900 ">Lista de Personas</h3>
        <button wire:click="create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nueva Persona
        </button>
    </div>

    @if($showingForm)
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title text-gray-900  mb-3">
                    {{ $editing ? 'Editar Persona' : 'Nueva Persona' }}
                </h5>
                
                <form wire:submit.prevent="save">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-gray-900 ">Nombre</label>
                            <input type="text" wire:model="first_name" class="form-control @error('first_name') is-invalid @enderror">
                            @error('first_name') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-gray-900 ">Apellido</label>
                            <input type="text" wire:model="last_name" class="form-control @error('last_name') is-invalid @enderror">
                            @error('last_name') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-gray-900 ">RUT</label>
                            <input type="text" wire:model="rut" placeholder="12345678-9" class="form-control @error('rut') is-invalid @enderror">
                            <div class="form-text">Formato: 12345678-9 (sin puntos, con guión)</div>
                            @error('rut') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-gray-900 ">Email</label>
                            <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror">
                            @error('email') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-gray-900 ">Teléfono</label>
                            <input type="text" wire:model="phone" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-gray-900 ">Rol</label>
                            <select wire:model="role_type" class="form-select @error('role_type') is-invalid @enderror">
                                <option value="trabajador">Trabajador</option>
                                <option value="tesorero">Tesorero</option>
                            </select>
                            @error('role_type') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" wire:model="is_enabled" class="form-check-input" id="is_enabled">
                            <label class="form-check-label text-gray-900 " for="is_enabled">Activo</label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ $editing ? 'Actualizar' : 'Guardar' }}
                        </button>
                        <button type="button" wire:click="cancel" class="btn btn-secondary">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Búsqueda -->
    <div class="row mb-3">
        <div class="col-md-6">
            <input type="text" wire:model.live="search" placeholder="Buscar personas..." 
                  class="form-control">
        </div>
    </div>

    <!-- Tabla -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th wire:click="sortBy('first_name')" style="cursor: pointer;" class="user-select-none">
                        Nombre
                        @if($sortField === 'first_name')
                            @if($sortDirection === 'asc')
                                <i class="fas fa-sort-up"></i>
                            @else
                                <i class="fas fa-sort-down"></i>
                            @endif
                        @else
                            <i class="fas fa-sort"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('last_name')" style="cursor: pointer;" class="user-select-none">
                        Apellido
                        @if($sortField === 'last_name')
                            @if($sortDirection === 'asc')
                                <i class="fas fa-sort-up"></i>
                            @else
                                <i class="fas fa-sort-down"></i>
                            @endif
                        @else
                            <i class="fas fa-sort"></i>
                        @endif
                    </th>
                    <th wire:click="sortBy('rut')" style="cursor: pointer;" class="user-select-none">
                        RUT
                        @if($sortField === 'rut')
                            @if($sortDirection === 'asc')
                                <i class="fas fa-sort-up"></i>
                            @else
                                <i class="fas fa-sort-down"></i>
                            @endif
                        @else
                            <i class="fas fa-sort"></i>
                        @endif
                    </th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($people as $person)
                    <tr>
                        <td>{{ $person->first_name }}</td>
                        <td>{{ $person->last_name }}</td>
                        <td>{{ $person->rut }}</td>
                        <td>{{ $person->email }}</td>
                        <td>{{ $person->phone ?? '-' }}</td>
                        <td>
                            @if($person->role_type === 'tesorero')
                                <span class="badge bg-primary">Tesorero</span>
                            @else
                                <span class="badge bg-secondary">Trabajador</span>
                            @endif
                        </td>
                        <td>
                            @if($person->is_enabled)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button wire:click="edit({{ $person->id }})" class="btn btn-outline-primary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="delete({{ $person->id }})" class="btn btn-outline-danger btn-sm" 
                                        wire:confirm="¿Está seguro de que desea eliminar esta persona?" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No se encontraron personas</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-center">
        {{ $people->links() }}
    </div>
</div>

@push('scripts')
<script>
    // Escuchar eventos de Livewire para mostrar notificaciones con Toastr
    document.addEventListener('livewire:init', function () {
        Livewire.on('showToastr', function (data) {
            if (data.type === 'success') {
                toastr.success(data.message);
            } else if (data.type === 'error') {
                toastr.error(data.message);
            }
        });
    });
</script>
@endpush