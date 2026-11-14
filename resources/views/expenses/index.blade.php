<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gestión de Rendiciones
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Rendiciones de Gastos</h3>
                        @if (auth()->user()->hasAnyRole(['boss', 'treasurer']))
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('expenses.create') }}" class="btn btn-outline-primary" title="Formulario con un comprobante por gasto, como lo llenaría el trabajador">
                                    <i class="fas fa-plus"></i> Nueva Rendición
                                </a>
                                <a href="{{ route('expenses.intake') }}" class="btn btn-warning">
                                    <i class="fas fa-file-invoice-dollar"></i> Registrar rendición recibida
                                </a>
                            </div>
                        @else
                            <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nueva Rendición
                            </a>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table id="expenses-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Número</th>
                                    <th>Título</th>
                                    <th>Solicitante</th>
                                    <th>Cuenta</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha Envío</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <!-- Formulario oculto para eliminación -->
                    <form id="deleteForm" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function() {
        // Mostrar mensajes toastr desde la sesión
        
        // Inicializar DataTable para Gastos
        let table = $('#expenses-table').DataTable({
            processing: true,
            serverSide: true,
            search: { search: new URLSearchParams(window.location.search).get('buscar') || '' },
            ajax: "{{ route('datatables.expenses') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'expense_number', name: 'expense_number', orderable: true},
                {data: 'title', name: 'title', orderable: true},
                {data: 'submitter_name', name: 'submitter_name', orderable: true},
                {data: 'account_name', name: 'account_name', orderable: true},
                {data: 'total_amount_formatted', name: 'total_amount_formatted', className: 'text-end', orderable: true},
                {data: 'status_spanish', name: 'status_spanish', orderable: true},
                {data: 'submitted_at_formatted', name: 'submitted_at_formatted', orderable: true},
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
            layout: { topStart: ['buttons', 'pageLength'], topEnd: 'search', bottomStart: 'info', bottomEnd: 'paging' },
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            pageLength: 25,
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Descargar Excel',
                    className: 'btn btn-success btn-sm',
                    filename: 'gastos_' + new Date().toISOString().split('T')[0],
                    exportOptions: {
                        columns: dtExportColumns
                    }
                }
            ],
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            order: [[7, 'desc']], // Ordenar por fecha de envío descendente
            columnDefs: [
                { targets: [0, 8], orderable: false }
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

    // Función para eliminar expense
    function deleteExpense(id) {
        coteConfirm('¿Está seguro de que desea eliminar esta rendición?', { confirmText: 'Eliminar' }).then(function (ok) {
            if (!ok) return;
            const form = document.getElementById('deleteForm');
            form.action = '/expenses/' + id;
            form.submit();
        });
    }
    </script>
    @endpush
</x-app-layout>
