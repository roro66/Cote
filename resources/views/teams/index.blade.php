<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Equipos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Equipos/Cuadrillas</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teamModal">
                            <i class="fas fa-plus"></i> Nuevo Equipo
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="teams-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre del Equipo</th>
                                    <th>Líder</th>
                                    <th>RUT del Líder</th>
                                    <th>Descripción</th>
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
        // Inicializar DataTable para Equipos
        let table = $('#teams-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('datatables.teams') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'leader_name', name: 'leader_name'},
                {data: 'leader_rut', name: 'leader_rut'},
                {data: 'description', name: 'description', defaultContent: '-'},
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
            dom: '<"row"<"col-sm-12"<"d-flex justify-content-between align-items-center"<"dt-buttons"B><"dt-length"l>>>>frtip',
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            pageLength: 25,
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
                    filename: 'equipos_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'equipos_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'equipos_' + new Date().toISOString().split('T')[0],
                    title: 'Lista de Equipos - COTESO',
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Lista de Equipos - COTESO',
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
    function editTeam(id) {
        console.log('Editar equipo:', id);
    }

    function deleteTeam(id) {
        if (confirm('¿Está seguro de que desea eliminar este equipo?')) {
            $.ajax({
                url: '/teams/' + id,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#teams-table').DataTable().ajax.reload();
                    toastr.success('Equipo eliminado exitosamente');
                },
                error: function() {
                    toastr.error('Error al eliminar el equipo');
                }
            });
        }
    }
    </script>
    @endpush
</x-app-layout>
