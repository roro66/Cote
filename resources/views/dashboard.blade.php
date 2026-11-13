<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel de Tesorería
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(config('ai.enabled'))
                @php
                    $assistantQuestions = auth()->user()->hasAnyRole(['boss', 'treasurer'])
                        ? ['¿Qué está pendiente de aprobar?', '¿Quiénes tienen dinero por rendir?', '¿A quiénes les debemos dinero?']
                        : ['¿Cuánto tengo por rendir?', '¿En qué estado están mis rendiciones?', '¿Cómo creo una rendición?'];
                @endphp
                <div class="bg-white shadow rounded-lg mb-6 p-4 d-flex flex-wrap align-items-center gap-2">
                    <span class="me-2"><i class="fas fa-robot text-primary"></i> <strong>Pregúntale al asistente:</strong></span>
                    @foreach($assistantQuestions as $assistantQuestion)
                        <a href="{{ route('assistant', ['q' => $assistantQuestion]) }}" class="btn btn-outline-primary btn-sm">{{ $assistantQuestion }}</a>
                    @endforeach
                </div>
            @endif
            <livewire:dashboard-summary />
        </div>
    </div>
</x-app-layout>
