{{-- Barra superior: toggle del sidebar (móvil) + título + tema + usuario --}}
<header class="navbar-top bg-white border-bottom shadow-sm sticky-top navbar-theme-aware">
    <div class="d-flex align-items-center justify-content-between w-100 px-3 py-2">
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-link text-dark navbar-mobile-btn d-md-none me-2 p-0" id="sidebar-mobile-toggle" aria-label="Abrir menú">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            @isset($header)
                <div class="navbar-page-title">
                    {{ $header }}
                </div>
            @else
                <span class="text-muted small">Panel</span>
            @endisset
        </div>

        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-link text-dark navbar-theme-btn hover-bg-light rounded p-2" id="theme-toggle" aria-label="Cambiar tema (claro/oscuro)" title="Cambiar tema">
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
                    @if(auth()->check() && (auth()->user()->hasRole('boss') || strtolower(auth()->user()->email) === 'admin@cote.com'))
                        <x-dropdown-link :href="route('users.index')">
                            {{ __('Administrar usuarios') }}
                        </x-dropdown-link>
                        <div class="border-top my-1"></div>
                    @endif
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
</header>
