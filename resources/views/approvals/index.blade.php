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
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Alertas -->
                    <div id="approval-alerts"></div>

                    <!-- Pestañas de Navegación -->
                    <ul class="nav nav-tabs mb-4" id="approvalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="true">
                                <i class="fas fa-exchange-alt me-2"></i>Transferencias Pendientes
                                @if($pendingTransactions->count() > 0)
                                    <span class="badge bg-warning text-dark ms-2">{{ $pendingTransactions->count() }}</span>
                                @endif
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="expenses-tab" data-bs-toggle="tab" data-bs-target="#expenses" type="button" role="tab" aria-controls="expenses" aria-selected="false">
                                <i class="fas fa-receipt me-2"></i>Rendiciones Pendientes
                                @if($pendingExpenses->count() > 0)
                                    <span class="badge bg-info text-dark ms-2">{{ $pendingExpenses->count() }}</span>
                                @endif
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="approvalTabContent">
                        <!-- Pestaña de Transferencias -->
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="h4">Transferencias Pendientes de Aprobación</h3>
                            </div>

                            <div class="table-responsive">
                                <table id="transactions-table" class="table table-striped table-bordered" style="width:100%">
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
                                <table id="expenses-table" class="table table-striped table-bordered" style="width:100%">
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
                            <textarea class="form-control" id="rejectReason" name="reject_reason" rows="3" required placeholder="Explique brevemente por qué se rechaza esta solicitud..."></textarea>
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
    <div class="modal fade" id="expenseDetailsModal" tabindex="-1" aria-labelledby="expenseDetailsModalLabel" aria-hidden="true">
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
                serverSide: false,
                data: @json($pendingTransactions),
                columns: [
                    {
                        data: 'transaction_number',
                        name: 'transaction_number',
                        render: function(data, type, row) {
                            return '<span class="badge bg-secondary">' + data + '</span>';
                        }
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type, row) {
                            return new Date(data).toLocaleString('es-CL');
                        }
                    },
                    {
                        data: 'from_account',
                        name: 'from_account',
                        render: function(data, type, row) {
                            const person = row.from_account?.person;
                            const accountType = row.from_account?.type === 'treasury' ? 'Tesorería' : 'Personal';
                            return `<div class="d-flex align-items-center">
                                        <i class="fas fa-university text-success me-2"></i>
                                        <div>
                                            <strong>${person?.name || 'N/A'}</strong><br>
                                            <small class="text-muted">${accountType}</small>
                                        </div>
                                    </div>`;
                        }
                    },
                    {
                        data: 'to_account',
                        name: 'to_account',
                        render: function(data, type, row) {
                            const person = row.to_account?.person;
                            const accountType = row.to_account?.type === 'treasury' ? 'Tesorería' : 'Personal';
                            return `<div class="d-flex align-items-center">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <div>
                                            <strong>${person?.name || 'N/A'}</strong><br>
                                            <small class="text-muted">${accountType}</small>
                                        </div>
                                    </div>`;
                        }
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        render: function(data, type, row) {
                            return '<span class="fw-bold text-success">$' + new Intl.NumberFormat('es-CL').format(data) + ' CLP</span>';
                        }
                    },
                    {
                        data: 'description',
                        name: 'description',
                        render: function(data, type, row) {
                            return '<span class="text-truncate" style="max-width: 200px;" title="' + data + '">' + 
                                   (data.length > 30 ? data.substring(0, 30) + '...' : data) + '</span>';
                        }
                    },
                    {
                        data: 'created_by',
                        name: 'created_by',
                        render: function(data, type, row) {
                            return row.created_by?.name || 'Sistema';
                        }
                    },
                    {
                        data: 'id',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `<div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success btn-sm" onclick="approveTransaction(${data})" title="Aprobar">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectTransaction(${data})" title="Rechazar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>`;
                        }
                    }
                ],
                order: [[1, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
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
                        title: 'Transferencias Pendientes - COTESO',
                        exportOptions: {
                            columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-secondary btn-sm',
                        title: 'Transferencias Pendientes - COTESO',
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
                serverSide: false,
                data: @json($pendingExpenses),
                columns: [
                    {
                        data: 'expense_date',
                        name: 'expense_date',
                        render: function(data, type, row) {
                            return new Date(data).toLocaleDateString('es-CL');
                        }
                    },
                    {
                        data: 'account',
                        name: 'account',
                        render: function(data, type, row) {
                            const person = row.account?.person;
                            return `<div class="d-flex align-items-center">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        ${person?.name || 'N/A'}
                                    </div>`;
                        }
                    },
                    {
                        data: 'description',
                        name: 'description',
                        render: function(data, type, row) {
                            return '<span class="text-truncate" style="max-width: 200px;" title="' + data + '">' + 
                                   (data.length > 30 ? data.substring(0, 30) + '...' : data) + '</span>';
                        }
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        render: function(data, type, row) {
                            return '<span class="fw-bold text-info">$' + new Intl.NumberFormat('es-CL').format(data) + ' CLP</span>';
                        }
                    },
                    {
                        data: 'items',
                        name: 'items',
                        render: function(data, type, row) {
                            const itemsCount = data ? data.length : 0;
                            return '<span class="badge bg-secondary">' + itemsCount + ' items</span>';
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data, type, row) {
                            return '<span class="badge bg-warning text-dark">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                        }
                    },
                    {
                        data: 'id',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `<div class="btn-group" role="group">
                                        <button type="button" class="btn btn-info btn-sm" onclick="viewExpenseDetails(${data})" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm" onclick="approveExpense(${data})" title="Aprobar">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectExpense(${data})" title="Rechazar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>`;
                        }
                    }
                ],
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
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
                        title: 'Rendiciones Pendientes - COTESO',
                        exportOptions: {
                            columns: ':visible:not(:last-child)' // Solo columnas visibles, excepto Acciones
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-secondary btn-sm',
                        title: 'Rendiciones Pendientes - COTESO',
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
            $('#approvalTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
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

            fetch(`/approvals/transaction/${transactionId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    transactionsTable.row((idx, rowData) => rowData.id === transactionId).remove().draw();
                    updateTransactionBadge();
                } else {
                    showAlert('error', data.message || 'Error al aprobar la transferencia');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Error de conexión al aprobar la transferencia');
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

            fetch(`/approvals/expense/${expenseId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    expensesTable.row((idx, rowData) => rowData.id === expenseId).remove().draw();
                    updateExpenseBadge();
                } else {
                    showAlert('error', data.message || 'Error al aprobar la rendición');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Error de conexión al aprobar la rendición');
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
                showAlert('error', 'Debe ingresar un motivo para el rechazo');
                return;
            }

            const url = currentRejectType === 'transaction' 
                ? `/approvals/transaction/${currentRejectId}/reject`
                : `/approvals/expense/${currentRejectId}/reject`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    reject_reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    
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
                    showAlert('error', data.message || `Error al rechazar la ${currentRejectType === 'transaction' ? 'transferencia' : 'rendición'}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Error de conexión al rechazar la solicitud');
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
                    showAlert('error', 'Error al cargar los detalles de la rendición');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Error de conexión al cargar los detalles');
            });
        }

        /**
         * Muestra alertas en la interfaz
         */
        function showAlert(type, message) {
            const alertContainer = document.getElementById('approval-alerts');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertIcon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fas ${alertIcon} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            alertContainer.innerHTML = alertHtml;
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
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
