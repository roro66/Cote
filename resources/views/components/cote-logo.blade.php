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
        <linearGradient id="{{ $id }}-body" x1="14" y1="12" x2="58" y2="62" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#5eead4"/>
            <stop offset="0.55" stop-color="#14b8a6"/>
            <stop offset="1" stop-color="#1d4ed8"/>
        </linearGradient>
        <linearGradient id="{{ $id }}-snout" x1="36" y1="40" x2="36" y2="56" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ecfeff"/>
            <stop offset="1" stop-color="#99f6e4"/>
        </linearGradient>
        <radialGradient id="{{ $id }}-shine" cx="28" cy="28" r="22" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#fff" stop-opacity="0.35"/>
            <stop offset="1" stop-color="#fff" stop-opacity="0"/>
        </radialGradient>
    </defs>

    {{-- Alcancia de frente, simetrica --}}
    <g class="cote-logo-pig">
        {{-- Orejas --}}
        <path fill="url(#{{ $id }}-body)" d="M18 28c-1-12 10-18 16-12 1 4-2 9-6 11-4 1-8 2-10 1z"/>
        <path fill="url(#{{ $id }}-body)" d="M54 28c1-12-10-18-16-12-1 4 2 9 6 11 4 1 8 2 10 1z"/>
        <path fill="#a5f3fc" fill-opacity="0.7" d="M21 27c0-6 5-9 8-6 0 3-2 6-4 7-2 1-4 1-4-1z"/>
        <path fill="#a5f3fc" fill-opacity="0.7" d="M51 27c0-6-5-9-8-6 0 3 2 6 4 7 2 1 4 1 4-1z"/>

        {{-- Cuerpo --}}
        <circle cx="36" cy="40" r="21" fill="url(#{{ $id }}-body)"/>
        <circle cx="36" cy="40" r="21" fill="url(#{{ $id }}-shine)"/>

        {{-- Patas --}}
        <rect x="23" y="56" width="8" height="7.5" rx="3.5" fill="url(#{{ $id }}-body)"/>
        <rect x="41" y="56" width="8" height="7.5" rx="3.5" fill="url(#{{ $id }}-body)"/>

        {{-- Ranura --}}
        <rect x="29" y="23.5" width="14" height="3.2" rx="1.6" fill="#0f172a" fill-opacity="0.42"/>

        {{-- Ojos (ambos, bien separados) --}}
        <circle cx="27" cy="35" r="4.4" fill="#fff"/>
        <circle cx="45" cy="35" r="4.4" fill="#fff"/>
        <circle cx="27.7" cy="35.6" r="2.15" fill="#0f172a"/>
        <circle cx="45.7" cy="35.6" r="2.15" fill="#0f172a"/>
        <circle cx="28.6" cy="34.7" r="0.75" fill="#fff"/>
        <circle cx="46.6" cy="34.7" r="0.75" fill="#fff"/>

        {{-- Hocico centrado y simetrico --}}
        <ellipse cx="36" cy="47.2" rx="11" ry="7.8" fill="url(#{{ $id }}-snout)"/>
        <ellipse cx="36" cy="47.2" rx="11" ry="7.8" fill="none" stroke="#0f766e" stroke-opacity="0.22" stroke-width="1.1"/>
        <ellipse cx="32" cy="47.2" rx="1.7" ry="2.3" fill="#0f766e" fill-opacity="0.8"/>
        <ellipse cx="40" cy="47.2" rx="1.7" ry="2.3" fill="#0f766e" fill-opacity="0.8"/>
    </g>

    {{-- Moneda sobre la ranura --}}
    <g class="cote-logo-coin" transform="translate(36 15)">
        <g class="cote-logo-coin-spin">
            <circle cx="0" cy="0" r="6.4" fill="#fbbf24"/>
            <circle cx="0" cy="0" r="4.1" fill="none" stroke="#92400e" stroke-width="1.1" opacity="0.45"/>
            <text x="0" y="2.85" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="7.6" font-weight="700" fill="#78350f">$</text>
        </g>
    </g>

    <g font-family="Helvetica Neue, Helvetica, Arial, sans-serif">
        <text x="78" y="38" font-size="28" font-weight="700" letter-spacing="0.5" fill="{{ $word }}">COTE</text>
        <text x="79" y="56" font-size="12" font-weight="500" letter-spacing="2.4" fill="{{ $sub }}">TESORERIA</text>
    </g>
</svg>
