<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Bancos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Gestión de Bancos</h1>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bankModal" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Agregar Banco
                        </button>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table id="banks-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
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

    <!-- Modal para agregar/editar banco -->
    <div class="modal fade" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bankModalLabel">Agregar Banco</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bankForm">
                        @csrf
                        <input type="hidden" id="bankId" name="id">
                        
                        <div class="mb-3">
                            <label for="code" class="form-label">Código *</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveBank()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let bankTable;
        
        $(document).ready(function() {
            // Inicializar DataTable
            bankTable = $('#banks-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('datatables.banks') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'code', name: 'code'},
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
            $('#bankModalLabel').text('Agregar Banco');
            $('#bankForm')[0].reset();
            $('#bankId').val('');
            $('.form-control').removeClass('is-invalid');
            $('#bankModal').modal('show');
        }

        function editBank(id) {
            $.get("{{ route('banks.index') }}/" + id, function(data) {
                $('#bankModalLabel').text('Editar Banco');
                $('#bankId').val(data.id);
                $('#code').val(data.code);
                $('#name').val(data.name);
                $('.form-control').removeClass('is-invalid');
                $('#bankModal').modal('show');
            });
        }

        function saveBank() {
            const id = $('#bankId').val();
            const url = id ? "{{ route('banks.index') }}/" + id : "{{ route('banks.store') }}";
            const method = id ? 'PUT' : 'POST';
            
            $.ajax({
                url: url,
                type: method,
                data: $('#bankForm').serialize(),
                success: function(response) {
                    $('#bankModal').modal('hide');
                    bankTable.ajax.reload();
                    toastr.success('Banco guardado exitosamente');
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
                        toastr.error('Error al guardar el banco');
                    }
                }
            });
        }

        function deleteBank(id, name) {
            if (confirm('¿Estás seguro de que quieres eliminar el banco "' + name + '"?')) {
                $.ajax({
                    url: "{{ route('banks.index') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        bankTable.ajax.reload();
                        toastr.success('Banco eliminado exitosamente');
                    },
                    error: function(xhr) {
                        if (xhr.status === 400) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Error al eliminar el banco');
                        }
                    }
                });
            }
        }
    </script>
    @endpush
</x-app-layout>
