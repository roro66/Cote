<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.theme-init')

        <link rel="stylesheet" href="{{ asset('vendor/sweetalert2/sweetalert2.min.css') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <button type="button" data-theme-toggle class="guest-theme-toggle fixed top-4 right-4 z-10 rounded-full p-2 shadow"
                aria-label="Cambiar tema (claro/oscuro)" title="Cambiar tema">
            <svg class="theme-icon-light w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
            <svg class="theme-icon-dark w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4" fill="currentColor"/>
                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
            </svg>
        </button>

        <div class="guest-page min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100" style="background-image: url('{{ asset('assets/img/pattern-bills.svg') }}'); background-repeat: repeat; background-size: 160px 160px;">
            <div class="mb-4">
                <a href="/" class="guest-logo inline-block">
                    <img src="{{ asset('assets/img/logo01.png') }}" alt="{{ config('app.name') }}" class="h-auto max-w-full" style="width: 480px;" />
                </a>
            </div>

            <div class="guest-card w-full sm:max-w-md mt-6 px-6 py-4 bg-white/90 shadow-md overflow-hidden sm:rounded-lg" style="backdrop-filter: blur(6px);">
                {{ $slot }}
            </div>
        </div>

        <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
        @include('partials.notices')
        @include('partials.theme-toggle-script')
    </body>
</html>
