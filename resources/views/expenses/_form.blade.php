{{-- Formulario de rendición compartido por crear y editar. Variables: $accounts, $expense (opcional) --}}
@php
    $expense = $expense ?? null;
    $itemsData = $expense
        ? $expense->items->map(fn ($item) => [
            'id' => $item->id,
            'expense_date' => optional($item->expense_date)->format('Y-m-d'),
            'description' => $item->description,
            'amount' => (float) $item->amount,
            'document_type' => $item->document_type,
            'vendor_name' => $item->vendor_name,
            'receipt_number' => $item->document_number,
            'expense_category_id' => $item->expense_category_id,
            'without_receipt' => (bool) $item->without_receipt,
            'without_receipt_reason' => $item->without_receipt_reason,
            'documents' => $item->documents->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->name,
                'url' => $doc->url,
                'is_image' => str_starts_with((string) $doc->mime_type, 'image/'),
            ])->values(),
        ])->values()
        : [];
@endphp

@if ($expense && $expense->status === 'rejected')
    <div class="alert alert-warning">
        <strong>Rendición rechazada:</strong> {{ $expense->rejection_reason }}
        <div class="small mt-1">Al guardar los cambios se enviará nuevamente para aprobación.</div>
    </div>
@endif

<form id="expenseForm" action="{{ $expense ? route('expenses.update', $expense) : route('expenses.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($expense)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="account_id" class="form-label">Cuenta <span class="text-danger">*</span></label>
        <select class="form-select" id="account_id" name="account_id" required>
            <option value="">Seleccionar cuenta...</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected($expense?->account_id === $account->id || $accounts->count() === 1)>
                    {{ $account->person?->full_name ?? $account->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>

    <div id="accountSummary" class="row g-3 mb-4" style="display:none;">
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small mb-1">Saldo actual</div>
                <div id="currentBalance" class="fs-5 fw-bold">$0</div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small mb-1">Saldo si se aprueba esta rendición</div>
                <div id="projectedBalance" class="fs-5 fw-bold">$0</div>
                <div id="projectedHint" class="small mt-1 text-muted" style="display:none">Quedará en negativo: la empresa le deberá esa diferencia.</div>
            </div></div>
        </div>
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Descripción general <span class="text-danger">*</span></label>
        <textarea class="form-control" id="description" name="description" rows="2" required maxlength="500"
                  placeholder="Ej.: Gastos semana del 14 al 18 de octubre">{{ $expense?->description }}</textarea>
        <div class="invalid-feedback"></div>
    </div>

    <h5 class="mb-3">Gastos</h5>
    <div id="expenseItems"></div>

    <div class="text-center mb-3">
        <button type="button" class="btn btn-success" onclick="addExpenseItem()">
            <i class="fas fa-plus me-1"></i>Agregar gasto
        </button>
    </div>

    <div class="alert alert-info">
        <strong>Total:</strong> <span id="totalAmount">$0</span>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" id="expenseSubmit">
            <i class="fas fa-save me-1"></i>{{ $expense ? ($expense->status === 'rejected' ? 'Guardar y reenviar' : 'Guardar cambios') : 'Crear rendición' }}
        </button>
        <a href="{{ $expense ? route('expenses.show', $expense) : route('expenses.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<template id="expenseItemTemplate">
    <div class="expense-item card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="card-title mb-0">Gasto</h6>
                <button type="button" class="btn btn-outline-danger btn-sm" data-remove-item title="Quitar gasto" aria-label="Quitar gasto">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <input type="hidden" data-field="id">

            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Fecha del comprobante <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" data-field="expense_date" required>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Descripción <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" data-field="description" placeholder="Ej.: Almuerzo cuadrilla" required maxlength="255">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Monto (CLP) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control item-amount" data-field="amount" step="1" min="1" placeholder="0" required>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <div class="row g-2 mt-1">
                <div class="col-md-3">
                    <label class="form-label">Tipo de documento <span class="text-danger">*</span></label>
                    <select class="form-select" data-field="document_type" required>
                        <option value="boleta">Boleta</option>
                        <option value="factura">Factura</option>
                        <option value="ticket">Ticket</option>
                        <option value="guia_despacho">Guía de despacho</option>
                        <option value="vale">Vale</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Proveedor <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" data-field="vendor_name" placeholder="Nombre del comercio" required maxlength="255">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">N° de documento</label>
                    <input type="text" class="form-control" data-field="receipt_number" maxlength="100">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoría</label>
                    <select class="form-select item-category-select" data-field="expense_category_id">
                        <option value="">-- Sin categorizar --</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label mb-1">Comprobantes (fotos o PDF)</label>
                <div class="existing-documents d-flex flex-wrap gap-2 mb-2"></div>
                <div class="d-flex align-items-center gap-2">
                    <label class="btn btn-outline-primary btn-sm mb-0 file-trigger">
                        <i class="fas fa-paperclip me-1"></i>Agregar archivos
                        <input type="file" class="d-none item-files" data-field="files" multiple accept="image/*,application/pdf">
                    </label>
                    <span class="small text-muted selected-files">Ningún archivo nuevo</span>
                </div>
                <div class="invalid-feedback d-block files-feedback"></div>

                <label class="form-check mt-2 small">
                    <input type="checkbox" class="form-check-input without-receipt" data-field="without_receipt" value="1">
                    Sin comprobante (excepcional)
                </label>
                <div class="without-receipt-reason mt-1" style="display:none">
                    <input type="text" class="form-control form-control-sm" data-field="without_receipt_reason" maxlength="255"
                           placeholder="Motivo: ej. colación en local sin boleta">
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
(function () {
    const EXISTING_ITEMS = @json($itemsData);
    const TODAY = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    let itemCounter = 0;
    let categories = [];
    let currentBalance = 0;

    const clp = n => '$' + new Intl.NumberFormat('es-CL').format(Math.round(Number(n) || 0));

    function fieldName(index, field) {
        return field === 'files' ? `items[${index}][files][]` : `items[${index}][${field}]`;
    }

    function fillCategories(select, selected) {
        select.innerHTML = '<option value="">-- Sin categorizar --</option>';
        categories.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            select.appendChild(opt);
        });
        if (selected) select.value = String(selected);
    }

    function renderDocuments(container, index, documents) {
        (documents || []).forEach(doc => {
            const chip = document.createElement('label');
            chip.className = 'border rounded px-2 py-1 d-flex align-items-center gap-2 small existing-doc';

            const link = document.createElement('a');
            link.href = doc.url;
            link.target = '_blank';
            link.rel = 'noopener';
            if (doc.is_image) {
                const img = document.createElement('img');
                img.src = doc.url;
                img.alt = doc.name;
                img.style.cssText = 'height:36px;width:auto;border-radius:4px;object-fit:cover';
                link.appendChild(img);
            } else {
                link.innerHTML = '<i class="fas fa-file-pdf me-1"></i>';
                link.appendChild(document.createTextNode(doc.name.length > 24 ? doc.name.slice(0, 21) + '…' : doc.name));
            }

            const remove = document.createElement('input');
            remove.type = 'checkbox';
            remove.className = 'form-check-input m-0';
            remove.name = `items[${index}][remove_documents][]`;
            remove.value = doc.id;
            remove.title = 'Quitar este comprobante al guardar';
            remove.addEventListener('change', () => chip.classList.toggle('text-decoration-line-through', remove.checked));

            chip.append(link, remove, document.createTextNode('Quitar'));
            container.appendChild(chip);
        });
    }

    window.addExpenseItem = function (data = {}) {
        const index = itemCounter++;
        const node = document.getElementById('expenseItemTemplate').content.cloneNode(true);
        const card = node.querySelector('.expense-item');
        card.dataset.index = index;

        card.querySelectorAll('[data-field]').forEach(input => {
            const field = input.dataset.field;
            input.name = fieldName(index, field);
            if (field === 'files') return;
            if (input.type === 'checkbox') {
                input.checked = !!data[field];
                return;
            }
            const value = field === 'expense_date' ? (data.expense_date || TODAY) : data[field];
            if (value !== undefined && value !== null) input.value = value;
        });
        toggleReason(card);
        card.querySelector('[data-field="expense_date"]').max = TODAY;
        fillCategories(card.querySelector('.item-category-select'), data.expense_category_id);
        renderDocuments(card.querySelector('.existing-documents'), index, data.documents);

        document.getElementById('expenseItems').appendChild(card);
        calculateTotal();
    };

    function toggleReason(card) {
        const checked = card.querySelector('.without-receipt').checked;
        card.querySelector('.without-receipt-reason').style.display = checked ? 'block' : 'none';
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-amount').forEach(input => total += parseFloat(input.value) || 0);
        document.getElementById('totalAmount').textContent = clp(total);
        const projected = currentBalance - total;
        const projectedEl = document.getElementById('projectedBalance');
        projectedEl.textContent = clp(projected);
        projectedEl.classList.toggle('text-danger', projected < 0);
        document.getElementById('projectedHint').style.display = projected < 0 ? 'block' : 'none';
    }

    function loadAccount(accountId) {
        const summary = document.getElementById('accountSummary');
        if (!accountId) {
            currentBalance = 0;
            summary.style.display = 'none';
            calculateTotal();
            return;
        }
        fetch(`/accounts/${accountId}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(acc => {
                currentBalance = parseFloat(acc.balance) || 0;
                const el = document.getElementById('currentBalance');
                el.textContent = clp(currentBalance);
                el.classList.toggle('text-danger', currentBalance < 0);
                summary.style.display = 'flex';
                calculateTotal();
            })
            .catch(() => { summary.style.display = 'none'; });
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function showErrors(form, errors) {
        Object.entries(errors || {}).forEach(([key, messages]) => {
            const parts = key.split('.');
            let input;
            if (parts[0] === 'items' && parts.length >= 3) {
                const card = form.querySelector(`.expense-item[data-index="${parts[1]}"]`);
                if (parts[2] === 'files') {
                    const feedback = card?.querySelector('.files-feedback');
                    if (feedback) feedback.textContent = messages[0];
                    return;
                }
                input = card?.querySelector(`[data-field="${parts[2]}"]`);
            } else {
                input = form.querySelector(`[name="${parts[0]}"]`);
            }
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = messages[0];
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('expenseForm');

        form.addEventListener('input', e => { if (e.target.classList.contains('item-amount')) calculateTotal(); });
        form.addEventListener('change', e => {
            if (e.target.classList.contains('without-receipt')) {
                toggleReason(e.target.closest('.expense-item'));
            }
            if (e.target.classList.contains('item-files')) {
                const names = Array.from(e.target.files || []).map(f => f.name);
                e.target.closest('.expense-item').querySelector('.selected-files').textContent = names.length ? names.join(', ') : 'Ningún archivo nuevo';
            }
        });
        form.addEventListener('click', e => {
            const btn = e.target.closest('[data-remove-item]');
            if (!btn) return;
            if (document.querySelectorAll('.expense-item').length > 1) {
                btn.closest('.expense-item').remove();
                calculateTotal();
            } else {
                toastr.warning('La rendición debe tener al menos un gasto.');
            }
        });

        const accountSelect = document.getElementById('account_id');
        accountSelect.addEventListener('change', () => loadAccount(accountSelect.value));

        fetch('/datatables/expense-categories', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : { data: [] })
            .then(json => { categories = Array.isArray(json.data) ? json.data : []; })
            .catch(() => {})
            .finally(() => {
                if (EXISTING_ITEMS.length) EXISTING_ITEMS.forEach(item => addExpenseItem(item));
                else addExpenseItem();
                loadAccount(accountSelect.value);
            });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors(form);

            const submit = document.getElementById('expenseSubmit');
            submit.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    toastr.success(data.message);
                    window.location.href = data.redirect;
                    return;
                }
                showErrors(form, data.errors);
                toastr.error(data.message || 'Revisa los campos marcados.');
                submit.disabled = false;
            })
            .catch(() => {
                toastr.error('No se pudo guardar la rendición. Revisa tu conexión e inténtalo de nuevo.');
                submit.disabled = false;
            });
        });
    });
})();
</script>
@endpush
