@props([
    'variant' => 'dark', // dark = texto claro (sidebar / login); light = texto oscuro
])

@php
    $id = 'cote-' . str_replace('.', '', uniqid('', true));
    $word = $variant === 'light' ? '#0f172a' : '#f8fafc';
    $sub = $variant === 'light' ? '#64748b' : '#94a3b8';
@endphp

<svg {{ $attributes->merge(['class' => 'cote-logo-svg']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 72" role="img" aria-label="COTE Tesoreria" width="280" height="72">
    <defs>
        <linearGradient id="{{ $id }}-mark" x1="10" y1="8" x2="62" y2="64" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#5eead4"/>
            <stop offset="0.45" stop-color="#14b8a6"/>
            <stop offset="1" stop-color="#1d4ed8"/>
        </linearGradient>
        <linearGradient id="{{ $id }}-snout" x1="54" y1="30" x2="66" y2="44" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#99f6e4"/>
            <stop offset="1" stop-color="#2dd4bf"/>
        </linearGradient>
        <linearGradient id="{{ $id }}-sheen" x1="14" y1="14" x2="44" y2="36" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#fff" stop-opacity="0.4"/>
            <stop offset="1" stop-color="#fff" stop-opacity="0"/>
        </linearGradient>
    </defs>

    {{-- Cerdito alcancia (reemplaza el cuadrado redondeado) --}}
    <g class="cote-logo-pig">
        {{-- Cola --}}
        <path d="M15 38c-5-1-7 4-4 6 2 1 4-1 5-3" fill="none" stroke="url(#{{ $id }}-mark)" stroke-width="2.6" stroke-linecap="round"/>

        {{-- Cuerpo + cabeza --}}
        <path fill="url(#{{ $id }}-mark)" d="
            M18 40
            C18 27 26 19 38 18
            C43 12 52 13 54 20
            C60 22 65 28 64 36
            C66 44 61 51 54 53
            L51 62 46 54
            L36 56 32 62 29 54
            C21 52 17 47 18 40
            Z"/>

        {{-- Oreja --}}
        <path fill="url(#{{ $id }}-mark)" d="M44 20c3-9 13-11 15-3-7 1-11 5-15 3z"/>
        <path fill="#fff" fill-opacity="0.22" d="M46 19c2-5 8-7 10-2-5 0-8 3-10 2z"/>

        {{-- Brillo del cuerpo --}}
        <path d="M24 28c6-7 16-10 28-6" fill="none" stroke="url(#{{ $id }}-sheen)" stroke-width="5" stroke-linecap="round"/>

        {{-- Hocico --}}
        <ellipse cx="61.5" cy="37" rx="7.2" ry="5.8" fill="url(#{{ $id }}-snout)"/>
        <ellipse cx="59.4" cy="36.2" rx="1.15" ry="1.45" fill="#0f766e" fill-opacity="0.75"/>
        <ellipse cx="63.6" cy="38" rx="1.15" ry="1.45" fill="#0f766e" fill-opacity="0.75"/>

        {{-- Ojo --}}
        <circle cx="49.5" cy="30.5" r="2.35" fill="#fff"/>
        <circle cx="50.1" cy="30.9" r="1.15" fill="#0f172a"/>
        <circle cx="50.6" cy="30.4" r="0.35" fill="#fff"/>

        {{-- Ranura de monedas --}}
        <rect x="29" y="21.5" width="15" height="3.2" rx="1.6" fill="#0f172a" fill-opacity="0.38"/>
        <rect x="30.2" y="22.1" width="12.6" height="1.1" rx="0.55" fill="#fff" fill-opacity="0.18"/>
    </g>

    {{-- Moneda girando sobre la ranura --}}
    <g class="cote-logo-coin" transform="translate(36.5 16.5)">
        <g class="cote-logo-coin-spin">
            <circle cx="0" cy="0" r="7" fill="#fbbf24"/>
            <circle cx="0" cy="0" r="4.5" fill="none" stroke="#92400e" stroke-width="1.15" opacity="0.45"/>
            <text x="0" y="3.1" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="8.2" font-weight="700" fill="#78350f">$</text>
        </g>
    </g>

    <g font-family="Helvetica Neue, Helvetica, Arial, sans-serif">
        <text x="78" y="38" font-size="28" font-weight="700" letter-spacing="0.5" fill="{{ $word }}">COTE</text>
        <text x="79" y="56" font-size="12" font-weight="500" letter-spacing="2.4" fill="{{ $sub }}">TESORERIA</text>
    </g>
</svg>
