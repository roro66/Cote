<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Personas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Personas Registradas</h3>
                        <a href="{{ route('people.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Persona
                        </a>
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

    @push('scripts')
    <script>
    $(document).ready(function() {
        // Inicializar DataTable para Personas
        let table = $('#people-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('datatables.people') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'full_name', name: 'full_name', orderable: false},
                {data: 'rut', name: 'rut'},
                {data: 'email', name: 'email'},
                {data: 'phone', name: 'phone'},
                {data: 'status', name: 'is_enabled', orderable: false},
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
            dom: '<"row"<"col-sm-12"<"d-flex justify-content-between align-items-center"<"dt-buttons"B><"dt-length"l>>>>frtip',
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            pageLength: 25,
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    className: 'btn btn-secondary btn-sm',
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    }
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    title: 'Lista de Personas - COTESO',
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Lista de Personas - COTESO',
                    exportOptions: {
                        columns: ':visible:not(:last-child)'
                    }
                }
            ],
            responsive: true,
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0, 6], orderable: false }
            ]
        });
        
        // Aplicar estilos personalizados para el layout
        setTimeout(function() {
            $('.dt-buttons').css({
                'display': 'flex',
                'gap': '5px'
            });
            $('.dt-length label').addClass('d-flex align-items-center gap-2');
            $('.dt-length select').addClass('form-select form-select-sm');
        }, 100);
    });

    // Funciones globales para las acciones
    function editPerson(id) {
        window.location.href = '/people/' + id + '/edit';
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
                    if (response.success) {
                        $('#people-table').DataTable().ajax.reload();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    var response = xhr.responseJSON;
                    if (response && response.message) {
                        toastr.error(response.message);
                    } else {
                        toastr.error('Error al eliminar la persona');
                    }
                }
            });
        }
    }
    </script>
    @endpush
</x-app-layout>
