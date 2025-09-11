<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Panel
                    </x-nav-link>
                    <x-nav-link :href="route('people.index')" :active="request()->routeIs('people.*')">
                        Personas
                    </x-nav-link>
                    <x-nav-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')">
                        Cuentas
                    </x-nav-link>
                    <x-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">
                        Transacciones
                    </x-nav-link>
                    <x-nav-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                        Rendiciones
                    </x-nav-link>
                    
                    <x-nav-link :href="route('approvals.index')" :active="request()->routeIs('approvals.*')">
                        Aprobaciones
                    </x-nav-link>
                    
                    <x-nav-link :href="route('banks.index')" :active="request()->routeIs('banks.*')">
                        Bancos
                    </x-nav-link>
                    <x-nav-link :href="route('expense-categories.index')" :active="request()->routeIs('expense-categories.*')">
                        Categorías
                    </x-nav-link>
                    
                    <x-nav-link :href="route('account-types.index')" :active="request()->routeIs('account-types.*')">
                        Tipos de Cuenta
                    </x-nav-link>
                    <x-nav-link :href="route('statistics.index')" :active="request()->routeIs('statistics.*')">
                        Estadísticas
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:space-x-3">
                <!-- Dark Mode Toggle -->
                <button 
                    id="theme-toggle"
                    onclick="toggleTheme()"
                    class="inline-flex items-center p-2 rounded-md text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 transition ease-in-out duration-150"
                    title="Cambiar tema"
                >
                    <!-- Sol (modo claro) -->
                    <svg id="sun-icon" class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.464 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                    <!-- Luna (modo oscuro) -->
                    <svg id="moon-icon" class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                <script>
                    // Sync sun/moon icon visibility on load
                    (function(){
                        try {
                            const html = document.documentElement;
                            const isDark = html.getAttribute('data-bs-theme') === 'dark' || html.classList.contains('dark');
                            const sun = document.getElementById('sun-icon');
                            const moon = document.getElementById('moon-icon');
                            if (sun && moon) {
                                if (isDark) { sun.classList.add('hidden'); sun.classList.remove('block'); moon.classList.remove('hidden'); moon.classList.add('block'); }
                                else { moon.classList.add('hidden'); moon.classList.remove('block'); sun.classList.remove('hidden'); sun.classList.add('block'); }
                            }
                        } catch(e){}
                    })();
                </script>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if(auth()->check() && (auth()->user()->hasRole('boss') || strtolower(auth()->user()->email) === 'admin@coteso.com'))
                            <x-dropdown-link :href="route('users.index')">
                                {{ __('Administrar usuarios') }}
                            </x-dropdown-link>
                            <div class="border-t my-1"></div>
                        @endif
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Panel
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('people.index')" :active="request()->routeIs('people.*')">
                Personas
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('accounts.index')" :active="request()->routeIs('accounts.*')">
                Cuentas
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">
                Transacciones
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                Rendiciones
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('approvals.index')" :active="request()->routeIs('approvals.*')">
                Aprobaciones
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('banks.index')" :active="request()->routeIs('banks.*')">
                Bancos
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('expense-categories.index')" :active="request()->routeIs('expense-categories.*')">
                Categorías
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('account-types.index')" :active="request()->routeIs('account-types.*')">
                Tipos de Cuenta
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('statistics.index')" :active="request()->routeIs('statistics.*')">
                Estadísticas
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if(auth()->check() && (auth()->user()->hasRole('boss') || strtolower(auth()->user()->email) === 'admin@coteso.com'))
                    <x-responsive-nav-link :href="route('users.index')">
                        {{ __('Administrar usuarios') }}
                    </x-responsive-nav-link>
                @endif
                <!-- Dark Mode Toggle Mobile -->
                <button 
                    id="mobile-theme-toggle"
                    onclick="toggleTheme()"
                    class="w-full text-left block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                >
                    <span class="flex items-center">
                        <!-- Sol (modo claro) -->
                        <svg class="w-5 h-5 mr-2 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.464 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                        </svg>
                        <!-- Luna (modo oscuro) -->
                        <svg class="w-5 h-5 mr-2 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                        </svg>
                        <span class="dark:hidden">Cambiar a modo oscuro</span>
                        <span class="hidden dark:block">Cambiar a modo claro</span>
                    </span>
                </button>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
