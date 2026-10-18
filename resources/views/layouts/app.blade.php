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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
        
        <!-- DataTables CSS - versiones estables -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" integrity="sha384-ok3J6xA9oQqai5C9ytYveFsBeKgoGk4T+NExsr6hoIKjZdv9SJcmx2mafwUWRNf9" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" integrity="sha384-ElnGX1+feu5KEucSXJLJE9peLXQ3kZDXmq9a2q6nS26zB8zJ7j6ZlljCktlrgsUx" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" integrity="sha384-jj44VXG857zuSsNQ7iqJihzOcCFRhs3qS4oLwyc4Hg+P9WjpwyR6T1ulnFKzhhaQ" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.datatables.net/colreorder/1.7.0/css/colReorder.bootstrap5.min.css" integrity="sha384-4Dbetv92yZvbtJgLxoVr1+212thg1GEstqR/f2wef3W5Wj9VCb1aEdudXltW27fP" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css" integrity="sha384-BQuA/IRHdZd4G0fkajPKOBOE6lIuKmN2G95L52+ULcI1T/NGKY+gWsB/qDn6xxv7" crossorigin="anonymous">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha384-iw3OoTErCYJJB9mCa8LNS2hbsQ7M3C0EpIsO/H5+EGAkPGc6rk+V8i04oW/K5xq0" crossorigin="anonymous">
        
        <!-- Toastr CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" integrity="sha384-YzEqZ2pBV0i9OmlTyoz75PqwTR8If8GsXBv7HLQclEVqIC3VxIt98/U94ES6CJTR" crossorigin="anonymous">
        

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
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
        
        <!-- Toastr para notificaciones -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js" integrity="sha384-VDls8ImYGI8SwVxpmjX2Bn27U2TcNodzTNROTusVEWO55+lmL+H9NczoQJk6mwZR" crossorigin="anonymous"></script>
        
        <!-- DataTables JS - versiones estables EN ORDEN CORRECTO -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js" integrity="sha384-cjmdOgDzOE22dUheI5E6Gzd3upfmReW8N1y/4jwKQE50KYcvFKZJA9JxWgQOzqwQ" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js" integrity="sha384-PgPBH0hy6DTJwu7pTf6bkRqPlf/+pjUBExpr/eIfzszlGYFlF9Wi9VTAJODPhgCO" crossorigin="anonymous"></script>
        
        <!-- DataTables Buttons - DESPUÉS del core -->
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js" integrity="sha384-D7Ifmm6VZVyjXndPxbvw6UXiK3Vixw1lgK5y+0pVVaQvzHPCpx8atng0IjL5wdcQ" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js" integrity="sha384-8Fm9OFhJ1epvcmDiJTZ2SFHHZoCp/xJ8Ld7wG7/aUwGni32fG7LIhsNTFfEtUaSv" crossorigin="anonymous"></script>
        
        <!-- Librerías para exportación -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" integrity="sha384-+mbV2IY1Zk/X1p/nWllGySJSUN8uMs+gUAN10Or95UBH0fpj6GfKgPmgC5EXieXG" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" integrity="sha384-VFQrHzqBh5qiJIU0uGU5CIW3+OWpdGGJM9LBnGbuIH2mkICcFZ7lPd/AAtI7SNf7" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" integrity="sha384-/RlQG9uf0M2vcTw3CX7fbqgbj/h8wKxw7C3zu9/GxcBPRKOEcESxaxufwRXqzq6n" crossorigin="anonymous"></script>
        
        <!-- Botones de exportación -->
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js" integrity="sha384-On4D3nN2gGDU45v05twd+TTLH8PL+2gbsGVpnKEpSfv7pDd1pgEJxxc4k7iWeg9d" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js" integrity="sha384-f5wX6gS38xMVGrwiXWKR7VijcBONISoTGfHF3N7yTwGrmvHaMHB4lq+VKxeZID10" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js" integrity="sha384-dyI79OT6UE9qWK4x3HAQSilUPj6TfmkE8pX6KeBTyrhOaNrk7cbvUZUDMuC8S7/k" crossorigin="anonymous"></script>
        
        <!-- Responsive -->
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js" integrity="sha384-VUnyCeQcqiiTlSM4AISHjJWKgLSM5VSyOeipcD9S/ybCKR3OhChZrPPjjrLfVV0y" crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js" integrity="sha384-T6YQaHyTPTbybQQV23jtlugHCneQYjePXdcEU+KMWGQY8EUQygBW9pRx0zpSU0/i" crossorigin="anonymous"></script>
        
        
        @stack('scripts')
        @livewireScripts
    </body>
</html>
