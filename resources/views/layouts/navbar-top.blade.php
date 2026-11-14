{{-- Barra superior: toggle del sidebar (móvil) + título + tema + usuario --}}
<header class="navbar-top bg-white border-bottom shadow-sm sticky-top navbar-theme-aware">
    <div class="navbar-top-inner d-flex align-items-center justify-content-between w-100 px-3 py-2">
        <div class="navbar-top-start d-flex align-items-center">
            <button type="button" class="btn btn-link text-dark navbar-mobile-btn d-md-none me-2 p-0" id="sidebar-mobile-toggle" aria-label="Abrir menú">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            @isset($header)
                <div class="navbar-page-title d-none d-xl-block" title="{{ trim(preg_replace('/\s+/', ' ', strip_tags($header))) }}">
                    {{ $header }}
                </div>
            @else
                <span class="text-muted small d-none d-xl-inline">Panel</span>
            @endisset
        </div>

        @auth
            @php
                $quick = auth()->user()->hasAnyRole(['boss', 'treasurer'])
                    ? [
                        ['route' => route('cash.movement', 'entregar'), 'icon' => 'fa-hand-holding-usd', 'text' => 'Entregar dinero', 'short' => 'Entregar', 'color' => '#198754', 'active' => request()->is('caja/entregar')],
                        ['route' => route('cash.movement', 'devolucion'), 'icon' => 'fa-undo-alt', 'text' => 'Me devolvieron', 'short' => 'Devolución', 'color' => '#0f766e', 'active' => request()->is('caja/devolucion')],
                        ['route' => route('cash.movement', 'ingreso'), 'icon' => 'fa-piggy-bank', 'text' => 'Entró dinero', 'short' => 'Ingreso', 'color' => '#0d6efd', 'active' => request()->is('caja/ingreso')],
                        ['route' => route('expenses.intake'), 'icon' => 'fa-file-invoice-dollar', 'text' => 'Registrar rendición', 'short' => 'Rendición', 'color' => '#fd7e14', 'active' => request()->routeIs('expenses.intake*')],
                        ['route' => route('approvals.review'), 'icon' => 'fa-clipboard-check', 'text' => 'Revisar rendiciones', 'short' => 'Revisar', 'color' => '#b45309', 'active' => request()->routeIs('approvals.review'),
                            'badge' => \App\Models\Expense::where('status', 'submitted')->where('is_enabled', true)->count()],
                        ['route' => route('cashbook.index'), 'icon' => 'fa-book', 'text' => 'Libro de caja', 'short' => 'Libro', 'color' => '#6f42c1', 'active' => request()->routeIs('cashbook.*')],
                        ['route' => route('closings.index'), 'icon' => 'fa-calendar-check', 'text' => 'Cerrar mes', 'short' => 'Cierre', 'color' => '#495057', 'active' => request()->routeIs('closings.*')],
                    ]
                    : [
                        ['route' => route('expenses.create'), 'icon' => 'fa-plus-circle', 'text' => 'Nueva rendición', 'short' => 'Nueva', 'color' => '#198754', 'active' => request()->routeIs('expenses.create')],
                        ['route' => route('expenses.index'), 'icon' => 'fa-receipt', 'text' => 'Mis rendiciones', 'short' => 'Mis rendiciones', 'color' => '#0d6efd', 'active' => request()->routeIs('expenses.index')],
                    ];
                // Las rendiciones llegan en papel y se registran ya aprobadas: revisar solo aparece si alguien rindió desde la app
                $quick = array_values(array_filter($quick, fn ($item) => ! array_key_exists('badge', $item) || $item['badge'] > 0 || $item['active']));
                if (config('ai.enabled')) {
                    $quick[] = ['route' => route('assistant'), 'icon' => 'fa-robot', 'text' => 'Asistente', 'short' => 'Asistente', 'color' => '#5b4b8a', 'active' => request()->routeIs('assistant')];
                }
            @endphp
            <nav class="cote-quick-bar d-flex flex-nowrap align-items-center gap-1 mx-2 me-auto" data-labels="icons" aria-label="Accesos rápidos">
                @foreach ($quick as $item)
                    <a href="{{ $item['route'] }}" class="cote-quick-btn {{ $item['active'] ? 'is-active' : '' }}" style="--quick-color: {{ $item['color'] }};" title="{{ $item['text'] }}" aria-label="{{ $item['text'] }}">
                        <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span class="cote-quick-label cote-quick-label--short" aria-hidden="true">{{ $item['short'] }}</span>
                        <span class="cote-quick-label cote-quick-label--full" aria-hidden="true">{{ $item['text'] }}</span>
                        @if (! empty($item['badge']))
                            <span class="cote-quick-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endauth

        <div class="navbar-top-end d-flex align-items-center gap-2">
            <button type="button" class="btn btn-link text-dark navbar-theme-btn hover-bg-light rounded p-2" id="theme-toggle" data-theme-toggle aria-label="Cambiar tema (claro/oscuro)" title="Cambiar tema">
                <i class="fas fa-moon fa-lg theme-icon-light" aria-hidden="true"></i>
                <i class="fas fa-sun fa-lg theme-icon-dark d-none" aria-hidden="true"></i>
            </button>
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="btn btn-link text-dark navbar-user-btn text-decoration-none d-flex align-items-center py-1 px-2 rounded hover-bg-light" type="button">
                        <i class="fas fa-user-circle fa-lg me-1"></i>
                        <span class="d-none d-sm-inline">{{ Auth::user()->name }}</span>
                        <i class="fas fa-chevron-down small ms-1"></i>
                    </button>
                </x-slot>
                <x-slot name="content">
                    @role('boss')
                        <x-dropdown-link :href="route('users.index')">
                            {{ __('Administrar usuarios') }}
                        </x-dropdown-link>
                        <div class="border-top my-1"></div>
                    @endrole
                    <x-dropdown-link :href="route('profile.edit')">
                        {{ __('Perfil') }}
                    </x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Cerrar sesión') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
    <script>
        // Una sola fila: prueba nombre completo, corto o solo ícono, y oculta el título si no queda espacio digno
        (function () {
            const inner = document.currentScript.parentElement.querySelector('.navbar-top-inner');
            const nav = inner && inner.querySelector('.cote-quick-bar');
            if (!nav) return;
            const title = inner.querySelector('.navbar-page-title');
            const options = [['full', true], ['short', true], ['short', false], ['icons', true], ['icons', false]];
            const fit = () => {
                for (const [labels, withTitle] of options) {
                    nav.dataset.labels = labels;
                    inner.dataset.title = withTitle ? 'on' : 'off';
                    const titleOk = !withTitle || !title || title.offsetParent === null
                        || title.getBoundingClientRect().width >= Math.min(140, title.scrollWidth);
                    if (titleOk && nav.scrollWidth <= nav.clientWidth + 1 && inner.scrollWidth <= inner.clientWidth + 1) return;
                }
            };
            let frame = null;
            new ResizeObserver(() => { cancelAnimationFrame(frame); frame = requestAnimationFrame(fit); }).observe(inner);
            fit();
            document.fonts && document.fonts.ready.then(fit);
        })();
    </script>
</header>
