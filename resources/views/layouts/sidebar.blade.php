{{-- Sidebar lateral colapsable estilo AdminLTE --}}
<aside id="sidebar"
       class="sidebar shadow-sm"
       :class="{ 'sidebar-collapsed': !open }"
       x-data="{
           open: (localStorage.getItem('sidebar-open') !== 'false'),
           toggle() {
               this.open = !this.open;
               localStorage.setItem('sidebar-open', this.open);
           }
       }"
       x-init="document.body.classList.toggle('sidebar-collapsed', !open); $watch('open', v => document.body.classList.toggle('sidebar-collapsed', !v))">
    {{-- Brand --}}
    <div class="sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-secondary">
        <a href="{{ route('dashboard') }}" class="sidebar-brand-link text-decoration-none text-white d-flex align-items-center overflow-hidden">
            <span class="sidebar-brand-icon me-2">
                <i class="fas fa-coins fa-lg"></i>
            </span>
            <span class="sidebar-brand-text text-nowrap" x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:leave="transition ease-in duration-150">COTE</span>
        </a>
        <button type="button" class="btn btn-link text-white p-0 border-0 d-none d-md-inline-block" @click="toggle()" title="Contraer/Expandir menú" aria-label="Toggle sidebar">
            <i class="fas fa-angle-double-left" x-show="open" x-cloak></i>
            <i class="fas fa-angle-double-right" x-show="!open" x-cloak style="display: none;"></i>
        </button>
    </div>

    {{-- Nav --}}
    <nav class="sidebar-nav mt-2">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Panel</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('people.index') }}" class="nav-link {{ request()->routeIs('people.*') ? 'active' : '' }}">
                    <i class="fas fa-users nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Personas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <i class="fas fa-wallet nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Cuentas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Transacciones</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Rendiciones</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('approvals.index') }}" class="nav-link {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
                    <i class="fas fa-check-circle nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Aprobaciones</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('statistics.index') }}" class="nav-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Estadísticas</span>
                </a>
            </li>
            <li class="sidebar-divider" x-show="open" x-transition></li>
            <li class="nav-header" x-show="open" x-transition><span>Configuración</span></li>
            <li class="nav-item">
                <a href="{{ route('expense-categories.index') }}" class="nav-link {{ request()->routeIs('expense-categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Categorías</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('banks.index') }}" class="nav-link {{ request()->routeIs('banks.*') ? 'active' : '' }}">
                    <i class="fas fa-university nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Bancos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('account-types.index') }}" class="nav-link {{ request()->routeIs('account-types.*') ? 'active' : '' }}">
                    <i class="fas fa-list nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Tipos de Cuenta</span>
                </a>
            </li>
            <li class="sidebar-divider" x-show="open" x-transition></li>
            <li class="nav-item">
                <a href="#" onclick="openMonthlyExpenseReport(); return false;" class="nav-link">
                    <i class="fas fa-file-alt nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Informes</span>
                </a>
            </li>
            @if(auth()->check() && (auth()->user()->hasRole('boss') || strtolower(auth()->user()->email) === 'admin@cote.com'))
            <li class="nav-item">
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fas fa-user-cog nav-icon"></i>
                    <span class="nav-text" x-show="open" x-transition>Usuarios</span>
                </a>
            </li>
            @endif
        </ul>
    </nav>

    {{-- Footer sidebar (opcional) --}}
    <div class="sidebar-footer mt-auto border-top border-secondary py-2 px-3" x-show="open" x-transition>
        <small class="text-white-50">COTE · Tesorería</small>
    </div>
</aside>
