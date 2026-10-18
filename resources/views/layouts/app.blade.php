<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Tema: preferencia guardada o preferencia del sistema -->
        <script>
            (function() {
                try {
                    var theme = localStorage.getItem('theme');
                    if (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
                        theme = 'dark';
                    theme = theme || 'light';
                    document.documentElement.setAttribute('data-bs-theme', theme);
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                } catch (e) { /* ignore */ }
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
        
        <!-- Sidebar lateral estilo AdminLTE -->
        <style>
            :root {
                --sidebar-width: 250px;
                --sidebar-bg: #343a40;
                --sidebar-nav-hover: rgba(255,255,255,0.08);
                --sidebar-nav-active: rgba(255,255,255,0.15);
            }
            body { overflow-x: hidden; }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1035;
                width: var(--sidebar-width);
                height: 100vh;
                background: var(--sidebar-bg);
                transition: transform 0.2s ease;
                display: flex;
                flex-direction: column;
                overflow-x: hidden;
            }
            .sidebar-brand { min-height: 115px; flex-shrink: 0; padding: 0.75rem 0.5rem; }
            .sidebar-brand-link { min-width: 0; min-height: 100px; justify-content: center; }
            .sidebar-nav { flex: 1; overflow-y: auto; overflow-x: hidden; }
            .sidebar-nav .nav-link {
                display: flex;
                align-items: center;
                padding: 0.6rem 1rem;
                color: rgba(255,255,255,0.8);
                text-decoration: none;
                border-left: 3px solid transparent;
                transition: background 0.15s, color 0.15s;
            }
            .sidebar-nav .nav-link:hover { background: var(--sidebar-nav-hover); color: #fff; }
            .sidebar-nav .nav-link.active { background: var(--sidebar-nav-active); color: #fff; border-left-color: #0d6efd; }
            .sidebar-nav .nav-icon {
                width: 1.5rem;
                flex-shrink: 0;
                text-align: center;
                margin-right: 0.5rem;
            }
            .sidebar-logo { max-height: 100px; width: auto; object-fit: contain; display: block; }
            .sidebar-footer-text { color: rgba(255,255,255,0.85); font-size: 0.8rem; }
            .sidebar-nav .nav-text { white-space: nowrap; overflow: hidden; }
            .sidebar-divider { height: 1px; margin: 0.5rem 1rem; background: rgba(255,255,255,0.2); list-style: none; }
            .sidebar-nav .nav-header { padding: 0.5rem 1rem; font-size: 0.7rem; text-transform: uppercase; color: rgba(255,255,255,0.5); list-style: none; }
            .sidebar-footer { flex-shrink: 0; }
            .main-wrapper {
                margin-left: var(--sidebar-width);
                min-height: 100vh;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
            }
            .navbar-top { min-height: 52px; z-index: 1030; }
            .navbar-page-title { font-size: 1.1rem; font-weight: 500; }
            .main-content { flex: 1; padding: 1rem 1.5rem; min-width: 0; max-width: 100%; }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1034;
            }
            @media (max-width: 767.98px) {
                .sidebar { transform: translateX(-100%); }
                .sidebar.sidebar-mobile-open { transform: translateX(0); }
                .sidebar-overlay.sidebar-overlay-show { display: block; }
                .main-wrapper { margin-left: 0 !important; }
            }
            [x-cloak] { display: none !important; }
            .hover-bg-light:hover { background: rgba(0,0,0,0.05); }
            .dataTables_wrapper .row:first-child { margin-bottom: 1rem !important; }
            .dt-buttons { margin-bottom: 0.5rem; }
            .dt-length { margin-bottom: 0.5rem; }

            /* ========== Modo oscuro REAL: todo el área de contenido ========== */
            [data-bs-theme="dark"] body { background-color: #212529 !important; }
            [data-bs-theme="dark"] .main-wrapper { background-color: #212529 !important; }
            [data-bs-theme="dark"] .main-content { background-color: #212529 !important; color: #e9ecef; }
            /* Anular Tailwind/Bootstrap bg-white, bg-light y bg-gray-50 dentro del contenido */
            [data-bs-theme="dark"] .main-wrapper .bg-white { background-color: #343a40 !important; }
            [data-bs-theme="dark"] .main-wrapper .bg-light { background-color: #343a40 !important; }
            [data-bs-theme="dark"] .main-wrapper .bg-gray-50 { background-color: rgba(255,255,255,0.06) !important; }
            /* Forzar texto claro: anular Tailwind text-gray-* que dejan texto negro en modo oscuro */
            [data-bs-theme="dark"] .main-wrapper .text-gray-900 { color: #f8f9fa !important; }
            [data-bs-theme="dark"] .main-wrapper .text-gray-800 { color: #f8f9fa !important; }
            [data-bs-theme="dark"] .main-wrapper .text-gray-700 { color: #e9ecef !important; }
            [data-bs-theme="dark"] .main-wrapper .text-gray-600 { color: #e9ecef !important; }
            [data-bs-theme="dark"] .main-wrapper .text-gray-500 { color: #adb5bd !important; }
            [data-bs-theme="dark"] .main-wrapper .text-gray-400 { color: #adb5bd !important; }
            [data-bs-theme="dark"] .main-wrapper .text-gray-300 { color: #ced4da !important; }
            /* Títulos y encabezados dentro del contenido */
            [data-bs-theme="dark"] .main-wrapper h1, [data-bs-theme="dark"] .main-wrapper h2,
            [data-bs-theme="dark"] .main-wrapper h3, [data-bs-theme="dark"] .main-wrapper h4,
            [data-bs-theme="dark"] .main-wrapper h5, [data-bs-theme="dark"] .main-wrapper h6 { color: #f8f9fa !important; }
            /* Celdas de tabla (DataTables y tablas Bootstrap): texto siempre claro */
            [data-bs-theme="dark"] .main-wrapper table tbody td,
            [data-bs-theme="dark"] .main-wrapper table tbody th { color: #e9ecef !important; }
            /* Bootstrap text-dark y similares */
            [data-bs-theme="dark"] .main-wrapper .text-dark { color: #e9ecef !important; }
            [data-bs-theme="dark"] .main-wrapper .text-black { color: #f8f9fa !important; }
            [data-bs-theme="dark"] .navbar-top {
                background-color: #212529 !important;
                border-bottom-color: #495057 !important;
            }
            [data-bs-theme="dark"] .navbar-top .navbar-page-title,
            [data-bs-theme="dark"] .navbar-top .text-muted { color: #e9ecef !important; }
            [data-bs-theme="dark"] .navbar-top .btn-link { color: #e9ecef !important; }
            [data-bs-theme="dark"] .navbar-top .btn-link:hover { color: #fff !important; }
            [data-bs-theme="dark"] .navbar-top .hover-bg-light:hover { background: rgba(255,255,255,0.08) !important; }
            /* DataTables modo oscuro: fondo y texto legibles */
            [data-bs-theme="dark"] .dataTables_wrapper .table { background-color: #212529; color: #e9ecef; border-color: #495057; }
            [data-bs-theme="dark"] .dataTables_wrapper thead th { background-color: #343a40; color: #f8f9fa; border-color: #495057; }
            [data-bs-theme="dark"] .dataTables_wrapper tbody td,
            [data-bs-theme="dark"] .dataTables_wrapper tbody th { border-color: #495057; color: #e9ecef !important; }
            [data-bs-theme="dark"] .dataTables_wrapper input,
            [data-bs-theme="dark"] .dataTables_wrapper select {
                background-color: #343a40; color: #f8f9fa; border-color: #495057;
            }
            [data-bs-theme="dark"] .dataTables_wrapper .dataTables_length label,
            [data-bs-theme="dark"] .dataTables_wrapper .dataTables_info { color: #adb5bd !important; }
            [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .page-link {
                background-color: #343a40; color: #e9ecef; border-color: #495057;
            }
            [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
                background-color: #0d6efd; border-color: #0d6efd; color: #fff;
            }
            [data-bs-theme="dark"] .dataTables_wrapper .dt-buttons .btn {
                background-color: #343a40; color: #e9ecef; border-color: #495057;
            }
            [data-bs-theme="dark"] .dataTables_wrapper .dt-buttons .btn:hover { background-color: #495057; color: #fff; }
            /* Cards y contenido en modo oscuro */
            [data-bs-theme="dark"] .card { background-color: #343a40; border-color: #495057; color: #e9ecef; }
            [data-bs-theme="dark"] .card-header { background-color: #212529; border-color: #495057; color: #f8f9fa; }
            [data-bs-theme="dark"] .table { color: #e9ecef; }
            [data-bs-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) { --bs-table-accent-bg: rgba(255,255,255,0.03); }
            [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select {
                background-color: #343a40; color: #f8f9fa; border-color: #495057;
            }
            [data-bs-theme="dark"] .form-control::placeholder { color: #adb5bd; }
            [data-bs-theme="dark"] .form-control:focus { background-color: #343a40; color: #f8f9fa; border-color: #86b7fe; }
            [data-bs-theme="dark"] .form-label, [data-bs-theme="dark"] label { color: #e9ecef; }
            [data-bs-theme="dark"] .text-body { color: #e9ecef !important; }
            [data-bs-theme="dark"] .text-muted { color: #adb5bd !important; }
            [data-bs-theme="dark"] .border { border-color: #495057 !important; }
            [data-bs-theme="dark"] .dropdown-menu { background-color: #343a40; border-color: #495057; }
            [data-bs-theme="dark"] .dropdown-item { color: #e9ecef; }
            [data-bs-theme="dark"] .dropdown-item:hover { background-color: #495057; color: #fff; }
            [data-bs-theme="dark"] .dropdown-divider { border-color: #495057; }
            /* Dropdown usuario (Alpine/Tailwind): modo oscuro */
            [data-bs-theme="dark"] .dropdown-content-theme { background-color: #343a40 !important; border-color: #495057; }
            [data-bs-theme="dark"] .dropdown-content-theme a { color: #e9ecef !important; }
            [data-bs-theme="dark"] .dropdown-content-theme a:hover { background-color: #495057 !important; color: #fff !important; }
            /* Modales Bootstrap en modo oscuro (refuerzo) */
            [data-bs-theme="dark"] .modal-content { background-color: #343a40; border-color: #495057; color: #e9ecef; }
            [data-bs-theme="dark"] .modal-header { border-color: #495057; }
            [data-bs-theme="dark"] .modal-footer { border-color: #495057; }
            [data-bs-theme="dark"] .modal-body { color: #e9ecef; }
            /* Enlaces en contenido: visibles en modo oscuro */
            [data-bs-theme="dark"] .main-content a:not(.btn) { color: #6ea8fe; }
            [data-bs-theme="dark"] .main-content a:not(.btn):hover { color: #9ec5fe; }
            /* Toastr en modo oscuro: texto legible */
            [data-bs-theme="dark"] #toast-container .toast { background-color: #343a40; color: #e9ecef; }
            [data-bs-theme="dark"] #toast-container .toast-title { color: #f8f9fa; }
            [data-bs-theme="dark"] #toast-container .toast-message { color: #e9ecef; }
            [data-bs-theme="dark"] .btn-light { background-color: #495057; color: #f8f9fa; border-color: #495057; }
            [data-bs-theme="dark"] .btn-light:hover { background-color: #5a6268; color: #fff; }
            /* Modales de Informes (se insertan en body desde JS): modo oscuro por ID */
            [data-bs-theme="dark"] #monthlyExpenseModal .bg-white,
            [data-bs-theme="dark"] #reportDisplayModal .bg-white { background-color: #343a40 !important; }
            [data-bs-theme="dark"] #monthlyExpenseModal .text-gray-900,
            [data-bs-theme="dark"] #monthlyExpenseModal .text-gray-700,
            [data-bs-theme="dark"] #reportDisplayModal .text-gray-900,
            [data-bs-theme="dark"] #reportDisplayModal .text-gray-700 { color: #e9ecef !important; }
            [data-bs-theme="dark"] #monthlyExpenseModal .text-gray-400,
            [data-bs-theme="dark"] #monthlyExpenseModal .text-gray-600,
            [data-bs-theme="dark"] #reportDisplayModal .text-gray-400,
            [data-bs-theme="dark"] #reportDisplayModal .text-gray-600 { color: #adb5bd !important; }
            [data-bs-theme="dark"] #monthlyExpenseModal .border-gray-200,
            [data-bs-theme="dark"] #reportDisplayModal .border-gray-200 { border-color: #495057 !important; }
            [data-bs-theme="dark"] #monthlyExpenseModal input[type="date"],
            [data-bs-theme="dark"] #monthlyExpenseModal input[type="text"],
            [data-bs-theme="dark"] #monthlyExpenseModal input[type="number"],
            [data-bs-theme="dark"] #monthlyExpenseModal select,
            [data-bs-theme="dark"] #reportDisplayModal input,
            [data-bs-theme="dark"] #reportDisplayModal select {
                background-color: #343a40 !important; color: #f8f9fa !important; border-color: #495057 !important;
            }
            [data-bs-theme="dark"] #monthlyExpenseModal label,
            [data-bs-theme="dark"] #reportDisplayModal label { color: #e9ecef !important; }
            [data-bs-theme="dark"] #reportDisplayModal #reportContent,
            [data-bs-theme="dark"] #reportDisplayModal #reportContent table { background-color: #212529 !important; color: #e9ecef !important; }
            [data-bs-theme="dark"] #reportDisplayModal #reportContent th,
            [data-bs-theme="dark"] #reportDisplayModal #reportContent td { border-color: #495057; color: #e9ecef !important; }
            /* Iconos del botón tema: en claro se muestra luna, en oscuro sol */
            .theme-icon-dark { display: none !important; }
            .theme-icon-light { display: inline-block !important; }
            [data-bs-theme="dark"] .theme-icon-light { display: none !important; }
            [data-bs-theme="dark"] .theme-icon-dark { display: inline-block !important; }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- Alpine llega con Livewire: cargarlo en todas las páginas, no solo en las que tienen componentes --}}
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>
        @include('layouts.sidebar')

        <div class="main-wrapper bg-light">
            @include('layouts.navbar-top')

            <main class="main-content">
                {{ $slot }}
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var overlay = document.getElementById('sidebar-overlay');
                var sidebar = document.getElementById('sidebar');
                var mobileToggle = document.getElementById('sidebar-mobile-toggle');
                if (overlay && sidebar) {
                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('sidebar-mobile-open');
                        overlay.classList.remove('sidebar-overlay-show');
                    });
                }
                if (mobileToggle && sidebar) {
                    mobileToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('sidebar-mobile-open');
                        document.getElementById('sidebar-overlay').classList.toggle('sidebar-overlay-show');
                    });
                }

                // Toggle modo oscuro / claro
                var themeToggle = document.getElementById('theme-toggle');
                if (themeToggle) {
                    themeToggle.addEventListener('click', function() {
                        var html = document.documentElement;
                        var current = html.getAttribute('data-bs-theme') || 'light';
                        var next = current === 'dark' ? 'light' : 'dark';
                        html.setAttribute('data-bs-theme', next);
                        html.classList.toggle('dark', next === 'dark');
                        try { localStorage.setItem('theme', next); } catch (e) {}
                    });
                }
            });
        </script>
        
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
        
        
        @stack('scripts')
        @livewireScripts
    </body>
</html>
