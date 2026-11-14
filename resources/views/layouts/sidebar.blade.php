{{-- Sidebar lateral siempre visible (sin colapsar) --}}
<aside id="sidebar" class="sidebar shadow-sm">
    {{-- Brand: logo arriba del menú (SVG inline: evita fallos de encoding al servir .svg como <img>) --}}
    <div class="sidebar-brand border-bottom border-secondary">
        <a href="{{ route('dashboard') }}" class="sidebar-brand-link text-decoration-none" title="Ir al panel" aria-label="COTE Tesorería">
            <svg class="sidebar-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 72" role="img" aria-hidden="true" width="220" height="56">
                <defs>
                    <linearGradient id="sidebar-cote-mark" x1="8" y1="6" x2="60" y2="66" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="#2dd4bf"/>
                        <stop offset="0.55" stop-color="#0d9488"/>
                        <stop offset="1" stop-color="#1d4ed8"/>
                    </linearGradient>
                    <linearGradient id="sidebar-cote-sheen" x1="12" y1="10" x2="48" y2="28" gradientUnits="userSpaceOnUse">
                        <stop offset="0" stop-color="#fff" stop-opacity="0.35"/>
                        <stop offset="1" stop-color="#fff" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <rect x="2" y="6" width="60" height="60" rx="16" fill="url(#sidebar-cote-mark)"/>
                <path d="M10 18c8-8 22-10 34-2" fill="none" stroke="url(#sidebar-cote-sheen)" stroke-width="6" stroke-linecap="round"/>
                <path d="M48 26.5c-2.2-5.8-8.2-9.8-15.2-9.8-9.1 0-16.5 7.2-16.5 19.3s7.4 19.3 16.5 19.3c7 0 13-4 15.2-9.8" fill="none" stroke="#fff" stroke-width="6.2" stroke-linecap="round"/>
                <circle cx="40.5" cy="36" r="7.2" fill="#fbbf24"/>
                <circle cx="40.5" cy="36" r="4.6" fill="none" stroke="#92400e" stroke-width="1.2" opacity="0.45"/>
                <text x="40.5" y="39.2" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="8.5" font-weight="700" fill="#78350f">$</text>
                <g fill="#f8fafc" font-family="Helvetica Neue, Helvetica, Arial, sans-serif">
                    <text x="78" y="38" font-size="28" font-weight="700" letter-spacing="0.5">COTE</text>
                    <text x="79" y="56" font-size="12" font-weight="500" letter-spacing="2.4" fill="#94a3b8">TESORERIA</text>
                </g>
            </svg>
        </a>
    </div>

    {{-- Nav --}}
    <nav class="sidebar-nav mt-2">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt nav-icon nav-icon-panel"></i>
                    <span class="nav-text">Panel</span>
                </a>
            </li>
            @if(config('ai.enabled'))
            <li class="nav-item">
                <a href="{{ route('assistant') }}" class="nav-link {{ request()->routeIs('assistant') ? 'active' : '' }}">
                    <i class="fas fa-robot nav-icon nav-icon-assistant"></i>
                    <span class="nav-text">Asistente</span>
                </a>
            </li>
            @endif
            @hasanyrole('boss|treasurer')
            <li class="nav-item">
                <a href="{{ route('workers.index') }}" class="nav-link {{ request()->routeIs('workers.*') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-usd nav-icon nav-icon-workers"></i>
                    <span class="nav-text">Trabajadores</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('cashbook.index') }}" class="nav-link {{ request()->routeIs('cashbook.*') ? 'active' : '' }}">
                    <i class="fas fa-book nav-icon nav-icon-cashbook"></i>
                    <span class="nav-text">Libro de caja</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('people.index') }}" class="nav-link {{ request()->routeIs('people.*') ? 'active' : '' }}">
                    <i class="fas fa-users nav-icon nav-icon-people"></i>
                    <span class="nav-text">Personas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet nav-icon nav-icon-accounts"></i>
                    <span class="nav-text">Cuentas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt nav-icon nav-icon-transactions"></i>
                    <span class="nav-text">Movimientos</span>
                </a>
            </li>
            @endhasanyrole
            <li class="nav-item">
                <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar nav-icon nav-icon-expenses"></i>
                    <span class="nav-text">Rendiciones</span>
                </a>
            </li>
            @hasanyrole('boss|treasurer')
            <li class="nav-item">
                <a href="{{ route('approvals.index') }}" class="nav-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
                    <i class="fas fa-check-circle nav-icon nav-icon-approvals"></i>
                    <span class="nav-text">Aprobaciones</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('statistics.index') }}" class="nav-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar nav-icon nav-icon-statistics"></i>
                    <span class="nav-text">Estadísticas</span>
                </a>
            </li>
            <li class="sidebar-divider"></li>
            <li class="nav-header"><span>Configuración</span></li>
            <li class="nav-item">
                <a href="{{ route('expense-categories.index') }}" class="nav-link {{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags nav-icon nav-icon-categories"></i>
                    <span class="nav-text">Categorías</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('banks.index') }}" class="nav-link {{ request()->routeIs('banks.*') ? 'active' : '' }}">
                    <i class="fas fa-university nav-icon nav-icon-banks"></i>
                    <span class="nav-text">Bancos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('account-types.index') }}" class="nav-link {{ request()->routeIs('account-types.*') ? 'active' : '' }}">
                    <i class="fas fa-list nav-icon nav-icon-account-types"></i>
                    <span class="nav-text">Tipos de Cuenta</span>
                </a>
            </li>
            <li class="sidebar-divider"></li>
            <li class="nav-item">
                <a href="{{ route('reports.index', ['openModal' => 'monthlyExpense']) }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt nav-icon nav-icon-reports"></i>
                    <span class="nav-text">Informes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('closings.index') }}" class="nav-link {{ request()->routeIs('closings.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check nav-icon nav-icon-closings"></i>
                    <span class="nav-text">Cierre de mes</span>
                </a>
            </li>
            @endhasanyrole
            @role('boss')
            <li class="nav-item">
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fas fa-user-cog nav-icon nav-icon-users"></i>
                    <span class="nav-text">Usuarios</span>
                </a>
            </li>
            @endrole
        </ul>
    </nav>

    {{-- Footer sidebar --}}
    <div class="sidebar-footer mt-auto border-top border-secondary py-2 px-3">
        <small class="sidebar-footer-text">COTE · Tesorería</small>
    </div>
</aside>
