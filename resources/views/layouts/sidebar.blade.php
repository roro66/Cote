{{-- Sidebar lateral siempre visible (sin colapsar) --}}
<aside id="sidebar" class="sidebar shadow-sm">
    {{-- Brand: logo arriba del menú --}}
    <div class="sidebar-brand d-flex flex-column align-items-center border-bottom border-secondary">
        <div class="d-flex align-items-center justify-content-center w-100 px-2">
            <a href="{{ route('dashboard') }}" class="sidebar-brand-link text-decoration-none d-flex align-items-center overflow-hidden justify-content-center">
                <img src="{{ asset('assets/img/logo01.png') }}" alt="COTE" class="sidebar-logo img-fluid" />
            </a>
        </div>
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
            @hasanyrole('boss|treasurer')
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
                    <span class="nav-text">Transacciones</span>
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
