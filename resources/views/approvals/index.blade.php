<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Sistema de Aprobaciones
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Alertas -->
                    <div id="approval-alerts"></div>

                    <!-- Pestañas de Navegación -->
                    <ul class="nav nav-tabs mb-4" id="approvalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab"
                                data-bs-target="#transactions" type="button" role="tab"
                                aria-controls="transactions" aria-selected="true">
                                <i class="fas fa-exchange-alt me-2"></i>Transferencias Pendientes
                                <span id="transactions-badge" class="badge bg-warning text-dark ms-2"
                                    style="display:none">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses"
                                type="button" role="tab" aria-controls="expenses" aria-selected="false">
                                <i class="fas fa-receipt me-2"></i>Rendiciones Pendientes
                                <span id="expenses-badge" class="badge bg-info text-dark ms-2"
                                    style="display:none">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="approvalTabContent">
                        <!-- Pestaña de Transferencias -->
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel"
                            aria-labelledby="transactions-tab">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="h4">Transferencias Pendientes de Aprobación</h3>
                            </div>

                            <div class="table-responsive">
                                <table id="transactions-table" class="table table-striped table-bordered"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>N° Transacción</th>
                                            <th>Fecha</th>
                                            <th>Desde</th>
                                            <th>Hacia</th>
                                            <th>Monto</th>
                                            <th>Concepto</th>
                                            <th>Solicitado por</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- Pestaña de Rendiciones -->
                        <div class="tab-pane fade" id="expenses" role="tabpanel" aria-labelledby="expenses-tab">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="h4">Rendiciones Pendientes de Aprobación</h3>
                            </div>

                            <div class="table-responsive">
                                <table id="expenses-table" class="table table-striped table-bordered"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Persona</th>
                                            <th>Descripción</th>
                                            <th>Monto Total</th>
                                            <th>Items</th>
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
        </div>
    </div>

    <!-- Modal para Motivo de Rechazo -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Motivo del Rechazo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="rejectForm">
                        <div class="mb-3">
                            <label for="rejectReason" class="form-label">Ingrese el motivo del rechazo:</label>
                            <textarea class="form-control" id="rejectReason" name="reject_reason" rows="3" required
                                placeholder="Explique brevemente por qué se rechaza esta solicitud..."></textarea>
                        </div>
                        <input type="hidden" id="rejectItemId" name="item_id">
                        <input type="hidden" id="rejectItemType" name="item_type">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="confirmReject()">
                        <i class="fas fa-times me-2"></i>Rechazar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Detalles de Rendición -->
    <div class="modal fade" id="expenseDetailsModal" tabindex="-1" aria-labelledby="expenseDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="expenseDetailsModalLabel">Detalles de la Rendición</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="expenseDetailsContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Token CSRF para las peticiones AJAX
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Variables para el modal de rechazo
            let currentRejectId = null;
            let currentRejectType = null;
            let transactionsTable = null;
            let expensesTable = null;

            $(document).ready(function() {
                // Inicializar DataTable para Transferencias
                transactionsTable = $('#transactions-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('datatables.approvals.transactions') }}',
                    columns: [{
                            data: 'transaction_number',
                            name: 'transaction_number',
                            render: (d) => `<span class="badge bg-secondary">${d}</span>`
                        },
                        {
                            data: 'created_at',
                            name: 'created_at',
                            render: (d) => new Date(d).toLocaleString('es-CL')
                        },
                        {
                            data: 'from_account',
                            name: 'from_account',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'to_account',
                            name: 'to_account',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'amount',
                            name: 'amount',
                            render: (d) =>
                                `<span class="fw-bold text-success">$${new Intl.NumberFormat('es-CL').format(d)} CLP</span>`
                        },
                        {
                            data: 'description',
                            name: 'description',
                            render: (d) =>
                                `<span class="text-truncate" style="max-width:200px;" title="${d}">${(d||'').length>30?(d||'').substring(0,30)+'...':(d||'')}</span>`
                        },
                        {
                            data: 'created_by',
                            name: 'created_by'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    order: [
                        [1, 'desc']
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
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Todos"]
                    ],
                    pageLength: 25,
                    drawCallback: function(settings) {
                        const info = this.api().page.info();
                        const badge = document.getElementById('transactions-badge');
                        if (badge) {
                            badge.textContent = info.recordsTotal;
                            badge.style.display = info.recordsTotal > 0 ? 'inline-block' : 'none';
                        }
                    },
                    buttons: [{
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
                            filename: 'transferencias_pendientes_' + new Date().toISOString().split('T')[0],
                            exportOptions: {
                                columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                            }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel"></i> Excel',
                            className: 'btn btn-secondary btn-sm',
                            filename: 'transferencias_pendientes_' + new Date().toISOString().split('T')[0],
                            exportOptions: {
                                columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf"></i> PDF',
                            className: 'btn btn-secondary btn-sm',
                            filename: 'transferencias_pendientes_' + new Date().toISOString().split('T')[0],
                            title: 'Transferencias Pendientes - COTE',
                            exportOptions: {
                                columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print"></i> Imprimir',
                            className: 'btn btn-secondary btn-sm',
                            title: 'Transferencias Pendientes - COTE',
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
                    pageLength: 25
                });

                // Inicializar DataTable para Rendiciones
                expensesTable = $('#expenses-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('datatables.approvals.expenses') }}',
                    columns: [{
                            data: 'expense_date',
                            name: 'expense_date',
                            render: (d) => new Date(d).toLocaleDateString('es-CL')
                        },
                        {
                            data: 'person_name',
                            name: 'person_name',
                            orderable: false,
                            searchable: false,
                            render: (d) =>
                                `<div class="d-flex align-items-center"><i class=\"fas fa-user text-primary me-2\"></i>${d}</div>`
                        },
                        {
                            data: 'description',
                            name: 'description',
                            render: (d) =>
                                `<span class=\"text-truncate\" style=\"max-width:200px;\" title=\"${d}\">${(d||'').length>30?(d||'').substring(0,30)+'...':(d||'')}</span>`
                        },
                        {
                            data: 'total_amount',
                            name: 'total_amount',
                            render: (d) =>
                                `<span class=\"fw-bold text-info\">$${new Intl.NumberFormat('es-CL').format(d)} CLP</span>`
                        },
                        {
                            data: 'items_count',
                            name: 'items_count',
                            render: (d) => `<span class=\"badge bg-secondary\">${d} items</span>`,
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'status',
                            render: (d) =>
                                `<span class=\"badge bg-warning text-dark\">${d.charAt(0).toUpperCase()+d.slice(1)}</span>`
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    order: [
                        [0, 'desc']
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
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Todos"]
                    ],
                    pageLength: 25,
                    drawCallback: function(settings) {
                        const info = this.api().page.info();
                        const badge = document.getElementById('expenses-badge');
                        if (badge) {
                            badge.textContent = info.recordsTotal;
                            badge.style.display = info.recordsTotal > 0 ? 'inline-block' : 'none';
                        }
                    },
                    buttons: [{
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
                            filename: 'rendiciones_pendientes_' + new Date().toISOString().split('T')[0],
                            exportOptions: {
                                columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                            }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="fas fa-file-excel"></i> Excel',
                            className: 'btn btn-secondary btn-sm',
                            filename: 'rendiciones_pendientes_' + new Date().toISOString().split('T')[0],
                            exportOptions: {
                                columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fas fa-file-pdf"></i> PDF',
                            className: 'btn btn-secondary btn-sm',
                            filename: 'rendiciones_pendientes_' + new Date().toISOString().split('T')[0],
                            title: 'Rendiciones Pendientes - COTE',
                            exportOptions: {
                                columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fas fa-print"></i> Imprimir',
                            className: 'btn btn-secondary btn-sm',
                            title: 'Rendiciones Pendientes - COTE',
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
                    pageLength: 25
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

                // Manejar cambio de pestañas
                $('#approvalTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                    if (e.target.getAttribute('aria-controls') === 'transactions') {
                        transactionsTable.columns.adjust().responsive.recalc();
                    } else if (e.target.getAttribute('aria-controls') === 'expenses') {
                        expensesTable.columns.adjust().responsive.recalc();
                    }
                });
            });

            /**
             * Aprueba una transferencia
             */
            function approveTransaction(transactionId) {
                if (!confirm('¿Está seguro que desea aprobar esta transferencia?')) {
                    return;
                }

                fetch(`/approvals/transactions/${transactionId}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message);
                            transactionsTable.row((idx, rowData) => rowData.id === transactionId).remove().draw();
                            updateTransactionBadge();
                        } else {
                            toastr.error(data.message || 'Error al aprobar la transferencia');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Error de conexión al aprobar la transferencia');
                    });
            }

            /**
             * Prepara el rechazo de una transferencia
             */
            function rejectTransaction(transactionId) {
                currentRejectId = transactionId;
                currentRejectType = 'transaction';

                const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
                modal.show();
            }

            /**
             * Aprueba una rendición
             */
            function approveExpense(expenseId) {
                if (!confirm('¿Está seguro que desea aprobar esta rendición?')) {
                    return;
                }

                fetch(`/approvals/expenses/${expenseId}/approve`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message);
                            expensesTable.row((idx, rowData) => rowData.id === expenseId).remove().draw();
                            updateExpenseBadge();
                        } else {
                            toastr.error(data.message || 'Error al aprobar la rendición');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Error de conexión al aprobar la rendición');
                    });
            }

            /**
             * Prepara el rechazo de una rendición
             */
            function rejectExpense(expenseId) {
                currentRejectId = expenseId;
                currentRejectType = 'expense';

                const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
                modal.show();
            }

            /**
             * Confirma y ejecuta el rechazo
             */
            function confirmReject() {
                const reason = document.getElementById('rejectReason').value.trim();

                if (!reason) {
                    toastr.error('Debe ingresar un motivo para el rechazo');
                    return;
                }

                const url = currentRejectType === 'transaction' ?
                    `/approvals/transactions/${currentRejectId}/reject` :
                    `/approvals/expenses/${currentRejectId}/reject`;

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            reject_reason: reason
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message);

                            if (currentRejectType === 'transaction') {
                                transactionsTable.row((idx, rowData) => rowData.id === currentRejectId).remove().draw();
                                updateTransactionBadge();
                            } else {
                                expensesTable.row((idx, rowData) => rowData.id === currentRejectId).remove().draw();
                                updateExpenseBadge();
                            }

                            // Cerrar modal y limpiar formulario
                            const modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
                            modal.hide();
                            document.getElementById('rejectReason').value = '';
                        } else {
                            toastr.error(data.message ||
                                `Error al rechazar la ${currentRejectType === 'transaction' ? 'transferencia' : 'rendición'}`
                            );
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Error de conexión al rechazar la solicitud');
                    });
            }

            /**
             * Muestra los detalles de una rendición
             */
            function viewExpenseDetails(expenseId) {
                fetch(`/expenses/${expenseId}/details`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('expenseDetailsContent').innerHTML = data.html;
                            const modal = new bootstrap.Modal(document.getElementById('expenseDetailsModal'));
                            modal.show();
                        } else {
                            toastr.error('Error al cargar los detalles de la rendición');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Error de conexión al cargar los detalles');
                    });
            }

            /**
             * Actualiza el contador de transferencias pendientes
             */
            function updateTransactionBadge() {
                const badge = document.querySelector('#transactions-tab .badge');
                if (badge) {
                    const count = parseInt(badge.textContent) - 1;
                    if (count > 0) {
                        badge.textContent = count;
                    } else {
                        badge.remove();
                    }
                }
            }

            /**
             * Actualiza el contador de rendiciones pendientes
             */
            function updateExpenseBadge() {
                const badge = document.querySelector('#expenses-tab .badge');
                if (badge) {
                    const count = parseInt(badge.textContent) - 1;
                    if (count > 0) {
                        badge.textContent = count;
                    } else {
                        badge.remove();
                    }
                }
            }
        </script>
    @endpush
</x-app-layout>
