<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Set default light theme -->
        <script>
            (function() {
                try {
                    const html = document.documentElement;
                    html.setAttribute('data-bs-theme', 'light');
                } catch (e) {
                    // ignore
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- DataTables CSS - versiones estables -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- Toastr CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
        
        <!-- Toastr CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
        
        <!-- Custom Styles for Fixed Header -->
        <style>
            .fixed-header-nav {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1030;
            }
            
            .fixed-header-content {
                position: fixed;
                top: 64px; /* altura del nav */
                z-index: 1020;
            }

            .main-content {
                margin-top: 128px; /* nav (64px) + header (64px) */
            }

            /* Para móviles */
            @media (max-width: 640px) {
                .main-content {
                    margin-top: 140px; /* Un poco más de espacio en móviles */
                }
            }
            
            /* Espaciado para DataTables */
            .dataTables_wrapper .row:first-child {
                margin-bottom: 1rem !important;
            }
            
            .dt-buttons {
                margin-bottom: 0.5rem;
            }
            
            .dt-length {
                margin-bottom: 0.5rem;
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- Alpine.js (CDN) para dropdowns y UI reactiva sin compilar -->
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <div class="fixed-header-nav">
                @include('layouts.navigation')
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header class="fixed-header-content bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="main-content">
                {{ $slot }}
            </main>
        </div>
        
        <!-- jQuery PRIMERO -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Toastr para notificaciones -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        
        <!-- DataTables JS - versiones estables EN ORDEN CORRECTO -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
        
        <!-- DataTables Buttons - DESPUÉS del core -->
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
        
        <!-- Librerías para exportación -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        
        <!-- Botones de exportación -->
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
        
        <!-- Responsive -->
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
        
        <!-- Global Reports Functions -->
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

            function openMonthlyExpenseReport() {
                // Cargar la página de informes y abrir el modal automáticamente
                fetch('{{ route("reports.index") }}')
                    .then(response => response.text())
                    .then(html => {
                        // Crear un contenedor temporal para cargar el HTML
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        
                        // Buscar el modal en el HTML cargado
                        const modal = tempDiv.querySelector('#monthlyExpenseModal');
                        if (modal) {
                            // Si no existe en la página actual, agregarlo
                            if (!document.querySelector('#monthlyExpenseModal')) {
                                document.body.appendChild(modal);
                            }
                            
                            // También agregar el modal de resultados si no existe
                            const resultModal = tempDiv.querySelector('#reportDisplayModal');
                            if (resultModal && !document.querySelector('#reportDisplayModal')) {
                                document.body.appendChild(resultModal);
                            }
                            
                            // Agregar el contenedor de resultados si no existe
                            if (!document.querySelector('#reportResults')) {
                                const reportResults = document.createElement('div');
                                reportResults.id = 'reportResults';
                                document.body.appendChild(reportResults);
                            }
                            
                            // Cargar las funciones JavaScript necesarias
                            loadReportsFunctions();
                            
                            // Abrir el modal
                            openMonthlyExpenseModal();
                        }
                    })
                    .catch(error => {
                        console.error('Error loading reports:', error);
                        // Fallback: redirigir a la página
                        window.location.href = '{{ route("reports.index") }}';
                    });
            }

            function loadReportsFunctions() {
                // Solo cargar si no están ya definidas
                if (typeof openMonthlyExpenseModal !== 'function') {
                    window.openMonthlyExpenseModal = function() {
                        const modal = document.getElementById('monthlyExpenseModal');
                        if (modal) {
                            // Inicializar fechas si no están configuradas
                            const startDate = document.getElementById('start_date');
                            const endDate = document.getElementById('end_date');
                            if (startDate && !startDate.value) {
                                const now = new Date();
                                const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                                const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 0);
                                startDate.value = lastMonth.toISOString().split('T')[0];
                                endDate.value = lastDayOfMonth.toISOString().split('T')[0];
                            }
                            
                            // Configurar listener para mostrar/ocultar documentos
                            function toggleDocumentsOptionGlobal() {
                                const selectedType = document.querySelector('input[name="report_type"]:checked');
                                const container = document.getElementById('include_documents_container');
                                if (container) {
                                    if (selectedType && selectedType.value === 'detailed') {
                                        container.classList.remove('hidden');
                                    } else {
                                        container.classList.add('hidden');
                                        const checkbox = document.getElementById('include_documents');
                                        if (checkbox) checkbox.checked = false;
                                    }
                                }
                            }
                            
                            const reportTypeRadios = document.querySelectorAll('input[name="report_type"]');
                            reportTypeRadios.forEach(radio => {
                                if (!radio.hasAttribute('data-listener-added')) {
                                    radio.addEventListener('change', toggleDocumentsOptionGlobal);
                                    radio.setAttribute('data-listener-added', 'true');
                                }
                            });
                            
                            // Verificar estado inicial
                            setTimeout(toggleDocumentsOptionGlobal, 100);
                            
                            modal.classList.remove('hidden');
                        }
                    };
                }
                
                if (typeof closeMonthlyExpenseModal !== 'function') {
                    window.closeMonthlyExpenseModal = function() {
                        const modal = document.getElementById('monthlyExpenseModal');
                        if (modal) modal.classList.add('hidden');
                    };
                }
                
                if (typeof generateMonthlyReport !== 'function') {
                    window.generateMonthlyReport = function() {
                        const form = document.getElementById('monthlyExpenseForm');
                        if (!form) return;
                        
                        const formData = new FormData(form);
                        
                        // Guardar los parámetros del informe
                        currentReportParams = {};
                        for (let [key, value] of formData.entries()) {
                            currentReportParams[key] = value;
                        }
                        
                        fetch('{{ route("reports.monthly-expenses") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.text())
                        .then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (data.success) {
                                    displayReport(data.data, data.report_info);
                                    closeMonthlyExpenseModal();
                                } else {
                                    alert('Error: ' + (data.message || 'Error desconocido'));
                                }
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                alert('Error al procesar el informe');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error al generar el informe');
                        });
                    };
                }
                
                if (typeof exportCurrentReport !== 'function') {
                    window.exportCurrentReport = function() {
                        if (!currentReportParams) {
                            alert('No hay informe actual para exportar.');
                            return;
                        }
                        
                        const tempForm = document.createElement('form');
                        tempForm.method = 'POST';
                        tempForm.action = '{{ route("reports.export-monthly-expenses") }}';
                        tempForm.style.display = 'none';
                        
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = '{{ csrf_token() }}';
                        tempForm.appendChild(csrfInput);
                        
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
                    };
                }
                
                if (typeof displayReport !== 'function') {
                    window.displayReport = function(data, reportInfo) {
                        let html = `<div class="mb-4 p-4 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-900">Información del Informe</h4>
                            <p class="text-sm text-gray-600">
                                <strong>Período:</strong> ${reportInfo.start_date} al ${reportInfo.end_date}<br>
                                <strong>Tipo:</strong> ${reportInfo.report_type === 'summary' ? 'Resumido' : 'Detallado'}<br>
                                <strong>Filtro:</strong> <span class="px-2 py-1 rounded text-xs font-semibold ${reportInfo.approval_status === 'approved_only' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'}">${reportInfo.approval_status === 'approved_only' ? 'Solo rendiciones aprobadas' : 'Todas las rendiciones (incluye pendientes)'}</span>
                            </p>
                        </div>`;
                        
                        html += `<div class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-medium text-gray-900">Resumen General</h4>
                            <div class="grid grid-cols-3 gap-4 mt-2 text-sm">
                                <div><strong>Total General:</strong> $${formatChileanNumber(data.total_amount)}</div>
                                <div><strong>Rendiciones:</strong> ${data.total_expenses}</div>
                                <div><strong>Items:</strong> ${data.total_items}</div>
                            </div>
                        </div>`;
                        
                        // Mostrar datos por categoría
                        if (data.report_type === 'summary') {
                            html += '<h4 class="font-medium text-gray-900 mb-3">Resumen por Categoría</h4>';
                            html += '<div class="overflow-x-auto"><table class="min-w-full table-auto">';
                            html += '<thead class="bg-gray-50"><tr>';
                            html += '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>';
                            html += '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>';
                            html += '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>';
                            html += '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Rendiciones</th>';
                            html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                            
                            data.categories.forEach(category => {
                                html += `<tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">${category.category}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">$${formatChileanNumber(category.total_amount)}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">${category.items_count}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">${category.expenses_count}</td>
                                </tr>`;
                            });
                            
                            html += '</tbody></table></div>';
                        } else {
                            // Informe detallado
                            html += '<h4 class="font-medium text-gray-900 mb-3">Detalle por Categoría</h4>';
                            
                            data.categories.forEach(category => {
                                html += `<div class="mb-6 border rounded-lg p-4">
                                    <h5 class="font-medium text-gray-900 mb-2">
                                        ${category.category} - Total: $${formatChileanNumber(category.total_amount)}
                                    </h5>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full table-auto text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">N° Rendición</th>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Fecha</th>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Persona</th>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Descripción</th>
                                                    <th class="px-2 py-1 text-right text-xs font-medium text-gray-500">Monto</th>
                                                    <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Estado</th>
                                                    ${data.include_documents ? '<th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Documentos</th>' : ''}
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">`;
                                
                                category.items.forEach(item => {
                                    html += `<tr>
                                        <td class="px-2 py-1 text-gray-900">${item.expense_number}</td>
                                        <td class="px-2 py-1 text-gray-900">${item.expense_date}</td>
                                        <td class="px-2 py-1 text-gray-900">${item.submitter}</td>
                                        <td class="px-2 py-1 text-gray-900">${item.item_description}</td>
                                        <td class="px-2 py-1 text-gray-900 text-right">$${formatChileanNumber(item.amount)}</td>
                                        <td class="px-2 py-1 text-gray-900">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${item.expense_status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                                ${item.expense_status === 'approved' ? 'Aprobada' : 'Pendiente'}
                                            </span>
                                        </td>`;
                                    
                                    // Agregar columna de documentos si está habilitada
                                    if (data.include_documents) {
                                        if (item.documents && item.documents.length > 0) {
                                            html += `<td class="px-2 py-1 text-gray-900">
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
                                            html += `<td class="px-2 py-1 text-gray-500 italic">Sin documentos</td>`;
                                        }
                                    }
                                    
                                    html += `</tr>`;
                                });
                                
                                html += '</tbody></table></div></div>';
                            });
                        }
                        
                        const reportContent = document.getElementById('reportContent');
                        if (reportContent) {
                            reportContent.innerHTML = html;
                            document.getElementById('reportDisplayModal').classList.remove('hidden');
                        }
                    };
                }
                
                if (typeof closeReportDisplayModal !== 'function') {
                    window.closeReportDisplayModal = function() {
                        const modal = document.getElementById('reportDisplayModal');
                        if (modal) modal.classList.add('hidden');
                    };
                }
            }
        </script>
        
        @stack('scripts')
    </body>
</html>
