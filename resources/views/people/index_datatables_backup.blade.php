<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800  leading-tight">
            {{ __('Gestión de Personas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white  overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 ">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Personas Registradas</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#personModal">
                            <i class="fas fa-plus"></i> Nueva Persona
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="people-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre Completo</th>
                                    <th>RUT</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Persona -->
    <div class="modal fade" id="personModal" tabindex="-1" aria-labelledby="personModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="personModalLabel">Nueva Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="personForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                                <div class="invalid-feedback" id="first_name_error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                                <div class="invalid-feedback" id="last_name_error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="rut" class="form-label">RUT</label>
                                <input type="text" class="form-control" id="rut" name="rut" placeholder="12345678-9" required>
                                <div class="form-text">Formato: 12345678-9 (sin puntos, con guión)</div>
                                <div class="invalid-feedback" id="rut_error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback" id="email_error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                                <div class="invalid-feedback" id="phone_error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="role_type" class="form-label">Rol</label>
                                <select class="form-select" id="role_type" name="role_type" required>
                                    <option value="trabajador">Trabajador</option>
                                    <option value="tesorero">Tesorero</option>
                                </select>
                                <div class="invalid-feedback" id="role_type_error"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" checked>
                                    <label class="form-check-label" for="is_enabled">
                                        Activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function() {
        // Configurar Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };

        // Inicializar DataTable
        let table = $('#people-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('datatables.people') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'full_name', name: 'full_name'},
                {data: 'rut_formatted', name: 'rut_formatted'},
                {data: 'email', name: 'email', defaultContent: '-'},
                {data: 'phone', name: 'phone', defaultContent: '-'},
                {data: 'status', name: 'status', orderable: false},
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
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    className: 'btn btn-secondary btn-sm',
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    title: 'Lista de Personas - COTESO',
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Lista de Personas - COTESO',
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'colvis',
                    text: '<i class="fas fa-columns"></i> Columnas',
                    className: 'btn btn-secondary btn-sm',
                    columns: ':not(:last-child)' // No permitir ocultar columna de Acciones
                }
            ],
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            order: [[1, 'asc']],
            searchHighlight: true,
            columnDefs: [
                { targets: [0, 6], orderable: false }
            ]
        });

        // Manejar formulario de persona
        $('#personForm').on('submit', function(e) {
            e.preventDefault();
            
            // Limpiar errores previos
            clearValidationErrors();
            
            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('people.store') }}",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#personModal').modal('hide');
                    table.ajax.reload();
                    toastr.success('Persona guardada exitosamente');
                    $('#personForm')[0].reset();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        // Errores de validación
                        let errors = xhr.responseJSON.errors;
                        showValidationErrors(errors);
                    } else {
                        // Otros errores
                        let message = xhr.responseJSON && xhr.responseJSON.message 
                            ? xhr.responseJSON.message 
                            : 'Error al guardar la persona';
                        toastr.error(message);
                    }
                }
            });
        });
    });

    // Funciones globales para las acciones
    function editPerson(id) {
        // Limpiar errores antes de cargar datos
        clearValidationErrors();
        
        // Cambiar título del modal
        $('#personModalLabel').text('Editar Persona');
        
        // Cargar datos de la persona
        $.ajax({
            url: '/people/' + id,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const person = response.data;
                    
                    // Llenar formulario con datos de la persona
                    $('#first_name').val(person.first_name);
                    $('#last_name').val(person.last_name);
                    $('#rut').val(person.rut);
                    $('#email').val(person.email);
                    $('#phone').val(person.phone || '');
                    $('#role_type').val(person.role_type);
                    $('#bank_name').val(person.bank_name || '');
                    $('#account_type').val(person.account_type || '');
                    $('#account_number').val(person.account_number || '');
                    $('#address').val(person.address || '');
                    $('#is_enabled').prop('checked', person.is_enabled);
                    
                    // Cambiar la acción del formulario para actualización
                    $('#personForm').attr('data-action', 'update');
                    $('#personForm').attr('data-id', id);
                    
                    // Abrir el modal
                    $('#personModal').modal('show');
                } else {
                    toastr.error('Error al cargar los datos de la persona');
                }
            },
            error: function() {
                toastr.error('Error al cargar los datos de la persona');
            }
        });
    }

    function deletePerson(id) {
        if (confirm('¿Está seguro de que desea eliminar esta persona?')) {
            $.ajax({
                url: '/people/' + id,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#people-table').DataTable().ajax.reload();
                    toastr.success('Persona eliminada exitosamente');
                },
                error: function() {
                    toastr.error('Error al eliminar la persona');
                }
            });
        }
    }

    // Función para mostrar errores de validación en los campos
    function showValidationErrors(errors) {
        for (let field in errors) {
            let errorElement = $('#' + field + '_error');
            let inputElement = $('#' + field);
            
            if (errorElement.length > 0) {
                errorElement.text(errors[field][0]).show();
                inputElement.addClass('is-invalid');
            } else {
                // Si no hay elemento específico, mostrar con toastr como fallback
                toastr.error(field + ': ' + errors[field][0]);
            }
        }
    }

    // Función para limpiar errores de validación
    function clearValidationErrors() {
        $('.invalid-feedback').text('').hide();
        $('.form-control, .form-select').removeClass('is-invalid');
    }

    // Limpiar errores cuando se abra el modal
    $('#personModal').on('show.bs.modal', function () {
        clearValidationErrors();
    });
    </script>
    @endpush
</x-app-layout>
