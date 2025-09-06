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

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Cuentas</h5>
                                    <h2 class="mb-0">{{ $stats['total'] ?? 0 }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Activas</h5>
                                    <h2 class="mb-0">{{ $stats['enabled'] ?? 0 }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Cuentas con saldo ≠ 0</h5>
                                    <h2 class="mb-0">{{ $stats['nonzero'] ?? 0 }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Saldo</h5>
                                    <h2 class="mb-0">${{ number_format($stats['total_balance'] ?? 0, 0, ',', '.') }} CLP</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Cuentas Registradas</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#createAccountModal">
                            <i class="fas fa-plus"></i> Nueva Cuenta
                        </button>
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

    <!-- Modal para Ver Cuenta -->
    <div class="modal fade" id="viewAccountModal" tabindex="-1" aria-labelledby="viewAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAccountModalLabel">Detalle de Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre</dt>
                        <dd class="col-sm-8" id="acc_name">-</dd>

                        <dt class="col-sm-4">Tipo</dt>
                        <dd class="col-sm-8" id="acc_type">-</dd>

                        <dt class="col-sm-4">Propietario</dt>
                        <dd class="col-sm-8" id="acc_owner">-</dd>

                        <dt class="col-sm-4">Saldo</dt>
                        <dd class="col-sm-8" id="acc_balance">-</dd>

                        <dt class="col-sm-4">Notas</dt>
                        <dd class="col-sm-8" id="acc_notes">-</dd>

                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8" id="acc_status">-</dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'type_spanish',
                            name: 'type_spanish'
                        },
                        {
                            data: 'owner',
                            name: 'owner'
                        },
                        {
                            data: 'balance_formatted',
                            name: 'balance_formatted',
                            className: 'text-end'
                        },
                        {
                            data: 'status',
                            name: 'status',
                            orderable: true
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
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Todos"]
                    ],
                    order: [
                        [1, 'asc']
                    ],
                    columnDefs: [{
                        targets: [0, 6],
                        orderable: false
                    }]
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

            function viewAccount(id) {
                fetch('/accounts/' + id)
                    .then(res => res.json())
                    .then(acc => {
                        document.getElementById('acc_name').textContent = acc.name || '-';
                        document.getElementById('acc_type').textContent = acc.type === 'treasury' ? 'Tesorería' :
                        'Personal';
                        document.getElementById('acc_owner').textContent = acc.person ? (acc.person.first_name + ' ' + acc
                            .person.last_name) : (acc.type === 'treasury' ? 'Tesorería General' : 'Sin propietario');
                        document.getElementById('acc_balance').textContent = new Intl.NumberFormat('es-CL', {
                            style: 'currency',
                            currency: 'CLP',
                            maximumFractionDigits: 0
                        }).format(acc.balance || 0);
                        document.getElementById('acc_notes').textContent = acc.notes || '-';
                        document.getElementById('acc_status').innerHTML = acc.is_enabled ?
                            '<span class="badge bg-success">Activa</span>' :
                            '<span class="badge bg-danger">Inactiva</span>';

                        new bootstrap.Modal(document.getElementById('viewAccountModal')).show();
                    })
                    .catch(() => toastr.error('No se pudo cargar la cuenta'));
            }

            function viewTransactions(id) {
                window.location.href = '/transactions?account_id=' + id;
            }

            // Mostrar/ocultar campo persona según el tipo de cuenta
            $(document).ready(function() {
                $('#create_type').change(function() {
                    if ($(this).val() === 'person') {
                        $('#create-person-field').show();
                        $('#create_person_id').attr('required', true);
                    } else {
                        $('#create-person-field').hide();
                        $('#create_person_id').attr('required', false);
                        $('#create_person_id').val('');
                    }
                });
            });

            function deleteAccount(id) {
                if (confirm('¿Está seguro de que desea eliminar esta cuenta?')) {
                    $.ajax({
                        url: '/accounts/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#accounts-table').DataTable().ajax.reload();
                                toastr.success(response.message || 'Cuenta eliminada exitosamente');
                            } else {
                                toastr.error(response.message || 'Error al eliminar la cuenta');
                            }
                        },
                        error: function(xhr) {
                            let message = 'Error al eliminar la cuenta';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                            toastr.error(message);
                        }
                    });
                }
            }

            function clearForm() {
                $('#createAccountForm')[0].reset();
                $('#create-person-field').hide();
                $('#create_person_id').attr('required', false);
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').text('');
            }

            function saveAccount() {
                // Limpiar errores previos
                $('.form-control, .form-select').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                const formData = new FormData($('#createAccountForm')[0]);

                fetch('/accounts', {
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
                            toastr.success('Cuenta creada exitosamente');
                            $('#createAccountModal').modal('hide');
                            clearForm();
                            $('#accounts-table').DataTable().ajax.reload();
                        } else {
                            toastr.error(data.message || 'Error al crear la cuenta');
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
                            toastr.error(error.message || 'Error al crear la cuenta');
                        }
                    });
            }

            // Limpiar formulario al abrir el modal
            $('#createAccountModal').on('show.bs.modal', function() {
                clearForm();
            });
        </script>
    @endpush

    <!-- Modal para crear/editar cuenta -->
    <div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAccountModalLabel">Nueva Cuenta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createAccountForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_name" class="form-label">Nombre de la Cuenta *</label>
                                    <input type="text" class="form-control" id="create_name" name="name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_type" class="form-label">Tipo de Cuenta *</label>
                                    <select class="form-select" id="create_type" name="type" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="treasury">Tesorería</option>
                                        <option value="person">Personal</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6" id="create-person-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="create_person_id" class="form-label">Persona Propietaria</label>
                                    <select class="form-select" id="create_person_id" name="person_id">
                                        <option value="">Seleccionar persona...</option>
                                        @php
                                            $people = \App\Models\Person::where('is_enabled', true)->get();
                                        @endphp
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}">
                                                {{ $person->first_name }} {{ $person->last_name }} - {{ $person->rut }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_balance" class="form-label">Saldo Inicial *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="create_balance"
                                            name="balance" value="0" step="0.01" min="0" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="create_notes" class="form-label">Notas</label>
                            <textarea class="form-control" id="create_notes" name="notes" rows="3"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="hidden" name="is_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="create_is_enabled" name="is_enabled"
                                value="1" checked>
                            <label class="form-check-label" for="create_is_enabled">
                                Cuenta habilitada
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveAccount()">Crear Cuenta</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
