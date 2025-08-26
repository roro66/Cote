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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#createTransactionModal">
                            <i class="fas fa-plus"></i> Nueva Transacción
                        </button>
                    </div>

                    <!-- Resumen de cuenta (cuando se filtra por account_id) -->
                    <div id="account-summary" class="mb-3" style="display:none;">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <div class="text-muted small">Cuenta</div>
                                    <div class="fw-bold" id="acc-name">—</div>
                                    <div class="small text-muted" id="acc-owner">—</div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small">Saldo actual</div>
                                    <div class="fs-5 fw-bold" id="acc-balance">$0 CLP</div>
                                    <span class="badge" id="acc-balance-badge">—</span>
                                </div>
                            </div>
                        </div>
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
                                    <th>Saldo</th>
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
                // Leer account_id desde la URL si viene de "Movimientos" en cuentas
                const urlParams = new URLSearchParams(window.location.search);
                const accountId = urlParams.get('account_id');

                let table = $('#transactions-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('datatables.transactions') }}",
                        data: function(d) {
                            if (accountId) {
                                d.account_id = accountId;
                            }
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'transaction_number',
                            name: 'transaction_number'
                        },
                        {
                            data: 'movement_type_badge',
                            name: 'movement_type_badge',
                            orderable: false
                        },
                        {
                            data: 'from_account_name',
                            name: 'from_account_name'
                        },
                        {
                            data: 'to_account_name',
                            name: 'to_account_name'
                        },
                        {
                            data: 'amount_formatted',
                            name: 'amount',
                            className: 'text-end'
                        },
                        {
                            data: 'running_balance_formatted',
                            name: 'running_balance_formatted',
                            className: 'text-end',
                            orderable: false
                        },
                        {
                            data: 'status_spanish',
                            name: 'status_spanish',
                            orderable: false
                        },
                        {
                            data: 'creator_name',
                            name: 'creator_name'
                        },
                        {
                            data: 'created_at_formatted',
                            name: 'transactions.created_at'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
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
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Todos"]
                    ],
                    order: [
                        [9, 'desc']
                    ], // Ordenar por fecha descendente
                    columnDefs: [{
                        targets: [0, 10],
                        orderable: false
                    }],
                    scrollX: true
                });

                // Si hay account_id, mostrar solo el resumen de cuenta (sin modificar el título)
                if (accountId) {
                    // Cargar detalles de la cuenta para mostrar nombre y saldo
                    fetch('/accounts/' + accountId)
                        .then(res => {
                            if (!res.ok) throw new Error('Cuenta no encontrada');
                            return res.json();
                        })
                        .then(acc => {
                            // Mostrar resumen
                            const summary = document.getElementById('account-summary');
                            if (!summary) return;

                            const owner = acc.person ? (acc.person.first_name + ' ' + acc.person.last_name) : (acc
                                .type === 'treasury' ? 'Tesorería General' : 'Sin propietario');
                            const balance = parseFloat(acc.balance || 0);

                            document.getElementById('acc-name').textContent = (acc.name || '—') + ' (ID #' + acc
                                .id + ')';
                            document.getElementById('acc-owner').textContent = owner;
                            document.getElementById('acc-balance').textContent = formatCLP(balance);

                            const badge = document.getElementById('acc-balance-badge');
                            if (balance >= 0) {
                                badge.className = 'badge bg-success';
                                badge.textContent = 'A favor del titular';
                            } else {
                                badge.className = 'badge bg-danger';
                                badge.textContent = 'Adeudado a Tesorería';
                            }

                            summary.style.display = 'block';

                            // No modificar el título; el resumen ya muestra nombre e ID
                        })
                        .catch(() => {
                            /* silencioso */
                        });
                }

                function formatCLP(value) {
                    const n = Number(value || 0);
                    return '$' + new Intl.NumberFormat('es-CL').format(n) + ' CLP';
                }

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
                if (confirm('¿Está seguro de aprobar esta transacción?')) {
                    $.ajax({
                        url: '/approvals/transactions/' + id + '/approve',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $('#transactions-table').DataTable().ajax.reload();
                            toastr.success('Transacción aprobada');
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
                        url: '/approvals/transactions/' + id + '/reject',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            rejection_reason: reason
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

            function clearTransactionForm() {
                $('#createTransactionForm')[0].reset();
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').text('');
            }

            function saveTransaction() {
                // Limpiar errores previos
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                const formData = new FormData($('#createTransactionForm')[0]);

                fetch('/transactions', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => Promise.reject(data));
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            toastr.success('Transacción creada exitosamente');
                            $('#createTransactionModal').modal('hide');
                            clearTransactionForm();
                            $('#transactions-table').DataTable().ajax.reload();
                        } else {
                            toastr.error(data.message || 'Error al crear la transacción');
                        }
                    })
                    .catch(error => {
                        if (error.errors) {
                            // Mostrar errores de validación
                            Object.keys(error.errors).forEach(field => {
                                const input = $(`#create_${field}`);
                                input.addClass('is-invalid');
                                input.siblings('.invalid-feedback').text(error.errors[field][0]);
                            });
                        } else {
                            toastr.error(error.message || 'Error al crear la transacción');
                        }
                    });
            }

            // Document ready para eventos
            $(document).ready(function() {
                // Limpiar formulario al abrir el modal
                $('#createTransactionModal').on('show.bs.modal', function() {
                    clearTransactionForm();
                });

                // Validar que las cuentas origen y destino sean diferentes
                $('#create_from_account_id, #create_to_account_id').change(function() {
                    const fromAccount = $('#create_from_account_id').val();
                    const toAccount = $('#create_to_account_id').val();

                    if (fromAccount && toAccount && fromAccount === toAccount) {
                        $(this).addClass('is-invalid');
                        $(this).siblings('.invalid-feedback').text(
                            'La cuenta de destino debe ser diferente a la de origen');
                    } else {
                        $(this).removeClass('is-invalid');
                        $(this).siblings('.invalid-feedback').text('');
                    }
                });
            });
        </script>
    @endpush

    <!-- Modal para crear transacción -->
    <div class="modal fade" id="createTransactionModal" tabindex="-1" aria-labelledby="createTransactionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTransactionModalLabel">Nueva Transacción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createTransactionForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_type" class="form-label">Tipo de Transacción *</label>
                                    <select class="form-select" id="create_type" name="type" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="transfer">Transferencia</option>

                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_amount" class="form-label">Monto *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="create_amount" name="amount"
                                            step="0.01" min="0.01" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_from_account_id" class="form-label">Cuenta Origen *</label>
                                    <select class="form-select" id="create_from_account_id" name="from_account_id"
                                        required>
                                        <option value="">Seleccionar cuenta origen...</option>
                                        @php
                                            $accounts = \App\Models\Account::where('is_enabled', true)
                                                ->with('person')
                                                ->orderByRaw("CASE WHEN type = 'treasury' THEN 0 ELSE 1 END")
                                                ->orderBy('name')
                                                ->get();
                                            $treasury = $accounts->firstWhere('type', 'treasury');
                                        @endphp
                                        @if ($treasury)
                                            <option value="{{ $treasury->id }}" selected class="bg-warning text-dark">
                                                {{ $treasury->name }} - Tesorería
                                                - ${{ number_format($treasury->balance, 0, ',', '.') }}
                                            </option>
                                        @endif
                                        @foreach ($accounts as $account)
                                            @continue($treasury && $account->id === $treasury->id)
                                            <option value="{{ $account->id }}">
                                                {{ $account->name }}
                                                @if ($account->person)
                                                    ({{ $account->person->first_name }}
                                                    {{ $account->person->last_name }})
                                                @endif
                                                - ${{ number_format($account->balance, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_to_account_id" class="form-label">Cuenta Destino *</label>
                                    <select class="form-select" id="create_to_account_id" name="to_account_id" required>
                                        <option value="">Seleccionar cuenta destino...</option>
                                        @if ($treasury)
                                            <option value="{{ $treasury->id }}" class="bg-warning text-dark">
                                                {{ $treasury->name }} - Tesorería
                                                - ${{ number_format($treasury->balance, 0, ',', '.') }}
                                            </option>
                                        @endif
                                        @foreach ($accounts as $account)
                                            @continue($treasury && $account->id === $treasury->id)
                                            <option value="{{ $account->id }}">
                                                {{ $account->name }}
                                                @if ($account->person)
                                                    ({{ $account->person->first_name }}
                                                    {{ $account->person->last_name }})
                                                @endif
                                                - ${{ number_format($account->balance, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="create_description" class="form-label">Descripción *</label>
                            <textarea class="form-control" id="create_description" name="description" rows="3" maxlength="500" required></textarea>
                            <div class="form-text">Máximo 500 caracteres</div>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="create_notes" class="form-label">Notas</label>
                            <textarea class="form-control" id="create_notes" name="notes" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveTransaction()">Crear
                        Transacción</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
