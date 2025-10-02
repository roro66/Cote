<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Rendición #{{ $expense->expense_number }}
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
                            <form id="editExpenseForm" action="{{ route('expenses.update', $expense) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <!-- Cuenta -->
                                <div class="mb-3">
                                    <label for="account_id" class="form-label">
                                        Cuenta <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="account_id" name="account_id" required>
                                        <option value="">Seleccionar cuenta...</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ $expense->account_id == $account->id ? 'selected' : '' }}>
                                                {{ $account->name }} - {{ $account->person->first_name ?? 'Sin asignar' }} {{ $account->person->last_name ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Descripción -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        Descripción General <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required 
                                              placeholder="Descripción general de la rendición">{{ $expense->description }}</textarea>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Referencia -->
                                <div class="mb-3">
                                    <label for="reference" class="form-label">Referencia</label>
                                    <input type="text" class="form-control" id="reference" name="reference" 
                                           value="{{ $expense->reference }}" placeholder="Número de referencia o código interno">
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
                                        <!-- Items existentes se cargarán aquí -->
                                    </div>
                                    
                                    <!-- Botón para agregar items - después de los items existentes -->
                                    <div class="text-center mb-3">
                                        <button type="button" class="btn btn-success" onclick="addExpenseItem()">
                                            <i class="fas fa-plus me-1"></i>Agregar Item
                                        </button>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <strong>Total:</strong> <span id="totalAmount">{{ number_format($expense->total_amount, 2) }}</span> <span id="totalCurrency">CLP</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Actualizar Rendición
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
                        <input type="number" class="form-control item-amount" name="items[][amount]" 
                               step="0.01" min="0.01" placeholder="0.00" required onchange="calculateTotal()">
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
            </div>
        </div>
    </template>

    <script>
    let itemCounter = 0;

    // Mostrar mensajes toastr desde la sesión al cargar la página
    @if(session('toastr'))
        @php $toastr = session('toastr'); @endphp
        toastr.{{ $toastr['type'] }}('{{ $toastr['message'] }}');
    @endif

    function addExpenseItem() {
        const template = document.getElementById('expenseItemTemplate');
        const clone = template.content.cloneNode(true);
        
        // Update name attributes to include index
        clone.querySelectorAll('input, select').forEach(input => {
            if (input.name && input.name.includes('[]')) {
                input.name = input.name.replace('[]', `[${itemCounter}]`);
            }
        });
        
        document.getElementById('expenseItems').appendChild(clone);
        itemCounter++;
        calculateTotal();
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
    }

    // Cargar items existentes
    document.addEventListener('DOMContentLoaded', function() {
        @foreach($expense->items as $item)
        addExpenseItem();
        const lastItem = document.querySelector('.expense-item:last-child');
        lastItem.querySelector('.item-description').value = '{{ $item->description }}';
        lastItem.querySelector('.item-amount').value = '{{ $item->amount }}';
        lastItem.querySelector('select[name*="document_type"]').value = '{{ $item->document_type }}';
        lastItem.querySelector('input[name*="vendor_name"]').value = '{{ $item->vendor_name }}';
        lastItem.querySelector('input[name*="receipt_number"]').value = '{{ $item->document_number ?? '' }}';
        @endforeach
        
        // Load categories and populate selects
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
                    // populate each select and set selected when applicable
                    document.querySelectorAll('.expense-item').forEach((itemEl, idx) => {
                        const sel = itemEl.querySelector('.item-category-select');
                        if (sel) {
                            // populate
                            sel.innerHTML = '<option value="">-- Sin categorizar --</option>';
                            window.COTESO_CATEGORIES.forEach(c => {
                                const opt = document.createElement('option');
                                opt.value = c.id;
                                opt.textContent = c.name;
                                sel.appendChild(opt);
                            });
                            // set previously stored category if present from server
                            const serverCat = @json($expense->items->pluck('expense_category_id'));
                            const val = serverCat[idx] || '';
                            sel.value = val;
                        }
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

        calculateTotal();
        
        // Form submission
        document.getElementById('editExpenseForm').addEventListener('submit', function(e) {
            if (document.querySelectorAll('.expense-item').length === 0) {
                e.preventDefault();
                toastr.error('Debe agregar al menos un item de gasto');
                return false;
            }

            // Chequeo rápido de duplicados en cliente: (tipo, proveedor, número)
            const combos = new Map();
            let hasDup = false;
            document.querySelectorAll('.expense-item').forEach((itemEl) => {
                const type = itemEl.querySelector('select[name^="items"][name$="[document_type]"]').value;
                const vendor = (itemEl.querySelector('input[name^="items"][name$="[vendor_name]"]').value || '')
                    .trim().toLowerCase().replace(/\s+/g, ' ');
                const number = (itemEl.querySelector('input[name^="items"][name$="[receipt_number]"]').value || '')
                    .trim().toLowerCase();
                if (!number) return; // sólo chequeamos cuando hay número
                const key = `${type}|${vendor}|${number}`;
                if (combos.has(key)) {
                    hasDup = true;
                }
                combos.set(key, true);
            });

            if (hasDup) {
                e.preventDefault();
                toastr.error('Hay documentos duplicados dentro de la rendición (tipo + proveedor + número).');
                return false;
            }

            // Envío normal del form
        });
    });
    </script>
</x-app-layout>
