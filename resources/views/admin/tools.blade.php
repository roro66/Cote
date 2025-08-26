<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Herramientas de Administración
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="h5 mb-3">Normalización de Transacciones Legacy</h3>

                    @if (session('status'))
                        <div class="alert alert-info">{{ session('status') }}</div>
                    @endif

                    @if (session('output'))
                        <pre class="bg-dark text-light p-3 rounded" style="white-space: pre-wrap">{{ session('output') }}</pre>
                    @endif

                    <form method="POST" action="{{ route('admin.tools.normalize') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="dry_run" value="1">
                        <button type="submit" class="btn btn-outline-primary">Dry-run</button>
                    </form>

                    <form method="POST" action="{{ route('admin.tools.normalize') }}" class="d-inline ms-2"
                        onsubmit="return confirm('¿Aplicar normalización definitivamente?');">
                        @csrf
                        <input type="hidden" name="dry_run" value="0">
                        <button type="submit" class="btn btn-primary">Aplicar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
