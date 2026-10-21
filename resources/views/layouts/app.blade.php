<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.theme-init')

        <!-- Bootstrap CSS -->
        <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
        
        <!-- DataTables CSS - versiones estables -->
        <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/datatables/css/buttons.bootstrap5.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/datatables/css/responsive.bootstrap5.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/datatables/css/colReorder.bootstrap5.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/datatables/css/select.bootstrap5.min.css') }}">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
        
        <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
        

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
            });
        </script>
        @include('partials.theme-toggle-script')
        
        <!-- jQuery PRIMERO -->
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
        
        <!-- Bootstrap JS -->
        <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        
        <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
        @include('partials.notices')
        
        <!-- DataTables JS - versiones estables EN ORDEN CORRECTO -->
        <script src="{{ asset('vendor/datatables/js/dataTables.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
        
        <!-- DataTables Buttons - DESPUÉS del core -->
        <script src="{{ asset('vendor/datatables/js/dataTables.buttons.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/buttons.bootstrap5.min.js') }}"></script>
        
        <!-- Librerías para exportación -->
        <script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
        <script src="{{ asset('vendor/pdfmake/pdfmake.min.js') }}"></script>
        <script src="{{ asset('vendor/pdfmake/vfs_fonts.js') }}"></script>
        
        <!-- Botones de exportación -->
        <script src="{{ asset('vendor/datatables/js/buttons.html5.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/buttons.print.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/buttons.colVis.min.js') }}"></script>
        
        <!-- Responsive -->
        <script src="{{ asset('vendor/datatables/js/dataTables.responsive.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/responsive.bootstrap5.min.js') }}"></script>
        <script>
            if (window.DataTable) {
                const labels = DataTable.defaults.language.lengthLabels || {};
                labels['-1'] = 'Todos';
                DataTable.defaults.language.lengthLabels = labels;
            }
        </script>
        
        
        @stack('scripts')
        @livewireScripts
    </body>
</html>
