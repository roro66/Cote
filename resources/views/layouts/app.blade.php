<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Dark 5 (theme for dark mode) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-dark-5@1.1.3/dist/css/bootstrap-dark.min.css" rel="stylesheet">
        
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

    <!-- Dark Mode Styles (project-specific tweaks; loaded after Bootstrap Dark) -->
        <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
        
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

        <!-- Dark Mode Toggle Script -->
        <script>
            function applyTheme(isDark) {
                const html = document.documentElement;
                if (isDark) {
                    html.classList.add('dark');
                    html.setAttribute('data-bs-theme', 'dark');
                } else {
                    html.classList.remove('dark');
                    html.setAttribute('data-bs-theme', 'light');
                }
            }

            function toggleTheme() {
                const html = document.documentElement;
                const isDark = html.classList.contains('dark');
                applyTheme(!isDark);
                localStorage.setItem('theme', !isDark ? 'dark' : 'light');
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Alpine.js (CDN) para dropdowns y UI reactiva sin compilar -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        
        <!-- Inicialización del modo oscuro -->
        <script>
            // Verificar tema guardado o preferencia del sistema y aplicarlo (Tailwind + Bootstrap 5.3)
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const storedTheme = localStorage.getItem('theme');
            const useDark = storedTheme ? storedTheme === 'dark' : prefersDark;
            (function() { applyTheme(useDark); })();
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <div class="fixed-header-nav">
                @include('layouts.navigation')
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header class="fixed-header-content bg-white dark:bg-gray-800 shadow">
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
        
        @stack('scripts')
    </body>
</html>
