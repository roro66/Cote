<x-app-layout>
    <style>
        /* Asegura que el input de archivo quede por encima de overlays fijos */
        .file-input-zone { position: relative; z-index: 5000; }
    /* Contenedor para botón de adjuntar y nombres seleccionados */
    .file-upload { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    </style>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nueva Rendición
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Volver
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form id="createExpenseForm" enctype="multipart/form-data">
                                @csrf

                                <!-- Cuenta -->
                                <div class="mb-3">
                                    <label for="account_id" class="form-label">
                                        Cuenta <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="account_id" name="account_id" required>
                                        <option value="">Seleccionar cuenta...</option>
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->name }} -
                                                {{ $account->person->first_name ?? 'Sin asignar' }}
                                                {{ $account->person->last_name ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Resumen de Cuenta Seleccionada -->
                                <div id="accountSummary" class="row g-3 mb-4" style="display:none;">
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="text-muted small mb-1">Propietario</div>
                                                <div id="accountOwner" class="fw-semibold">—</div>
                                                <div id="accountType" class="text-muted small">—</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="text-muted small mb-1">Saldo actual</div>
                                                <div id="currentBalance" class="fs-5 fw-bold">$0 CLP</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <div class="text-muted small">Saldo proyectado</div>
                                                    <span class="badge bg-info"
                                                        title="Saldo tras aprobar esta rendición">Proyección</span>
                                                </div>
                                                <div id="projectedBalance" class="fs-5 fw-bold">$0 CLP</div>
                                                <div id="projectedHint" class="small mt-1 text-muted"
                                                    style="display:none">Quedará en negativo; Tesorería debe
                                                    regularizar.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        Descripción General <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required
                                        placeholder="Descripción general de la rendición"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Referencia -->
                                <div class="mb-3">
                                    <label for="reference" class="form-label">Referencia</label>
                                    <input type="text" class="form-control" id="reference" name="reference"
                                        placeholder="Número de referencia o código interno">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Campo oculto para moneda -->
                                <input type="hidden" id="currency" name="currency" value="CLP">

                                <!-- Items de Gasto -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Items de Gasto</h5>
                                    </div>

                                    <div id="expenseItems">
                                        <!-- Items will be added dynamically -->
                                    </div>

                                    <!-- Botón para agregar items - después de los items existentes -->
                                    <div class="text-center mb-3">
                                        <button type="button" class="btn btn-success" onclick="addExpenseItem()">
                                            <i class="fas fa-plus me-1"></i>Agregar Item
                                        </button>
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <strong>Total:</strong> <span id="totalAmount">0.00</span> <span
                                            id="totalCurrency">CLP</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Crear Rendición
                                    </button>
                                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Cancelar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template for expense items -->
    <template id="expenseItemTemplate">
        <div class="expense-item card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="card-title mb-0">Item de Gasto</h6>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeExpenseItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                        <input type="text" class="form-control item-description" name="items[][description]"
                            placeholder="Descripción del gasto" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Monto (CLP) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control item-amount" name="items[][amount]" step="0.01"
                            min="0.01" placeholder="0.00" required onchange="calculateTotal()">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Documento <span class="text-danger">*</span></label>
                        <select class="form-select" name="items[][document_type]" required>
                            <option value="ticket">Ticket</option>
                            <option value="boleta">Boleta</option>
                            <option value="factura">Factura</option>
                            <option value="guia_despacho">Guía de Despacho</option>
                            <option value="vale">Vale</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <!-- Campo oculto para moneda del item -->
                <input type="hidden" class="item-currency" name="items[][currency]" value="CLP">

                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Proveedor <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="items[][vendor_name]"
                            placeholder="Nombre del proveedor" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Categoría</label>
                        <select class="form-select item-category-select" name="items[][expense_category_id]">
                            <option value="">-- Sin categorizar --</option>
                        </select>
                        <div class="form-text">Seleccione la categoría de gasto (peaje, alimentación, insumos...)</div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Número de Recibo</label>
                        <input type="text" class="form-control" name="items[][receipt_number]"
                            placeholder="Número de recibo o factura">
                    </div>
                </div>
                <div class="row mt-2 file-input-zone">
                    <div class="col-md-12">
                        <label class="form-label">Adjuntos (fotos / PDF)</label>
                        <div class="file-upload">
                            <label class="btn btn-outline-primary btn-sm mb-0" for="item_files__INDEX__">
                                <i class="fas fa-paperclip me-1"></i> Elegir archivos
                            </label>
                            <span class="small text-muted selected-files">Ningún archivo seleccionado</span>
                        </div>
                        <input type="file" class="visually-hidden item-files" id="item_files__INDEX__" name="items[][files][]" multiple accept="image/*,application/pdf">
                        <div class="form-text">Puedes subir varias imágenes de tickets, boletas, facturas, etc. Se conservará el nombre original del archivo.</div>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script>
        let itemCounter = 0;
        let selectedAccount = null;
        let currentBalanceValue = 0;

        function formatCLP(value) {
            const n = Number(value || 0);
            return '$' + new Intl.NumberFormat('es-CL').format(n) + ' CLP';
        }

        function updateProjectedBalance() {
            const total = parseFloat(document.getElementById('totalAmount').textContent) || 0;
            const projected = currentBalanceValue - total;
            const projectedEl = document.getElementById('projectedBalance');
            projectedEl.textContent = formatCLP(projected);
            projectedEl.classList.toggle('text-danger', projected < 0);
            projectedEl.classList.toggle('text-success', projected >= 0);
            const hint = document.getElementById('projectedHint');
            hint.style.display = projected < 0 ? 'block' : 'none';
        }

        function addExpenseItem() {
            const template = document.getElementById('expenseItemTemplate');
            const clone = template.content.cloneNode(true);

            // Update name attributes to include index
            clone.querySelectorAll('input, select').forEach(input => {
                if (input.name && input.name.includes('[]')) {
                    input.name = input.name.replace('[]', `[${itemCounter}]`);
                }
            });

            // Ensure unique id for file input and label-for association
            const fileInput = clone.querySelector('.item-files');
            if (fileInput) {
                const newId = `item_files_${itemCounter}`;
                fileInput.id = newId;
                // Sync the custom button label 'for' attribute with the generated id
                const triggerLabel = clone.querySelector('label[for="item_files__INDEX__"]');
                if (triggerLabel) triggerLabel.setAttribute('for', newId);
            }

            document.getElementById('expenseItems').appendChild(clone);
                // After appending, populate category select for the newly added item
                const parent = document.getElementById('expenseItems');
                const lastItem = parent.lastElementChild;
                if (lastItem) {
                    const newSelect = lastItem.querySelector('.item-category-select');
                    if (newSelect) {
                        populateCategorySelect(newSelect);
                    }
                }
                itemCounter++;
                calculateTotal();
        }

            function populateCategorySelect(selectEl) {
                const cats = window.COTESO_CATEGORIES || [];
                selectEl.innerHTML = '<option value="">-- Sin categorizar --</option>';
                cats.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    selectEl.appendChild(opt);
                });
            }

        function removeExpenseItem(button) {
            if (document.querySelectorAll('.expense-item').length > 1) {
                button.closest('.expense-item').remove();
                calculateTotal();
            } else {
                toastr.warning('Debe mantener al menos un item de gasto');
            }
        }

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.item-amount').forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            document.getElementById('totalAmount').textContent = total.toFixed(2);
            document.getElementById('totalCurrency').textContent = document.getElementById('currency').value;
            updateProjectedBalance();
        }

        // Update total currency when main currency changes
        document.getElementById('currency').addEventListener('change', function() {
            calculateTotal();
        });

        // Manejar selección de cuenta
        document.getElementById('account_id').addEventListener('change', function() {
            const accountId = this.value;
            const summary = document.getElementById('accountSummary');
            if (!accountId) {
                selectedAccount = null;
                currentBalanceValue = 0;
                summary.style.display = 'none';
                updateProjectedBalance();
                return;
            }

            fetch(`/accounts/${accountId}`)
                .then(res => res.json())
                .then(acc => {
                    selectedAccount = acc;
                    currentBalanceValue = parseFloat(acc.balance) || 0;
                    document.getElementById('accountOwner').textContent = acc.person ?
                        `${acc.person.first_name} ${acc.person.last_name}` : '—';
                    document.getElementById('accountType').textContent = acc.type === 'treasury' ? 'Tesorería' :
                        'Personal';
                    const currentEl = document.getElementById('currentBalance');
                    currentEl.textContent = formatCLP(currentBalanceValue);
                    currentEl.classList.toggle('text-danger', currentBalanceValue < 0);
                    currentEl.classList.toggle('text-success', currentBalanceValue >= 0);
                    summary.style.display = 'flex';
                    updateProjectedBalance();
                })
                .catch(() => {
                    selectedAccount = null;
                    currentBalanceValue = 0;
                    summary.style.display = 'none';
                    updateProjectedBalance();
                });
        });

        // Add initial item
        document.addEventListener('DOMContentLoaded', function() {
            addExpenseItem();

            // Load categories and store locally
            window.COTESO_CATEGORIES = [];
            fetch('/datatables/expense-categories', {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
                .then(r => {
                    if (r.status === 401) {
                        toastr.warning('Necesitas iniciar sesión para cargar categorías. Por favor inicia sesión o recarga la página.');
                        // populate selects with a disabled placeholder
                        document.querySelectorAll('.item-category-select').forEach(sel => {
                            sel.innerHTML = '<option value="">-- Inicie sesión para ver categorías --</option>';
                            sel.disabled = true;
                        });
                        throw new Error('Unauthenticated');
                    }
                    return r.json();
                })
                .then(data => {
                    if (Array.isArray(data.data) && data.data.length > 0) {
                        window.COTESO_CATEGORIES = data.data;
                        // populate existing selects
                        document.querySelectorAll('.item-category-select').forEach(sel => {
                            populateCategorySelect(sel);
                        });
                    } else {
                        // No categories available
                        document.querySelectorAll('.item-category-select').forEach(sel => {
                            sel.innerHTML = '<option value="">-- No hay categorías disponibles --</option>';
                        });
                    }
                }).catch(err => {
                    if (err.message !== 'Unauthenticated') console.error('Error loading categories:', err);
                });

            // Form submission
            document.getElementById('createExpenseForm').addEventListener('submit', function(e) {
                e.preventDefault();

                if (document.querySelectorAll('.expense-item').length === 0) {
                    toastr.error('Debe agregar al menos un item de gasto');
                    return;
                }

                // Quick client-side duplicate detection by (tipo, proveedor, número)
                const combos = new Map();
                let hasDup = false;
                document.querySelectorAll('.expense-item').forEach((itemEl, idx) => {
                    const type = itemEl.querySelector('select[name^="items"][name$="[document_type]"]').value;
                    const vendor = (itemEl.querySelector('input[name^="items"][name$="[vendor_name]"]').value || '')
                        .trim().toLowerCase().replace(/\s+/g, ' ');
                    const number = (itemEl.querySelector('input[name^="items"][name$="[receipt_number]"]').value || '')
                        .trim().toLowerCase();
                    if (!number) return; // Only check when number present
                    const key = `${type}|${vendor}|${number}`;
                    if (combos.has(key)) {
                        hasDup = true;
                    }
                    combos.set(key, true);
                });

                if (hasDup) {
                    toastr.error('Hay documentos duplicados dentro de la rendición (tipo + proveedor + número).');
                    return;
                }

                const formEl = document.getElementById('createExpenseForm');
                const formData = new FormData(formEl);

                fetch('{{ route('expenses.store') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);

                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            return response.text().then(text => {
                                console.error('Non-JSON response:', text);
                                throw new Error('Response is not JSON');
                            });
                        }

                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message);
                            window.location.href = '{{ route('expenses.index') }}';
                        } else {
                            toastr.error(data.message);

                            // Handle validation errors
                            if (data.errors) {
                                Object.keys(data.errors).forEach(field => {
                                    const input = document.querySelector(`[name="${field}"]`);
                                    if (input) {
                                        input.classList.add('is-invalid');
                                        const feedback = input.parentNode.querySelector(
                                            '.invalid-feedback');
                                        if (feedback) {
                                            feedback.textContent = data.errors[field][0];
                                        }
                                    }
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error creando rendición:', error);
                        toastr.error('Error al crear la rendición: ' + (error?.message ||
                            'Desconocido'));
                    });
            });
        });

        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('item-files')) {
                const files = Array.from(e.target.files || []);
                const names = files.length ? files.map(f => f.name).join(', ') : 'Ningún archivo seleccionado';
                const info = e.target.closest('.card-body')?.querySelector('.selected-files');
                if (info) info.textContent = names;
            }
        });

        // Evitar que algún listener global bloquee el click al input file
        document.addEventListener('click', function(e) {
            const isFileLabel = e.target.closest && e.target.closest('.file-input-zone');
            if (isFileLabel) {
                // No cancelar el evento, solo marcar explícitamente que es un gesto de usuario
                // Algunos plugins pueden hacer preventDefault en clicks genéricos; aseguramos burbujeo limpio
            }
        }, { capture: true });

    // no-op
    </script>
</x-app-layout>
