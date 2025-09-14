<!-- Inline SVG logo so CSS classes passed via $attributes control sizing and color reliably -->
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 200" role="img" aria-label="COTESO logo" {{ $attributes->merge(['class' => 'block']) }}>
	<defs>
		<linearGradient id="g" x1="0" x2="1">
			<stop offset="0" stop-color="#0ea5a4"/>
			<stop offset="1" stop-color="#2563eb"/>
		</linearGradient>
	</defs>
	<rect width="100%" height="100%" fill="none"/>
	<!-- icon: stylized C and coin -->
	<g transform="translate(20,20)">
		<circle cx="60" cy="60" r="48" fill="url(#g)"/>
		<path d="M40 60a20 20 0 0 1 40 0 20 20 0 0 1-40 0z" fill="rgba(255,255,255,0.9)" />
		<path d="M78 50a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2" stroke="rgba(255,255,255,0.9)" stroke-width="3" fill="none" stroke-linecap="round"/>
	</g>
	<g transform="translate(140,60)">
		<text x="0" y="12" font-family="Helvetica, Arial, sans-serif" font-size="48" fill="currentColor" font-weight="700">COTESO</text>
		<text x="0" y="44" font-family="Helvetica, Arial, sans-serif" font-size="18" fill="currentColor" opacity="0.65">Tesorería</text>
	</g>
</svg>
