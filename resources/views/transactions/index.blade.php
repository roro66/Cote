<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Transacciones
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Transacciones</h3>
                        <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Transacción
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table id="transactions-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Número</th>
                                    <th>Tipo</th>
                                    <th>Cuenta Origen</th>
                                    <th>Cuenta Destino</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Creado por</th>
                                    <th>Fecha</th>
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
        // Inicializar DataTable para Transacciones
        let table = $('#transactions-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('datatables.transactions') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'transaction_number', name: 'transaction_number'},
                {data: 'type_spanish', name: 'type_spanish'},
                {data: 'from_account_name', name: 'from_account_name'},
                {data: 'to_account_name', name: 'to_account_name'},
                {data: 'amount_formatted', name: 'amount_formatted', className: 'text-end'},
                {data: 'status_spanish', name: 'status_spanish', orderable: false},
                {data: 'creator_name', name: 'creator_name'},
                {data: 'created_at_formatted', name: 'created_at_formatted'},
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
                    filename: 'transacciones_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'transacciones_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'transacciones_' + new Date().toISOString().split('T')[0],
                    title: 'Lista de Transacciones - COTESO',
                    orientation: 'landscape',
                    exportOptions: {
                        columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Lista de Transacciones - COTESO',
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
            order: [[8, 'desc']], // Ordenar por fecha descendente
            columnDefs: [
                { targets: [0, 9], orderable: false }
            ],
            scrollX: true
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
    function viewTransaction(id) {
        window.location.href = '/transactions/' + id;
    }

    function approveTransaction(id) {
        if (confirm('¿Está seguro de que desea aprobar esta transacción?')) {
            $.ajax({
                url: '/transactions/' + id + '/approve',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#transactions-table').DataTable().ajax.reload();
                    toastr.success('Transacción aprobada exitosamente');
                },
                error: function() {
                    toastr.error('Error al aprobar la transacción');
                }
            });
        }
    }

    function rejectTransaction(id) {
        let reason = prompt('¿Cuál es el motivo del rechazo?');
        if (reason !== null) {
            $.ajax({
                url: '/transactions/' + id + '/reject',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reason: reason
                },
                success: function(response) {
                    $('#transactions-table').DataTable().ajax.reload();
                    toastr.success('Transacción rechazada');
                },
                error: function() {
                    toastr.error('Error al rechazar la transacción');
                }
            });
        }
    }
    </script>
    @endpush
</x-app-layout>
