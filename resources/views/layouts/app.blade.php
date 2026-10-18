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
