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

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Personas</h5>
                                    <h2 class="mb-0">{{ $stats['total'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Activas</h5>
                                    <h2 class="mb-0">{{ $stats['active'] }}</h2>
                                    <small>({{ $stats['active_percentage'] }}%)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Tesoreros</h5>
                                    <h2 class="mb-0">{{ $stats['tesoreros'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Trabajadores</h5>
                                    <h2 class="mb-0">{{ $stats['trabajadores'] }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4">Personas Registradas</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#createPersonModal">
                            <i class="fas fa-plus"></i> Nueva Persona
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="people-table" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre Completo</th>
                                    <th>RUT</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Banco</th>
                                    <th>Tipo Cuenta</th>
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

    <!-- Modal para Crear Persona -->
    <div class="modal fade" id="createPersonModal" tabindex="-1" aria-labelledby="createPersonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPersonModalLabel">Nueva Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createPersonForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_first_name" class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" id="create_first_name" name="first_name"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_last_name" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="create_last_name" name="last_name"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_rut" class="form-label">RUT *</label>
                                    <input type="text" class="form-control" id="create_rut" name="rut"
                                        placeholder="12345678-9" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="create_email" name="email"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_phone" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="create_phone" name="phone">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_role_type" class="form-label">Tipo de Rol *</label>
                                    <select class="form-select" id="create_role_type" name="role_type" required>
                                        <option value="">Seleccionar rol</option>
                                        <option value="tesorero">Tesorero</option>
                                        <option value="trabajador">Trabajador</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="create_bank_id" class="form-label">Banco</label>
                                    <select class="form-select" id="create_bank_id" name="bank_id">
                                        <option value="">Seleccionar banco...</option>
                                        @foreach ($banks as $bank)
                                            <option value="{{ $bank->id }}" data-type="{{ $bank->type }}">
                                                {{ $bank->name }}
                                                @if ($bank->type === 'tarjeta_prepago')
                                                    (Prepago)
                                                @endif
                                                @if ($bank->type === 'cooperativa')
                                                    (Cooperativa)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="create_account_type_id" class="form-label">Tipo de Cuenta</label>
                                    <select class="form-select" id="create_account_type_id" name="account_type_id">
                                        <option value="">Seleccionar tipo de cuenta...</option>
                                        @foreach ($accountTypes as $accountType)
                                            <option value="{{ $accountType->id }}"
                                                title="{{ $accountType->description }}">
                                                {{ $accountType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="create_account_number" class="form-label">Número de Cuenta</label>
                                    <input type="text" class="form-control" id="create_account_number"
                                        name="account_number">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="create_address" class="form-label">Dirección</label>
                            <textarea class="form-control" id="create_address" name="address" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="create_is_enabled" name="is_enabled"
                                checked>
                            <label class="form-check-label" for="create_is_enabled">
                                Usuario Activo
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Crear
                        Persona</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver Persona -->
    <div class="modal fade" id="viewPersonModal" tabindex="-1" aria-labelledby="viewPersonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPersonModalLabel">Detalle de Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nombre</dt>
                        <dd class="col-sm-8" id="view_full_name">-</dd>

                        <dt class="col-sm-4">RUT</dt>
                        <dd class="col-sm-8" id="view_rut">-</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8" id="view_email">-</dd>

                        <dt class="col-sm-4">Teléfono</dt>
                        <dd class="col-sm-8" id="view_phone">-</dd>

                        <dt class="col-sm-4">Banco</dt>
                        <dd class="col-sm-8" id="view_bank">-</dd>

                        <dt class="col-sm-4">Tipo Cuenta</dt>
                        <dd class="col-sm-8" id="view_account_type">-</dd>

                        <dt class="col-sm-4">N° Cuenta</dt>
                        <dd class="col-sm-8" id="view_account_number">-</dd>

                        <dt class="col-sm-4">Dirección</dt>
                        <dd class="col-sm-8" id="view_address">-</dd>

                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8" id="view_status">-</dd>
                        <dt class="col-sm-4">Cuentas personales</dt>
                        <dd class="col-sm-8">
                            <ul class="list-unstyled mb-0" id="view_personal_accounts">
                                <li class="text-muted">—</li>
                            </ul>
                        </dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Persona -->
    <div class="modal fade" id="editPersonModal" tabindex="-1" aria-labelledby="editPersonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPersonModalLabel">Editar Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPersonForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_person_id" name="person_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_first_name" class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" id="edit_first_name"
                                        name="first_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_rut" class="form-label">RUT *</label>
                                    <input type="text" class="form-control" id="edit_rut" name="rut"
                                        placeholder="12345678-9" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="edit_email" name="email"
                                        required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_phone" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="edit_phone" name="phone">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_role_type" class="form-label">Tipo de Rol *</label>
                                    <select class="form-select" id="edit_role_type" name="role_type" required>
                                        <option value="">Seleccionar rol</option>
                                        <option value="tesorero">Tesorero</option>
                                        <option value="trabajador">Trabajador</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_bank_id" class="form-label">Banco</label>
                                    <select class="form-select" id="edit_bank_id" name="bank_id">
                                        <option value="">Seleccionar banco...</option>
                                        @foreach ($banks as $bank)
                                            <option value="{{ $bank->id }}" data-type="{{ $bank->type }}">
                                                {{ $bank->name }}
                                                @if ($bank->type === 'tarjeta_prepago')
                                                    (Prepago)
                                                @endif
                                                @if ($bank->type === 'cooperativa')
                                                    (Cooperativa)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_account_type_id" class="form-label">Tipo de Cuenta</label>
                                    <select class="form-select" id="edit_account_type_id" name="account_type_id">
                                        <option value="">Seleccionar tipo de cuenta...</option>
                                        @foreach ($accountTypes as $accountType)
                                            <option value="{{ $accountType->id }}"
                                                title="{{ $accountType->description }}">
                                                {{ $accountType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_account_number" class="form-label">Número de Cuenta</label>
                                    <input type="text" class="form-control" id="edit_account_number"
                                        name="account_number">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Dirección</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_enabled" name="is_enabled">
                            <label class="form-check-label" for="edit_is_enabled">
                                Usuario Activo
                            </label>
                        </div>
                        <hr class="my-4" />
                        <h6 class="mb-2">Cuentas personales para transferencias</h6>
                        <div id="personal_accounts_container" class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <small class="text-muted">Estas cuentas no reemplazan la cuenta interna. Son destinos posibles de transferencia.</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openAddPersonalAccount()">
                                    <i class="fas fa-plus"></i> Agregar cuenta
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped" id="personal_accounts_table">
                                    <thead>
                                        <tr>
                                            <th>Alias</th>
                                            <th>Banco</th>
                                            <th>Tipo</th>
                                            <th>N° Cuenta</th>
                                            <th>Predet.</th>
                                            <th>Activa</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                    <!-- Modal simple para agregar/editar cuenta personal -->
                    <div class="modal fade" id="personalAccountModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="personalAccountModalLabel">Cuenta personal</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="personalAccountForm">
                                        <input type="hidden" name="id" id="pa_id">
                                        <div class="mb-2">
                                            <label class="form-label">Alias</label>
                                            <input type="text" class="form-control" name="alias" id="pa_alias" />
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Banco</label>
                                                <select class="form-select" name="bank_id" id="pa_bank_id">
                                                    <option value="">—</option>
                                                    @foreach ($banks as $bank)
                                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Tipo de Cuenta</label>
                                                <select class="form-select" name="account_type_id" id="pa_account_type_id">
                                                    <option value="">—</option>
                                                    @foreach ($accountTypes as $accountType)
                                                        <option value="{{ $accountType->id }}">{{ $accountType->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Número de Cuenta</label>
                                            <input type="text" class="form-control" name="account_number" id="pa_account_number" />
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="pa_is_default">
                                            <label class="form-check-label" for="pa_is_default">Predeterminada</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="pa_is_active" checked>
                                            <label class="form-check-label" for="pa_is_active">Activa</label>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-primary" onclick="savePersonalAccount()">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Actualizar
                        Persona</button>
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
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'full_name',
                            name: 'full_name',
                            orderable: false
                        },
                        {
                            data: 'rut',
                            name: 'rut'
                        },
                        {
                            data: 'email',
                            name: 'email'
                        },
                        {
                            data: 'phone',
                            name: 'phone'
                        },
                        {
                            data: 'bank_info',
                            name: 'bank_info',
                            title: 'Banco',
                            orderable: false
                        },
                        {
                            data: 'account_info',
                            name: 'account_info',
                            title: 'Tipo Cuenta',
                            orderable: false
                        },
                        {
                            data: 'status',
                            name: 'is_enabled',
                            orderable: false
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
                    pageLength: 10,
                    buttons: [{
                            text: '<i class="fas fa-copy"></i> Copiar',
                            className: 'btn btn-sm btn-secondary',
                            action: function() {
                                exportAllData('copy');
                            }
                        },
                        {
                            text: '<i class="fas fa-file-csv"></i> CSV',
                            className: 'btn btn-sm btn-secondary',
                            action: function() {
                                exportAllData('csv');
                            }
                        },
                        {
                            text: '<i class="fas fa-file-excel"></i> Excel',
                            className: 'btn btn-sm btn-secondary',
                            action: function() {
                                exportAllData('excel');
                            }
                        },
                        {
                            text: '<i class="fas fa-file-pdf"></i> PDF',
                            className: 'btn btn-sm btn-secondary',
                            action: function() {
                                exportAllData('pdf');
                            }
                        },
                        {
                            text: '<i class="fas fa-print"></i> Imprimir',
                            className: 'btn btn-sm btn-secondary',
                            action: function() {
                                exportAllData('print');
                            }
                        },
                        {
                            extend: 'colvis',
                            text: '<i class="fas fa-columns"></i> Columnas',
                            className: 'btn btn-sm btn-secondary'
                        }
                    ],
                    responsive: true,
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
                    // Estilo simple y funcional para todos los elementos
                    $('.dt-buttons').css({
                        'display': 'flex',
                        'gap': '5px',
                        'justify-content': 'flex-start'
                    });

                    $('.dt-search input').addClass('form-control form-control-sm');
                    $('.dt-search input').attr('placeholder', 'Buscar personas...');

                    $('.dt-length label').addClass('d-flex align-items-center gap-2');
                    $('.dt-length select').addClass('form-select form-select-sm');

                    // Asegurar alineación vertical
                    $('.dt-buttons, .dt-search, .dt-length').addClass('d-flex align-items-center');
                }, 100);
            });

            // Función para refrescar las estadísticas sin recargar la tabla
            function refreshStatistics() {
                fetch('{{ route('people.index') }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.statistics) {
                            // Actualizar los números en las tarjetas de estadísticas
                            const stats = data.statistics;

                            // Total personas
                            const totalCard = document.querySelector('.card-body h3');
                            if (totalCard) totalCard.textContent = stats.total;

                            // Activos
                            const activosCards = document.querySelectorAll('.card-body');
                            if (activosCards[1]) {
                                const activosH3 = activosCards[1].querySelector('h3');
                                const activosSmall = activosCards[1].querySelector('small');
                                if (activosH3) activosH3.textContent = stats.activos;
                                if (activosSmall) activosSmall.textContent = `${stats.porcentaje_activos}% del total`;
                            }

                            // Tesoreros
                            if (activosCards[2]) {
                                const tesorerosH3 = activosCards[2].querySelector('h3');
                                const tesorerosSmall = activosCards[2].querySelector('small');
                                if (tesorerosH3) tesorerosH3.textContent = stats.tesoreros;
                                if (tesorerosSmall) tesorerosSmall.textContent = `${stats.porcentaje_tesoreros}% del total`;
                            }

                            // Trabajadores
                            if (activosCards[3]) {
                                const trabajadoresH3 = activosCards[3].querySelector('h3');
                                const trabajadoresSmall = activosCards[3].querySelector('small');
                                if (trabajadoresH3) trabajadoresH3.textContent = stats.trabajadores;
                                if (trabajadoresSmall) trabajadoresSmall.textContent =
                                    `${stats.porcentaje_trabajadores}% del total`;
                            }
                        }
                    })
                    .catch(error => {
                        console.warn('Error al actualizar estadísticas:', error);
                        // No mostrar error al usuario, las estadísticas no son críticas
                    });
            }

            // Función para exportar todos los datos
            function exportAllData(type) {
                // Tomar el término de búsqueda actual de DataTables (para exportar datos filtrados)
                const table = $('#people-table').DataTable();
                const search = table.search();
                const url = new URL('{{ route('people.export') }}', window.location.origin);
                if (search) {
                    url.searchParams.set('search', search);
                }

                fetch(url.toString())
                    .then(response => response.json())
                    .then(result => {
                        // Crear un DataTable temporal con todos los datos
                        const tempTable = $('<table>').DataTable({
                            data: result.data,
                            columns: [{
                                    data: 'DT_RowIndex',
                                    title: '#'
                                },
                                {
                                    data: 'full_name',
                                    title: 'Nombre Completo'
                                },
                                {
                                    data: 'rut',
                                    title: 'RUT'
                                },
                                {
                                    data: 'email',
                                    title: 'Email'
                                },
                                {
                                    data: 'phone',
                                    title: 'Teléfono'
                                },
                                {
                                    data: 'bank_name',
                                    title: 'Banco'
                                },
                                {
                                    data: 'account_type_name',
                                    title: 'Tipo Cuenta'
                                },
                                {
                                    data: 'account_number',
                                    title: 'Número Cuenta'
                                },
                                {
                                    data: 'status',
                                    title: 'Estado'
                                }
                            ],
                            dom: 'Brt',
                            buttons: [{
                                extend: type,
                                filename: 'personas_' + new Date().toISOString().split('T')[0],
                                title: 'Lista de Personas - COTESO'
                            }]
                        });

                        // Trigger the export
                        tempTable.button(0).trigger();

                        // Destruir la tabla temporal
                        tempTable.destroy();
                    })
                    .catch(error => {
                        console.error('Error al exportar:', error);
                        toastr.error('Error al exportar los datos');
                    });
            }

            function viewPerson(id) {
                fetch(`/people/${id}`)
                    .then(res => res.json())
                    .then(p => {
                        document.getElementById('view_full_name').textContent = p.full_name || '-';
                        document.getElementById('view_rut').textContent = p.rut_formatted || p.rut || '-';
                        document.getElementById('view_email').textContent = p.email || '-';
                        document.getElementById('view_phone').textContent = p.phone || '-';
                        document.getElementById('view_bank').textContent = p.bank_name ?
                            `${p.bank_name}${p.bank_type ? ' ('+p.bank_type+')' : ''}` : 'Sin banco';
                        document.getElementById('view_account_type').textContent = p.account_type_name || 'Sin tipo';
                        document.getElementById('view_account_number').textContent = p.account_number || '-';
                        document.getElementById('view_address').textContent = p.address || '-';
                        document.getElementById('view_status').innerHTML = p.is_enabled ?
                            '<span class="badge bg-success">Activo</span>' :
                            '<span class="badge bg-danger">Inactivo</span>';

                        // Render cuentas personales
                        const ul = document.getElementById('view_personal_accounts');
                        ul.innerHTML = '';
                        if (Array.isArray(p.personal_bank_accounts) && p.personal_bank_accounts.length) {
                            p.personal_bank_accounts.forEach(acc => {
                                const li = document.createElement('li');
                                li.textContent = `${acc.alias ? acc.alias + ' — ' : ''}${acc.bank_name || '—'} · ${acc.account_type_name || '—'} · ${acc.account_number || '—'}${acc.is_default ? ' (Predet.)' : ''}`;
                                ul.appendChild(li);
                            });
                        } else {
                            const li = document.createElement('li');
                            li.className = 'text-muted';
                            li.textContent = 'Sin cuentas personales';
                            ul.appendChild(li);
                        }

                        new bootstrap.Modal(document.getElementById('viewPersonModal')).show();
                    })
                    .catch(() => toastr.error('No se pudo cargar la persona'));
            }

            // Funciones globales para las acciones
            function editPerson(id) {
                // Limpiar formulario anterior
                clearValidationErrors('editPersonForm');

                // Obtener datos de la persona
                fetch(`/people/${id}`)
                    .then(response => response.json())
                    .then(person => {
                        // Llenar el formulario con los datos
                        document.getElementById('edit_person_id').value = person.id;
                        document.getElementById('edit_first_name').value = person.first_name;
                        document.getElementById('edit_last_name').value = person.last_name;
                        document.getElementById('edit_rut').value = person.rut;
                        document.getElementById('edit_email').value = person.email;
                        document.getElementById('edit_phone').value = person.phone || '';
                        document.getElementById('edit_role_type').value = person.role_type;
                        document.getElementById('edit_bank_id').value = person.bank_id || '';
                        document.getElementById('edit_account_type_id').value = person.account_type_id || '';
                        document.getElementById('edit_account_number').value = person.account_number || '';
                        document.getElementById('edit_address').value = person.address || '';
                        document.getElementById('edit_is_enabled').checked = person.is_enabled;

                        // Cargar y mostrar cuentas personales
                        window.currentPersonId = person.id;
                        loadPersonalAccounts(person.id);

                        // Mostrar el modal
                        new bootstrap.Modal(document.getElementById('editPersonModal')).show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        toastr.error('Error al cargar los datos de la persona');
                    });
            }

            // Gestión de cuentas personales
            function loadPersonalAccounts(personId) {
                fetch(`/people/${personId}/personal-accounts`)
                    .then(r => r.json())
                    .then(({data}) => {
                        const tbody = document.querySelector('#personal_accounts_table tbody');
                        tbody.innerHTML = '';
                        data.forEach(acc => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${acc.alias ?? ''}</td>
                                <td>${acc.bank?.name ?? ''}</td>
                                <td>${acc.account_type?.name ?? ''}</td>
                                <td>${acc.account_number ?? ''}</td>
                                <td>${acc.is_default ? '<span class="badge bg-primary">Sí</span>' : ''}</td>
                                <td>${acc.is_active ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary me-1" data-action="edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" data-action="delete"><i class="fas fa-trash"></i></button>
                                </td>`;
                            tr.querySelector('button[data-action="edit"]').addEventListener('click', () => openEditPersonalAccount(acc));
                            tr.querySelector('button[data-action="delete"]').addEventListener('click', () => deletePersonalAccount(acc.id));
                            tbody.appendChild(tr);
                        });
                    })
                    .catch(() => toastr.error('No se pudieron cargar las cuentas personales'));
            }

            function openAddPersonalAccount() {
                document.getElementById('pa_id').value = '';
                document.getElementById('personalAccountModalLabel').textContent = 'Agregar cuenta personal';
                document.getElementById('pa_alias').value = '';
                document.getElementById('pa_bank_id').value = '';
                document.getElementById('pa_account_type_id').value = '';
                document.getElementById('pa_account_number').value = '';
                document.getElementById('pa_is_default').checked = false;
                document.getElementById('pa_is_active').checked = true;
                new bootstrap.Modal(document.getElementById('personalAccountModal')).show();
            }

            function openEditPersonalAccount(acc) {
                document.getElementById('pa_id').value = acc.id;
                document.getElementById('personalAccountModalLabel').textContent = 'Editar cuenta personal';
                document.getElementById('pa_alias').value = acc.alias ?? '';
                document.getElementById('pa_bank_id').value = acc.bank_id ?? (acc.bank?.id ?? '');
                document.getElementById('pa_account_type_id').value = acc.account_type_id ?? (acc.account_type?.id ?? '');
                document.getElementById('pa_account_number').value = acc.account_number ?? '';
                document.getElementById('pa_is_default').checked = !!acc.is_default;
                document.getElementById('pa_is_active').checked = acc.is_active !== false;
                new bootstrap.Modal(document.getElementById('personalAccountModal')).show();
            }

            function savePersonalAccount() {
                const personId = window.currentPersonId;
                const id = document.getElementById('pa_id').value;
                const payload = {
                    alias: document.getElementById('pa_alias').value || null,
                    bank_id: document.getElementById('pa_bank_id').value || null,
                    account_type_id: document.getElementById('pa_account_type_id').value || null,
                    account_number: document.getElementById('pa_account_number').value || null,
                    is_default: document.getElementById('pa_is_default').checked,
                    is_active: document.getElementById('pa_is_active').checked,
                };

                const method = id ? 'PUT' : 'POST';
                const url = id ? `/people/${personId}/personal-accounts/${id}` : `/people/${personId}/personal-accounts`;

                fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => {
                    if (!r.ok) return r.json().then(d => { throw {status: r.status, data: d}; });
                    return r.json();
                })
                .then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('personalAccountModal')).hide();
                    loadPersonalAccounts(personId);
                    toastr.success('Cuenta guardada');
                })
                .catch(err => {
                    const msg = err?.data?.message || 'Error al guardar la cuenta';
                    toastr.error(msg);
                });
            }

            function deletePersonalAccount(id) {
                const personId = window.currentPersonId;
                if (!confirm('¿Eliminar esta cuenta personal?')) return;
                fetch(`/people/${personId}/personal-accounts/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                .then(r => {
                    if (!r.ok) return r.json().then(d => { throw {status: r.status, data: d}; });
                    return r.json();
                })
                .then(() => {
                    loadPersonalAccounts(personId);
                    toastr.success('Cuenta eliminada');
                })
                .catch(err => {
                    toastr.error(err?.data?.message || 'Error al eliminar la cuenta');
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

            // Función para enviar formulario de creación
            function submitCreateForm() {
                clearValidationErrors('createPersonForm');

                const form = document.getElementById('createPersonForm');
                const formData = new FormData(form);
                const submitButton = document.querySelector('#createPersonModal .btn-primary');
                const originalText = submitButton.textContent;

                // Pre-process form data for FakeFiller compatibility
                const rutInput = document.getElementById('create_rut');
                if (rutInput && rutInput.value) {
                    // Ensure RUT is properly formatted even if FakeFiller filled it
                    formatRut(rutInput);
                    formData.set('rut', rutInput.value);
                }

                // Ensure checkbox value is properly set
                const isEnabledCheckbox = document.getElementById('create_is_enabled');
                if (isEnabledCheckbox) {
                    formData.set('is_enabled', isEnabledCheckbox.checked ? 'on' : '0');
                }

                // Debug: Log all form data
                console.log('=== DEBUGGING FORM SUBMISSION ===');
                console.log('Form data being sent:');
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: "${value}"`);
                }

                // Disable button and show loading
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando...';

                fetch('/people', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', Object.fromEntries(response.headers.entries()));

                        // Always get response text first, then try to parse as JSON
                        return response.text().then(text => {
                            console.log('Raw response text:', text);

                            let data;
                            try {
                                data = JSON.parse(text);
                            } catch (e) {
                                console.error('Error parsing JSON:', e);
                                throw {
                                    message: 'Error de servidor - respuesta no válida',
                                    response: response,
                                    rawText: text
                                };
                            }

                            if (!response.ok) {
                                throw {
                                    response: response,
                                    data: data
                                };
                            }

                            return data;
                        });
                    })
                    .then(data => {
                        console.log('Success data received:', data);
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('createPersonModal')).hide();
                            document.getElementById('createPersonForm').reset();
                            $('#people-table').DataTable().ajax.reload(null, false);
                            toastr.success(data.message);

                            // Update statistics without reloading table
                            refreshStatistics();
                        } else {
                            console.warn('Success=false received:', data);
                            if (data.errors) {
                                showValidationErrors('createPersonForm', data.errors);
                            } else {
                                toastr.error(data.message || 'Error al crear la persona');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('=== ERROR CAUGHT ===');
                        console.error('Full error object:', error);

                        // Si es un error de validación (422)
                        if (error.response && error.response.status === 422 && error.data && error.data.errors) {
                            console.log('Validation errors detected:', error.data.errors);
                            showValidationErrors('createPersonForm', error.data.errors);
                            toastr.error('Por favor, corrija los errores del formulario');
                        } else if (error.data && error.data.message) {
                            console.error('Server error message:', error.data.message);
                            toastr.error(error.data.message);
                        } else if (error.message) {
                            console.error('Generic error message:', error.message);
                            toastr.error(error.message);
                        } else {
                            console.error('Unknown error - displaying generic message');
                            toastr.error(
                                'Error desconocido al crear la persona. Revise la consola del navegador para más detalles.'
                            );
                        }
                    })
                    .finally(() => {
                        // Re-enable button
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                        console.log('=== FORM SUBMISSION COMPLETED ===');
                    });
            }

            // Función para enviar formulario de edición
            function submitEditForm() {
                clearValidationErrors('editPersonForm');

                const formData = new FormData(document.getElementById('editPersonForm'));
                const personId = document.getElementById('edit_person_id').value;
                formData.append('_method', 'PUT');

                fetch(`/people/${personId}`, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw {
                                    response: response,
                                    data: data
                                };
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('editPersonModal')).hide();
                            $('#people-table').DataTable().ajax.reload(null, false); // false = no reset paging
                            toastr.success(data.message);
                        } else {
                            if (data.errors) {
                                showValidationErrors('editPersonForm', data.errors);
                            } else {
                                toastr.error(data.message || 'Error al actualizar la persona');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        // Si es un error de validación (422)
                        if (error.response && error.response.status === 422 && error.data && error.data.errors) {
                            showValidationErrors('editPersonForm', error.data.errors);
                        } else if (error.data && error.data.message) {
                            toastr.error(error.data.message);
                        } else {
                            toastr.error('Error al actualizar la persona');
                        }
                    });
            }

            // Función para limpiar errores de validación
            function clearValidationErrors(formId) {
                const form = document.getElementById(formId);
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            }

            // Función para mostrar errores de validación
            function showValidationErrors(formId, errors) {
                console.log('Showing validation errors:', errors);
                const form = document.getElementById(formId);

                Object.keys(errors).forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentNode.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = errors[field][0];
                        }
                    } else {
                        console.warn(`Input with name "${field}" not found in form`);
                    }
                });
            }

            // Formateo de RUT para ambos modales (mejorado para FakeFiller)
            function formatRut(input) {
                let value = input.value.replace(/[^0-9kK]/g, ''); // Allow only numbers and K/k

                if (value.length === 0) return;

                // Handle case where K/k is typed
                value = value.replace(/k/gi, 'K');

                if (value.length > 1) {
                    const rut = value.slice(0, -1);
                    const dv = value.slice(-1);

                    // Format with dots and dash
                    let formattedRut = rut.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    input.value = formattedRut + '-' + dv;
                } else {
                    input.value = value;
                }
            }

            // Enhanced event listeners for RUT formatting that work with FakeFiller
            document.addEventListener('DOMContentLoaded', function() {
                // Multiple event types to catch FakeFiller
                const rutEvents = ['input', 'change', 'blur', 'keyup', 'paste'];

                const createRutInput = document.getElementById('create_rut');
                const editRutInput = document.getElementById('edit_rut');

                if (createRutInput) {
                    rutEvents.forEach(eventType => {
                        createRutInput.addEventListener(eventType, function() {
                            // Delay formatting slightly to allow FakeFiller to complete
                            setTimeout(() => formatRut(this), 10);
                        });
                    });
                }

                if (editRutInput) {
                    rutEvents.forEach(eventType => {
                        editRutInput.addEventListener(eventType, function() {
                            setTimeout(() => formatRut(this), 10);
                        });
                    });
                }

                // Limpiar formulario al cerrar modal de creación
                document.getElementById('createPersonModal').addEventListener('hidden.bs.modal', function() {
                    document.getElementById('createPersonForm').reset();
                    clearValidationErrors('createPersonForm');
                });
            });

            // Mostrar mensajes de sesión como tostadas
            @if (session('success'))
                toastr.success('{{ session('success') }}');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}');
            @endif
        </script>
    @endpush
</x-app-layout>
