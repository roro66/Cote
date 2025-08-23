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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPersonModal">
                            <i class="fas fa-plus"></i> Nueva Persona
                        </button>
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

    <!-- Modal para Crear Persona -->
    <div class="modal fade" id="createPersonModal" tabindex="-1" aria-labelledby="createPersonModalLabel" aria-hidden="true">
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
                                    <input type="text" class="form-control" id="create_first_name" name="first_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_last_name" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="create_last_name" name="last_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_rut" class="form-label">RUT *</label>
                                    <input type="text" class="form-control" id="create_rut" name="rut" placeholder="12345678-9" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="create_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="create_email" name="email" required>
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
                                    <label for="create_bank_name" class="form-label">Banco</label>
                                    <input type="text" class="form-control" id="create_bank_name" name="bank_name">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="create_account_type" class="form-label">Tipo de Cuenta</label>
                                    <input type="text" class="form-control" id="create_account_type" name="account_type">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="create_account_number" class="form-label">Número de Cuenta</label>
                                    <input type="text" class="form-control" id="create_account_number" name="account_number">
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
                            <input class="form-check-input" type="checkbox" id="create_is_enabled" name="is_enabled" checked>
                            <label class="form-check-label" for="create_is_enabled">
                                Usuario Activo
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitCreateForm()">Crear Persona</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Persona -->
    <div class="modal fade" id="editPersonModal" tabindex="-1" aria-labelledby="editPersonModalLabel" aria-hidden="true">
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
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_rut" class="form-label">RUT *</label>
                                    <input type="text" class="form-control" id="edit_rut" name="rut" placeholder="12345678-9" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="edit_email" name="email" required>
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
                                    <label for="edit_bank_name" class="form-label">Banco</label>
                                    <input type="text" class="form-control" id="edit_bank_name" name="bank_name">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_account_type" class="form-label">Tipo de Cuenta</label>
                                    <input type="text" class="form-control" id="edit_account_type" name="account_type">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_account_number" class="form-label">Número de Cuenta</label>
                                    <input type="text" class="form-control" id="edit_account_number" name="account_number">
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
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditForm()">Actualizar Persona</button>
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
            dom: '<"row mb-3"<"col-sm-4"B><"col-sm-4"f><"col-sm-4"l>>rtip',
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            pageLength: 10,
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    className: 'btn btn-secondary btn-sm',
                    action: function(e, dt, button, config) {
                        exportAllData('copy');
                    }
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    action: function(e, dt, button, config) {
                        exportAllData('csv');
                    }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    action: function(e, dt, button, config) {
                        exportAllData('excel');
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-secondary btn-sm',
                    filename: 'personas_' + new Date().toISOString().split('T')[0],
                    title: 'Lista de Personas - COTESO',
                    action: function(e, dt, button, config) {
                        exportAllData('pdf');
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Lista de Personas - COTESO',
                    action: function(e, dt, button, config) {
                        exportAllData('print');
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
            $('.dt-buttons, .dt-search, .dt-length').css('min-height', '50px');
        }, 100);
    });

    // Función para refrescar las estadísticas sin recargar la tabla
    function refreshStatistics() {
        fetch('{{ route("people.index") }}', {
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
                    if (trabajadoresSmall) trabajadoresSmall.textContent = `${stats.porcentaje_trabajadores}% del total`;
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
        fetch('{{ route("people.export") }}')
            .then(response => response.json())
            .then(result => {
                // Crear un DataTable temporal con todos los datos
                const tempTable = $('<table>').DataTable({
                    data: result.data,
                    columns: [
                        { data: 'DT_RowIndex', title: '#' },
                        { data: 'full_name', title: 'Nombre Completo' },
                        { data: 'rut', title: 'RUT' },
                        { data: 'email', title: 'Email' },
                        { data: 'phone', title: 'Teléfono' },
                        { data: 'status', title: 'Estado' }
                    ],
                    dom: 'Brt',
                    buttons: [
                        {
                            extend: type,
                            filename: 'personas_' + new Date().toISOString().split('T')[0],
                            title: 'Lista de Personas - COTESO'
                        }
                    ]
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
                document.getElementById('edit_bank_name').value = person.bank_name || '';
                document.getElementById('edit_account_type').value = person.account_type || '';
                document.getElementById('edit_account_number').value = person.account_number || '';
                document.getElementById('edit_address').value = person.address || '';
                document.getElementById('edit_is_enabled').checked = person.is_enabled;
                
                // Mostrar el modal
                new bootstrap.Modal(document.getElementById('editPersonModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Error al cargar los datos de la persona');
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
                    throw { response: response, data: data };
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
                toastr.error('Error desconocido al crear la persona. Revise la consola del navegador para más detalles.');
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
                    throw { response: response, data: data };
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
