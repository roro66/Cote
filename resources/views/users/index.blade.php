<x-app-layout>
    <style>
        /* Empuja esta vista un poco más hacia abajo para evitar solaparse con el header fijo */
        .users-page-wrapper { margin-top: 16px; }
        @media (max-width: 640px) { .users-page-wrapper { margin-top: 20px; } }
    </style>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-8 users-page-wrapper">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 rounded bg-red-100 text-red-800">{{ session('error') }}</div>
            @endif
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Usuarios Registrados</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                            <i class="fas fa-plus"></i> Nuevo Usuario
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="users-table" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Roles</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Usuario -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createUserForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="name" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar contraseña</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="create_is_enabled" checked>
                                    <label class="form-check-label" for="create_is_enabled">Usuario Activo</label>
                                </div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Roles</label>
                                <div class="row">
                                    @foreach($roles as $role)
                                        <div class="col-6 col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="create_role_{{ $role->id }}">
                                                <label class="form-check-label" for="create_role_{{ $role->id }}">{{ $role->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitCreateUser()">Grabar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" id="edit_user_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nueva contraseña (opcional)</label>
                                <input type="password" name="password" id="edit_password" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar contraseña</label>
                                <input type="password" name="password_confirmation" id="edit_password_confirmation" class="form-control">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="edit_is_enabled">
                                    <label class="form-check-label" for="edit_is_enabled">Usuario Activo</label>
                                </div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Roles</label>
                                <div class="row" id="edit_roles_container">
                                    @foreach($roles as $role)
                                        <div class="col-6 col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="edit_role_{{ $role->id }}">
                                                <label class="form-check-label" for="edit_role_{{ $role->id }}">{{ $role->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditUser()">Actualizar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let usersTable;

        document.addEventListener('DOMContentLoaded', function () {
            usersTable = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('datatables.users') }}",
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' },
                    { data: 'roles', name: 'roles', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false },
                ],
                order: [[1, 'asc']],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' }
            });

            // Reset form on modal close
            document.getElementById('createUserModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('createUserForm').reset();
                clearValidationErrors('createUserForm');
            });
            document.getElementById('editUserModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('editUserForm').reset();
                clearValidationErrors('editUserForm');
            });
        });

        // Helpers validation
        function clearValidationErrors(formId) {
            const form = document.getElementById(formId);
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        }
        function showValidationErrors(formId, errors) {
            const form = document.getElementById(formId);
            Object.keys(errors).forEach(field => {
                const inputs = form.querySelectorAll(`[name="${field}"]`);
                if (inputs && inputs.length) {
                    inputs.forEach(input => {
                        input.classList.add('is-invalid');
                        const fb = input.closest('.mb-3, .form-check')?.querySelector('.invalid-feedback');
                        if (fb) fb.textContent = errors[field][0];
                    });
                }
            });
        }

        // Create
        function submitCreateUser() {
            clearValidationErrors('createUserForm');
            const form = document.getElementById('createUserForm');
            const formData = new FormData(form);
            // checkbox value handling
            formData.set('is_enabled', document.getElementById('create_is_enabled').checked ? '1' : '0');

            fetch('/users', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('#createUserForm input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw { status: res.status, data };
                return data;
            }).then((data) => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
                    usersTable.ajax.reload(null, false);
                    if (window.toastr) toastr.success(data.message); else alert(data.message);
                }
            }).catch(err => {
                if (err.status === 422 && err.data && err.data.errors) {
                    showValidationErrors('createUserForm', err.data.errors);
                    if (window.toastr) toastr.error('Corrija los errores del formulario');
                } else if (err.data && err.data.message) {
                    if (window.toastr) toastr.error(err.data.message); else alert(err.data.message);
                } else {
                    if (window.toastr) toastr.error('Error al crear usuario');
                }
            });
        }

        // Edit
        function openEditUserModal(id) {
            clearValidationErrors('editUserForm');
            fetch(`/users/${id}`, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(user => {
                    document.getElementById('edit_user_id').value = user.id;
                    document.getElementById('edit_name').value = user.name;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_is_enabled').checked = !!user.is_enabled;
                    // Clear roles checks
                    document.querySelectorAll('#edit_roles_container input[type="checkbox"]').forEach(cb => cb.checked = false);
                    if (Array.isArray(user.roles)) {
                        user.roles.forEach(r => {
                            const cb = document.querySelector(`#edit_roles_container input[type="checkbox"][value="${r}"]`);
                            if (cb) cb.checked = true;
                        });
                    }
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                })
                .catch(() => { if (window.toastr) toastr.error('No se pudo cargar el usuario'); });
        }

        function submitEditUser() {
            clearValidationErrors('editUserForm');
            const form = document.getElementById('editUserForm');
            const id = document.getElementById('edit_user_id').value;
            const fd = new FormData(form);
            // Laravel expects PUT via _method when using fetch POST
            fd.set('_method', 'PUT');
            // Omit password fields if empty to satisfy nullable
            if (!fd.get('password')) {
                fd.delete('password');
                fd.delete('password_confirmation');
            }
            // checkbox value handling
            fd.set('is_enabled', document.getElementById('edit_is_enabled').checked ? '1' : '0');

            fetch(`/users/${id}`, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('#editUserForm input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw { status: res.status, data };
                return data;
            }).then((data) => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                    usersTable.ajax.reload(null, false);
                    if (window.toastr) toastr.success(data.message); else alert(data.message);
                }
            }).catch(err => {
                if (err.status === 422 && err.data && err.data.errors) {
                    showValidationErrors('editUserForm', err.data.errors);
                    if (window.toastr) toastr.error('Corrija los errores del formulario');
                } else if (err.data && err.data.message) {
                    if (window.toastr) toastr.error(err.data.message); else alert(err.data.message);
                } else {
                    if (window.toastr) toastr.error('Error al actualizar usuario');
                }
            });
        }

        // Delete
        function deleteUser(id) {
            if (!confirm('¿Eliminar este usuario?')) return;
            const fd = new FormData();
            fd.set('_method', 'DELETE');
            fd.set('_token', '{{ csrf_token() }}');
            fetch(`/users/${id}`, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw { status: res.status, data };
                return data;
            }).then((data) => {
                usersTable.ajax.reload(null, false);
                if (window.toastr) toastr.success(data.message || 'Usuario eliminado');
            }).catch(err => {
                const msg = err?.data?.message || 'No se pudo eliminar el usuario';
                if (window.toastr) toastr.error(msg); else alert(msg);
            });
        }
        // Expose to global for DataTables actions
        window.openEditUserModal = openEditUserModal;
        window.deleteUser = deleteUser;
        window.submitCreateUser = submitCreateUser;
        window.submitEditUser = submitEditUser;
    </script>
    @endpush
</x-app-layout>
