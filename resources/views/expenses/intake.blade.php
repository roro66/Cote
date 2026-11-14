<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $expense ? 'Corregir rendición ' . $expense->expense_number : 'Registrar rendición' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:expense-intake :expense="$expense" :persona="request()->integer('persona') ?: null" />
        </div>
    </div>
</x-app-layout>
