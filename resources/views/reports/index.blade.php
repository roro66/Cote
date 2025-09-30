<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Informes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Informes Disponibles</h3>
                    
@section('content')
<div class="container-fluid">
    <!-- Contenedor para mostrar resultados -->
    <div id="reportResults" class="mt-4"></div>
</div>                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button onclick="testFilters()" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-flask"></i> Probar Filtros
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Informe de Gastos Mensuales -->
    <div id="monthlyExpenseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Gastos Mensuales
                    </h3>
                    <button onclick="closeMonthlyExpenseModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="monthlyExpenseForm" class="space-y-5 pt-4">
                    <!-- Fechas -->
                    <div class="space-y-3">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Fecha Inicial
                            </label>
                            <input type="date" id="start_date" name="start_date" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Fecha Final
                            </label>
                            <input type="date" id="end_date" name="end_date" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    <!-- Tipo de Informe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tipo de Informe
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" id="report_type_summary" name="report_type" value="summary" checked
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Resumido (Solo totales por categoría)
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" id="report_type_detailed" name="report_type" value="detailed"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Detallado (Con items individuales)
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Estado de Aprobación -->
                    <div class="pt-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Estado de Aprobación
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" id="approval_status_approved" name="approval_status" value="approved_only" checked
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Solo rendiciones aprobadas
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" id="approval_status_all" name="approval_status" value="all"
                                    class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Todas las rendiciones
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Incluir Documentos -->
                    <div id="include_documents_container" class="hidden pt-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Opciones Adicionales
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" id="include_documents" name="include_documents" value="1"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Incluir información de documentos adjuntos
                            </span>
                        </label>
                    </div>

                    <!-- Botones -->
                    <div class="flex flex-col space-y-2 pt-4 border-t border-gray-200 dark:border-gray-700">
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
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <div class="flex items-center justify-between pb-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Informe de Gastos Mensuales
                    </h3>
                    <button onclick="closeReportDisplayModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="reportContent" class="max-h-96 overflow-y-auto">
                    <!-- El contenido del informe se cargará aquí dinámicamente -->
                </div>
                
                <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
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
        // Variables globales para guardar los parámetros del informe actual
        let currentReportParams = null;

        // Función para formatear números según la notación chilena
        function formatChileanNumber(number) {
            // Convertir a número si es string
            const num = typeof number === 'string' ? parseFloat(number) : number;
            
            // Validar que sea un número válido
            if (isNaN(num)) return '0';
            
            // Redondear según reglas chilenas (sin centavos)
            const rounded = Math.round(num);
            
            // Formatear con puntos como separadores de miles
            return rounded.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        // Inicializar fechas con el mes anterior
        document.addEventListener('DOMContentLoaded', function() {
            // Si llegamos directamente desde el menú, abrir el modal automáticamente
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openModal') === 'monthlyExpense') {
                setTimeout(() => {
                    openMonthlyExpenseReport();
                }, 100);
            }
            const now = new Date();
            const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 0);
            
            document.getElementById('start_date').value = lastMonth.toISOString().split('T')[0];
            document.getElementById('end_date').value = lastDayOfMonth.toISOString().split('T')[0];
            
            // Mostrar/ocultar opción de documentos según el tipo de informe
            function toggleDocumentsOption() {
                const selectedType = document.querySelector('input[name="report_type"]:checked');
                const container = document.getElementById('include_documents_container');
                if (selectedType && selectedType.value === 'detailed') {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                    const checkbox = document.getElementById('include_documents');
                    if (checkbox) checkbox.checked = false;
                }
            }
            
            document.querySelectorAll('input[name="report_type"]').forEach(radio => {
                radio.addEventListener('change', toggleDocumentsOption);
            });
            
            // Verificar estado inicial
            toggleDocumentsOption();
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
            const form = document.getElementById('monthlyExpenseForm');
            const formData = new FormData(form);
            
            // Guardar los parámetros del informe para poder exportar después
            currentReportParams = {};
            for (let [key, value] of formData.entries()) {
                currentReportParams[key] = value;
            }
            
            // Debug: mostrar los datos que se envían
            console.log('Datos del formulario:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            fetch('{{ route("reports.monthly-expenses") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text().then(text => {
                    console.log('Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response');
                    }
                });
            })
            .then(data => {
                console.log('Parsed data:', data);
                if (data.success) {
                    displayReport(data.data, data.report_info);
                    closeMonthlyExpenseModal();
                } else {
                    alert('Error al generar el informe: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al generar el informe: ' + error.message);
            });
        }

        function exportMonthlyReport() {
            const form = document.getElementById('monthlyExpenseForm');
            const formData = new FormData(form);
            
            // Crear un formulario temporal para la descarga
            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = '{{ route("reports.export-monthly-expenses") }}';
            tempForm.style.display = 'none';
            
            // Agregar token CSRF
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            tempForm.appendChild(csrfInput);
            
            // Agregar todos los campos del formulario
            for (let [key, value] of formData.entries()) {
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

        function exportCurrentReport() {
            if (!currentReportParams) {
                alert('No hay informe actual para exportar.');
                return;
            }
            
            // Crear un formulario temporal para la descarga
            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = '{{ route("reports.export-monthly-expenses") }}';
            tempForm.style.display = 'none';
            
            // Agregar token CSRF
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            tempForm.appendChild(csrfInput);
            
            // Agregar todos los parámetros del informe actual
            for (let [key, value] of Object.entries(currentReportParams)) {
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

        function displayReport(data, reportInfo) {
            let html = '';
            
            // Información del período
            html += `<div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">Información del Informe</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    <strong>Período:</strong> ${reportInfo.start_date} al ${reportInfo.end_date}<br>
                    <strong>Tipo:</strong> ${reportInfo.report_type === 'summary' ? 'Resumido' : 'Detallado'}<br>
                    <strong>Filtro:</strong> <span class="px-2 py-1 rounded text-xs font-semibold ${reportInfo.approval_status === 'approved_only' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">${reportInfo.approval_status === 'approved_only' ? 'Solo rendiciones aprobadas' : 'Todas las rendiciones (incluye pendientes)'}</span>
                </p>
            </div>`;
            
            // Resumen general
            html += `<div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">Resumen General</h4>
                <div class="grid grid-cols-3 gap-4 mt-2 text-sm">
                    <div><strong>Total General:</strong> $${formatChileanNumber(data.total_amount)}</div>
                    <div><strong>Rendiciones:</strong> ${data.total_expenses}</div>
                    <div><strong>Items:</strong> ${data.total_items}</div>
                </div>
            </div>`;
            
            // Mostrar datos por categoría
            if (data.report_type === 'summary') {
                html += '<h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Resumen por Categoría</h4>';
                html += '<div class="overflow-x-auto"><table class="min-w-full table-auto">';
                html += '<thead class="bg-gray-50 dark:bg-gray-700"><tr>';
                html += '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Categoría</th>';
                html += '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>';
                html += '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Items</th>';
                html += '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rendiciones</th>';
                html += '</tr></thead><tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">';
                
                data.categories.forEach(category => {
                    html += `<tr>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">${category.category}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">$${formatChileanNumber(category.total_amount)}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">${category.items_count}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 text-right">${category.expenses_count}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
            } else {
                // Informe detallado
                html += '<h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Detalle por Categoría</h4>';
                
                data.categories.forEach(category => {
                    html += `<div class="mb-6 border rounded-lg p-4">
                        <h5 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                            ${category.category} - Total: $${formatChileanNumber(category.total_amount)}
                        </h5>
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-auto text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-300">N° Rendición</th>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Fecha</th>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Persona</th>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Descripción</th>
                                        <th class="px-2 py-1 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Monto</th>
                                        <th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Estado</th>
                                        ${data.include_documents ? '<th class="px-2 py-1 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Documentos</th>' : ''}
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">`;
                    
                    category.items.forEach(item => {
                        html += `<tr>
                            <td class="px-2 py-1 text-gray-900 dark:text-gray-100">${item.expense_number}</td>
                            <td class="px-2 py-1 text-gray-900 dark:text-gray-100">${item.expense_date}</td>
                            <td class="px-2 py-1 text-gray-900 dark:text-gray-100">${item.submitter}</td>
                            <td class="px-2 py-1 text-gray-900 dark:text-gray-100">${item.item_description}</td>
                            <td class="px-2 py-1 text-gray-900 dark:text-gray-100 text-right">$${formatChileanNumber(item.amount)}</td>
                            <td class="px-2 py-1 text-gray-900 dark:text-gray-100">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full ${item.expense_status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                    ${item.expense_status === 'approved' ? 'Aprobada' : 'Pendiente'}
                                </span>
                            </td>`;
                        
                        // Agregar columna de documentos si está habilitada
                        if (data.include_documents) {
                            if (item.documents && item.documents.length > 0) {
                                html += `<td class="px-2 py-1 text-gray-900 dark:text-gray-100">
                                    <div class="flex flex-wrap gap-1">`;
                                item.documents.forEach(doc => {
                                    const extension = doc.filename.split('.').pop().toLowerCase();
                                    const iconClass = extension === 'pdf' ? 'fa-file-pdf text-red-600' : 
                                                    extension.match(/(jpg|jpeg|png|gif)/) ? 'fa-file-image text-blue-600' : 
                                                    'fa-file text-gray-600';
                                    html += `<a href="${doc.url}" target="_blank" title="${doc.filename}" 
                                        class="inline-flex items-center px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded">
                                        <i class="fa ${iconClass} mr-1"></i>
                                        ${doc.filename.length > 15 ? doc.filename.substring(0, 12) + '...' : doc.filename}
                                    </a>`;
                                });
                                html += `</div></td>`;
                            } else {
                                html += `<td class="px-2 py-1 text-gray-500 dark:text-gray-400 italic">Sin documentos</td>`;
                            }
                        }
                        
                        html += `</tr>`;
                    });
                    
                    html += '</tbody></table></div></div>';
                });
            }
            
            document.getElementById('reportContent').innerHTML = html;
            document.getElementById('reportDisplayModal').classList.remove('hidden');
        }

        function testFilters() {
            fetch('{{ route("reports.test-filters") }}')
            .then(response => response.json())
            .then(data => {
                alert(`PRUEBA DE FILTROS (${data.period}):

Solo aprobadas: ${data.approved_only.total_expenses} rendiciones, $${data.approved_only.total_amount.toLocaleString()}

Todas las rendiciones: ${data.all.total_expenses} rendiciones, $${data.all.total_amount.toLocaleString()}

DIFERENCIA: ${data.difference.expenses} rendiciones, $${data.difference.amount.toLocaleString()}

${data.difference.expenses > 0 ? '✅ Los filtros están funcionando correctamente!' : '⚠️ No hay rendiciones sin aprobar en este período'}`);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al probar filtros: ' + error.message);
            });
        }
    </script>
    @endpush
</x-app-layout>