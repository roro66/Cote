<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Tipos de Cuenta') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Gestión de Tipos de Cuenta</h1>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accountTypeModal" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Agregar Tipo de Cuenta
                        </button>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table id="account-types-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Personas Asociadas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para agregar/editar tipo de cuenta -->
    <div class="modal fade" id="accountTypeModal" tabindex="-1" aria-labelledby="accountTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountTypeModalLabel">Agregar Tipo de Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="accountTypeForm">
                        @csrf
                        <input type="hidden" id="accountTypeId" name="id">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveAccountType()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let accountTypeTable;
        
        $(document).ready(function() {
            // Inicializar DataTable
            accountTypeTable = $('#account-types-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('datatables.account-types') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'people_count', name: 'people_count', searchable: false, orderable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {
                    "decimal": "",
                    "emptyTable": "No hay datos disponibles en la tabla",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
                    "infoFiltered": "(filtrado de _MAX_ entradas totales)",
                    "infoPostFix": "",
                    "thousands": ".",
                    "lengthMenu": "Mostrar _MENU_ entradas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron registros coincidentes",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                dom: '<"row mb-3"<"col-sm-4"B><"col-sm-4"f><"col-sm-4"l>>rtip',
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                pageLength: 10
            });
        });

        function openAddModal() {
            $('#accountTypeModalLabel').text('Agregar Tipo de Cuenta');
            $('#accountTypeForm')[0].reset();
            $('#accountTypeId').val('');
            $('.form-control').removeClass('is-invalid');
            $('#accountTypeModal').modal('show');
        }

        function editAccountType(id) {
            $.get("{{ route('account-types.index') }}/" + id, function(data) {
                $('#accountTypeModalLabel').text('Editar Tipo de Cuenta');
                $('#accountTypeId').val(data.id);
                $('#name').val(data.name);
                $('.form-control').removeClass('is-invalid');
                $('#accountTypeModal').modal('show');
            });
        }

        function saveAccountType() {
            const id = $('#accountTypeId').val();
            const url = id ? "{{ route('account-types.index') }}/" + id : "{{ route('account-types.store') }}";
            const method = id ? 'PUT' : 'POST';
            
            $.ajax({
                url: url,
                type: method,
                data: $('#accountTypeForm').serialize(),
                success: function(response) {
                    $('#accountTypeModal').modal('hide');
                    accountTypeTable.ajax.reload();
                    toastr.success('Tipo de cuenta guardado exitosamente');
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $('.form-control').removeClass('is-invalid');
                        $('.invalid-feedback').text('');
                        
                        for (let field in errors) {
                            $('#' + field).addClass('is-invalid');
                            $('#' + field).next('.invalid-feedback').text(errors[field][0]);
                        }
                    } else {
                        toastr.error('Error al guardar el tipo de cuenta');
                    }
                }
            });
        }

        function deleteAccountType(id, name) {
            if (confirm('¿Estás seguro de que quieres eliminar el tipo de cuenta "' + name + '"?')) {
                $.ajax({
                    url: "{{ route('account-types.index') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        accountTypeTable.ajax.reload();
                        toastr.success('Tipo de cuenta eliminado exitosamente');
                    },
                    error: function(xhr) {
                        if (xhr.status === 400) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Error al eliminar el tipo de cuenta');
                        }
                    }
                });
            }
        }
    </script>
    @endpush
</x-app-layout>
