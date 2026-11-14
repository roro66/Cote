<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ ['entregar' => 'Entregar dinero', 'devolucion' => 'Me devolvieron dinero', 'ingreso' => 'Entró dinero a la caja'][$mode] }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <livewire:cash-movement :mode="$mode"
                :persona="request()->integer('persona') ?: null"
                :monto="request()->query('monto')"
                :detalle="request()->query('detalle')" />
        </div>
    </div>
</x-app-layout>
