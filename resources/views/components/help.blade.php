@props(['text'])
<button type="button" class="btn btn-link p-0 align-baseline cote-help" data-bs-toggle="popover" data-bs-trigger="focus"
        data-bs-placement="top" data-bs-content="{{ $text }}" title="¿Qué es esto?" aria-label="¿Qué es esto? {{ $text }}">
    <i class="far fa-question-circle"></i>
</button>
