<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Cuentas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Cuentas Registradas</h3>
                        <a href="{{ route('accounts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Cuenta
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table id="accounts-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Propietario</th>
                                    <th>Saldo</th>
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
        // Inicializar DataTable para Cuentas
        let table = $('#accounts-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('datatables.accounts') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'type_spanish', name: 'type_spanish'},
                {data: 'owner', name: 'owner'},
                {data: 'balance_formatted', name: 'balance_formatted', className: 'text-end'},
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
                    filename: 'cuentas_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'cuentas_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'cuentas_' + new Date().toISOString().split('T')[0],
                    title: 'Lista de Cuentas - COTESO',
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Lista de Cuentas - COTESO',
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
    function editAccount(id) {
        window.location.href = '/accounts/' + id + '/edit';
    }

    function viewTransactions(id) {
        window.location.href = '/transactions?account_id=' + id;
    }

    function deleteAccount(id) {
        if (confirm('¿Está seguro de que desea eliminar esta cuenta?')) {
            $.ajax({
                url: '/accounts/' + id,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#accounts-table').DataTable().ajax.reload();
                    toastr.success('Cuenta eliminada exitosamente');
                },
                error: function() {
                    toastr.error('Error al eliminar la cuenta');
                }
            });
        }
    }
    </script>
    @endpush
</x-app-layout>
