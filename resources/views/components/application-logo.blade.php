<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 72" role="img" aria-label="COTE Tesorería" {{ $attributes->merge(['class' => 'block']) }}>
	<defs>
		<linearGradient id="cote-mark-{{ $id = uniqid() }}" x1="8" y1="6" x2="60" y2="66" gradientUnits="userSpaceOnUse">
			<stop offset="0" stop-color="#2dd4bf"/>
			<stop offset="0.55" stop-color="#0d9488"/>
			<stop offset="1" stop-color="#1d4ed8"/>
		</linearGradient>
		<linearGradient id="cote-sheen-{{ $id }}" x1="12" y1="10" x2="48" y2="28" gradientUnits="userSpaceOnUse">
			<stop offset="0" stop-color="#fff" stop-opacity="0.35"/>
			<stop offset="1" stop-color="#fff" stop-opacity="0"/>
		</linearGradient>
	</defs>

	<rect x="2" y="6" width="60" height="60" rx="16" fill="url(#cote-mark-{{ $id }})"/>
	<path d="M10 18c8-8 22-10 34-2" fill="none" stroke="url(#cote-sheen-{{ $id }})" stroke-width="6" stroke-linecap="round"/>
	<path d="M48 26.5c-2.2-5.8-8.2-9.8-15.2-9.8-9.1 0-16.5 7.2-16.5 19.3s7.4 19.3 16.5 19.3c7 0 13-4 15.2-9.8"
		fill="none" stroke="#fff" stroke-width="6.2" stroke-linecap="round"/>
	<circle cx="40.5" cy="36" r="7.2" fill="#fbbf24"/>
	<circle cx="40.5" cy="36" r="4.6" fill="none" stroke="#92400e" stroke-width="1.2" opacity="0.45"/>
	<text x="40.5" y="39.2" text-anchor="middle" font-family="Figtree, Helvetica, Arial, sans-serif"
		font-size="8.5" font-weight="700" fill="#78350f">$</text>

	<g fill="currentColor" font-family="Figtree, Helvetica Neue, Helvetica, Arial, sans-serif">
		<text x="78" y="38" font-size="28" font-weight="700" letter-spacing="0.5">COTE</text>
		<text x="79" y="56" font-size="12" font-weight="500" letter-spacing="2.4" opacity="0.55">TESORERÍA</text>
	</g>
</svg>
