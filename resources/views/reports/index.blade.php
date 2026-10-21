<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800  leading-tight">
            Informes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white  overflow-hidden shadow sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900  mb-4">Informes Disponibles</h3>

                    <div class="d-flex align-items-center justify-content-between border rounded p-3">
                        <div>
                            <div class="fw-semibold text-gray-900">Gastos mensuales</div>
                            <div class="text-sm text-gray-500">Rendiciones por categoría en un período, resumido o con detalle y documentos.</div>
                        </div>
                        <button type="button" onclick="openMonthlyExpenseReport()" class="btn btn-primary btn-sm">
                            <i class="fa fa-chart-bar"></i> Generar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Informe de Gastos Mensuales -->
    <div id="monthlyExpenseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Gastos Mensuales
                    </h3>
                    <button onclick="closeMonthlyExpenseModal()" class="text-gray-400 hover:text-gray-600 ">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="monthlyExpenseForm" class="space-y-5 pt-4">
                    <!-- Fechas -->
                    <div class="space-y-3">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700  mb-1">
                                Fecha Inicial
                            </label>
                            <input type="date" id="start_date" name="start_date" required
                              class="w-full rounded-md border-gray-300  bg-white  text-gray-900  shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700  mb-1">
                                Fecha Final
                            </label>
                            <input type="date" id="end_date" name="end_date" required
                              class="w-full rounded-md border-gray-300  bg-white  text-gray-900  shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <!-- Tipo de Informe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700  mb-2">
                            Tipo de Informe
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" id="report_type_summary" name="report_type" value="summary" checked
                                  class="rounded border-gray-300  text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Resumido (Solo totales por categoría)
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" id="report_type_detailed" name="report_type" value="detailed"
                                  class="rounded border-gray-300  text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Detallado (Con items individuales)
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Estado de Aprobación -->
                    <div class="pt-2">
                        <label class="block text-sm font-medium text-gray-700  mb-2">
                            Estado de Aprobación
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" id="approval_status_approved" name="approval_status" value="approved_only" checked
                                  class="rounded border-gray-300  text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Solo rendiciones aprobadas
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" id="approval_status_all" name="approval_status" value="all"
                                  class="rounded border-gray-300  text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Todas las rendiciones
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Incluir Documentos -->
                    <div id="include_documents_container" class="hidden pt-2">
                        <label class="block text-sm font-medium text-gray-700  mb-2">
                            Opciones Adicionales
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" id="include_documents" name="include_documents" value="1"
                              class="rounded border-gray-300  text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Incluir información de documentos adjuntos
                            </span>
                        </label>
                    </div>

                    <!-- Botones -->
                    <div class="flex flex-col space-y-2 pt-4 border-t border-gray-200">
                        <button type="button" onclick="generateMonthlyReport()" 
                          class="w-full btn btn-primary btn-sm">
                            <i class="fa fa-chart-bar"></i> Generar Informe
                        </button>
                        <button type="button" onclick="closeMonthlyExpenseModal()" 
                          class="w-full btn btn-outline-secondary btn-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar el informe generado -->
    <div id="reportDisplayModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-medium text-gray-900">
                        Informe de Gastos Mensuales
                    </h3>
                    <button onclick="closeReportDisplayModal()" class="text-gray-400 hover:text-gray-600 ">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="reportContent" class="max-h-96 overflow-y-auto">
                    <!-- El contenido del informe se cargará aquí dinámicamente -->
                </div>
                
                <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                    <button type="button" onclick="exportCurrentReport()" 
                      class="btn btn-success">
                        <i class="fa fa-file-excel"></i> Exportar a Excel
                    </button>
                    <button type="button" onclick="closeReportDisplayModal()" 
                      class="btn btn-secondary">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentReportParams = null;

        function esc(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
        }

        function formatChileanNumber(number) {
            const num = typeof number === 'string' ? parseFloat(number) : number;
            if (isNaN(num)) return '0';
            return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const firstOfLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const lastOfLastMonth = new Date(now.getFullYear(), now.getMonth(), 0);
            document.getElementById('start_date').value = firstOfLastMonth.toISOString().split('T')[0];
            document.getElementById('end_date').value = lastOfLastMonth.toISOString().split('T')[0];

            function toggleDocumentsOption() {
                const selectedType = document.querySelector('input[name="report_type"]:checked');
                const container = document.getElementById('include_documents_container');
                if (selectedType && selectedType.value === 'detailed') {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                    document.getElementById('include_documents').checked = false;
                }
            }
            document.querySelectorAll('input[name="report_type"]').forEach(radio => {
                radio.addEventListener('change', toggleDocumentsOption);
            });
            toggleDocumentsOption();

            if (new URLSearchParams(window.location.search).get('openModal') === 'monthlyExpense') {
                openMonthlyExpenseReport();
            }
        });

        function openMonthlyExpenseReport() {
            document.getElementById('monthlyExpenseModal').classList.remove('hidden');
        }

        function closeMonthlyExpenseModal() {
            document.getElementById('monthlyExpenseModal').classList.add('hidden');
        }

        function closeReportDisplayModal() {
            document.getElementById('reportDisplayModal').classList.add('hidden');
        }

        function generateMonthlyReport() {
            const formData = new FormData(document.getElementById('monthlyExpenseForm'));
            currentReportParams = Object.fromEntries(formData.entries());

            fetch('{{ route("reports.monthly-expenses") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json().catch(() => ({ success: false, message: 'Respuesta inválida del servidor.' })))
            .then(data => {
                if (data.success) {
                    displayReport(data.data, data.report_info);
                    closeMonthlyExpenseModal();
                } else {
                    coteToast('error', 'Error al generar el informe: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(() => coteToast('error', 'No se pudo contactar al servidor para generar el informe.'));
        }

        function exportCurrentReport() {
            if (!currentReportParams) {
                coteToast('warning', 'No hay informe actual para exportar.');
                return;
            }

            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = '{{ route("reports.export-monthly-expenses") }}';
            tempForm.style.display = 'none';

            const fields = { _token: '{{ csrf_token() }}', ...currentReportParams };
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                tempForm.appendChild(input);
            }

            document.body.appendChild(tempForm);
            tempForm.submit();
            document.body.removeChild(tempForm);
        }

        function statusBadge(status) {
            const approved = status === 'approved';
            return `<span class="px-2 py-1 text-xs font-semibold rounded-full ${approved ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${approved ? 'Aprobada' : 'Pendiente'}</span>`;
        }

        function documentLinks(documents) {
            if (!documents || documents.length === 0) {
                return '<td class="px-2 py-1 text-gray-500 italic">Sin documentos</td>';
            }
            const links = documents.map(doc => {
                const name = String(doc.filename ?? '');
                const extension = name.split('.').pop().toLowerCase();
                const iconClass = extension === 'pdf' ? 'fa-file-pdf text-red-600'
                    : /^(jpg|jpeg|png|gif|webp)$/.test(extension) ? 'fa-file-image text-blue-600'
                    : 'fa-file text-gray-600';
                const label = name.length > 15 ? name.substring(0, 12) + '...' : name;
                return `<a href="${esc(doc.url)}" target="_blank" rel="noopener" title="${esc(name)}"
                    class="inline-flex items-center px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                    <i class="fa ${iconClass} mr-1"></i>${esc(label)}</a>`;
            }).join('');
            return `<td class="px-2 py-1 text-gray-900"><div class="flex flex-wrap gap-1">${links}</div></td>`;
        }

        function displayReport(data, reportInfo) {
            const approvedOnly = reportInfo.approval_status === 'approved_only';
            let html = `<div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium text-gray-900">Información del Informe</h4>
                <p class="text-sm text-gray-600">
                    <strong>Período:</strong> ${esc(reportInfo.start_date)} al ${esc(reportInfo.end_date)}<br>
                    <strong>Tipo:</strong> ${reportInfo.report_type === 'summary' ? 'Resumido' : 'Detallado'}<br>
                    <strong>Filtro:</strong> <span class="px-2 py-1 rounded text-xs font-semibold ${approvedOnly ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">${approvedOnly ? 'Solo rendiciones aprobadas' : 'Todas las rendiciones (incluye pendientes)'}</span>
                </p>
            </div>
            <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-medium text-gray-900">Resumen General</h4>
                <div class="grid grid-cols-3 gap-4 mt-2 text-sm">
                    <div><strong>Total General:</strong> $${formatChileanNumber(data.total_amount)}</div>
                    <div><strong>Rendiciones:</strong> ${esc(data.total_expenses)}</div>
                    <div><strong>Items:</strong> ${esc(data.total_items)}</div>
                </div>
            </div>`;

            if (data.report_type === 'summary') {
                html += `<h4 class="font-medium text-gray-900 mb-3">Resumen por Categoría</h4>
                    <div class="overflow-x-auto"><table class="min-w-full table-auto">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rendiciones</th>
                    </tr></thead><tbody class="bg-white divide-y divide-gray-200">`;
                data.categories.forEach(category => {
                    html += `<tr>
                        <td class="px-4 py-2 text-sm text-gray-900">${esc(category.category)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 text-right">$${formatChileanNumber(category.total_amount)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 text-right">${esc(category.items_count)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 text-right">${esc(category.expenses_count)}</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
            } else {
                html += '<h4 class="font-medium text-gray-900 mb-3">Detalle por Categoría</h4>';
                data.categories.forEach(category => {
                    html += `<div class="mb-6 border rounded-lg p-4">
                        <h5 class="font-medium text-gray-900 mb-2">${esc(category.category)} - Total: $${formatChileanNumber(category.total_amount)}</h5>
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-auto text-sm">
                                <thead class="bg-gray-50"><tr>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">N° Rendición</th>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Fecha</th>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Persona</th>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Descripción</th>
                                    <th class="px-2 py-1 text-right text-xs font-medium text-gray-500">Monto</th>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Estado</th>
                                    ${data.include_documents ? '<th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Documentos</th>' : ''}
                                </tr></thead>
                                <tbody class="bg-white divide-y divide-gray-200">`;
                    category.items.forEach(item => {
                        html += `<tr>
                            <td class="px-2 py-1 text-gray-900">${esc(item.expense_number)}</td>
                            <td class="px-2 py-1 text-gray-900">${esc(item.expense_date)}</td>
                            <td class="px-2 py-1 text-gray-900">${esc(item.submitter)}</td>
                            <td class="px-2 py-1 text-gray-900">${esc(item.item_description)}</td>
                            <td class="px-2 py-1 text-gray-900 text-right">$${formatChileanNumber(item.amount)}</td>
                            <td class="px-2 py-1 text-gray-900">${statusBadge(item.expense_status)}</td>
                            ${data.include_documents ? documentLinks(item.documents) : ''}
                        </tr>`;
                    });
                    html += '</tbody></table></div></div>';
                });
            }

            document.getElementById('reportContent').innerHTML = html;
            document.getElementById('reportDisplayModal').classList.remove('hidden');
        }
    </script>
    @endpush
</x-app-layout>