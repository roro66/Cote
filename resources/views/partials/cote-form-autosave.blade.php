{{--
  @include('partials.cote-form-autosave', [
      'formSlug' => 'expense-intake',
      'entityId' => $expenseId,
      'fields' => ['accountId', 'date', 'description', 'declaredTotal', 'rows', 'step'],
  ])
--}}
@once
    <script src="{{ asset('js/cote-form-autosave.js') }}"></script>
@endonce
<script>
(function () {
    if (typeof CoteFormAutoSave === 'undefined' || !CoteFormAutoSave.attach) {
        console.error('CoteFormAutoSave no cargó');
        return;
    }
    CoteFormAutoSave.attach({
        componentId: @json($this->getId()),
        userId: {{ auth()->check() ? (int) auth()->id() : 0 }},
        formSlug: @json($formSlug),
        entityId: @json($entityId ?? null),
        fields: @json($fields ?? []),
    });
})();
</script>
