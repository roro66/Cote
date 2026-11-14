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
        <linearGradient id="{{ $id }}-mark" x1="14" y1="10" x2="58" y2="64" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#5eead4"/>
            <stop offset="0.5" stop-color="#14b8a6"/>
            <stop offset="1" stop-color="#1d4ed8"/>
        </linearGradient>
        <linearGradient id="{{ $id }}-snout" x1="28" y1="40" x2="44" y2="54" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ccfbf1"/>
            <stop offset="1" stop-color="#5eead4"/>
        </linearGradient>
        <linearGradient id="{{ $id }}-sheen" x1="18" y1="16" x2="42" y2="40" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#fff" stop-opacity="0.42"/>
            <stop offset="1" stop-color="#fff" stop-opacity="0"/>
        </linearGradient>
    </defs>

    {{-- Cerdito alcancia de frente (simetrico, 2 ojos) --}}
    <g class="cote-logo-pig">
        {{-- Cola --}}
        <path d="M13.5 40c-4.5-2-6.5 3.5-3.2 5.5 1.8 1.1 3.6-0.4 4.4-2" fill="none" stroke="url(#{{ $id }}-mark)" stroke-width="2.4" stroke-linecap="round"/>

        {{-- Orejas (detras del cuerpo) --}}
        <ellipse cx="21" cy="20" rx="7.5" ry="9" transform="rotate(-18 21 20)" fill="url(#{{ $id }}-mark)"/>
        <ellipse cx="51" cy="20" rx="7.5" ry="9" transform="rotate(18 51 20)" fill="url(#{{ $id }}-mark)"/>
        <ellipse cx="21.5" cy="21" rx="3.6" ry="4.4" transform="rotate(-18 21.5 21)" fill="#99f6e4" fill-opacity="0.55"/>
        <ellipse cx="50.5" cy="21" rx="3.6" ry="4.4" transform="rotate(18 50.5 21)" fill="#99f6e4" fill-opacity="0.55"/>

        {{-- Cuerpo --}}
        <ellipse cx="36" cy="40" rx="23" ry="20.5" fill="url(#{{ $id }}-mark)"/>
        <path d="M20 30c7-9 18-12 31-7" fill="none" stroke="url(#{{ $id }}-sheen)" stroke-width="5.5" stroke-linecap="round"/>

        {{-- Patas --}}
        <rect x="22" y="55" width="7" height="8" rx="3.2" fill="url(#{{ $id }}-mark)"/>
        <rect x="43" y="55" width="7" height="8" rx="3.2" fill="url(#{{ $id }}-mark)"/>
        <rect x="22" y="59.5" width="7" height="3.2" rx="1.5" fill="#0f766e" fill-opacity="0.28"/>
        <rect x="43" y="59.5" width="7" height="3.2" rx="1.5" fill="#0f766e" fill-opacity="0.28"/>

        {{-- Ranura --}}
        <rect x="28.5" y="22" width="15" height="3.4" rx="1.7" fill="#0f172a" fill-opacity="0.4"/>
        <rect x="29.7" y="22.6" width="12.6" height="1.1" rx="0.55" fill="#fff" fill-opacity="0.2"/>

        {{-- Ojos --}}
        <circle cx="27.5" cy="34" r="4.1" fill="#fff"/>
        <circle cx="44.5" cy="34" r="4.1" fill="#fff"/>
        <circle cx="28.3" cy="34.6" r="2.05" fill="#0f172a"/>
        <circle cx="45.3" cy="34.6" r="2.05" fill="#0f172a"/>
        <circle cx="29.1" cy="33.7" r="0.7" fill="#fff"/>
        <circle cx="46.1" cy="33.7" r="0.7" fill="#fff"/>

        {{-- Hocico centrado --}}
        <ellipse cx="36" cy="46.5" rx="10.2" ry="7.4" fill="url(#{{ $id }}-snout)"/>
        <ellipse cx="36" cy="46.5" rx="10.2" ry="7.4" fill="none" stroke="#0f766e" stroke-opacity="0.18" stroke-width="1"/>
        <ellipse cx="32.2" cy="46.5" rx="1.55" ry="2.05" fill="#0f766e" fill-opacity="0.78"/>
        <ellipse cx="39.8" cy="46.5" rx="1.55" ry="2.05" fill="#0f766e" fill-opacity="0.78"/>
    </g>

    {{-- Moneda girando sobre la ranura --}}
    <g class="cote-logo-coin" transform="translate(36 14.5)">
        <g class="cote-logo-coin-spin">
            <circle cx="0" cy="0" r="6.6" fill="#fbbf24"/>
            <circle cx="0" cy="0" r="4.2" fill="none" stroke="#92400e" stroke-width="1.1" opacity="0.45"/>
            <text x="0" y="2.9" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="7.8" font-weight="700" fill="#78350f">$</text>
        </g>
    </g>

    <g font-family="Helvetica Neue, Helvetica, Arial, sans-serif">
        <text x="78" y="38" font-size="28" font-weight="700" letter-spacing="0.5" fill="{{ $word }}">COTE</text>
        <text x="79" y="56" font-size="12" font-weight="500" letter-spacing="2.4" fill="{{ $sub }}">TESORERIA</text>
    </g>
</svg>
