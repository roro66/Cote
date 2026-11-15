<?php

namespace App\Livewire\Concerns;

trait RestoresAutosavedDraft
{
    public function discardSavedData(): void
    {
        $this->dispatch('cote-toast', type: 'info', message: 'Se descartó el borrador autoguardado.');
    }

    protected function clearClientAutosave(): void
    {
        $this->dispatch('autosave:clear');
        $this->dispatch('autosave:clear-immediate');
    }

    /**
     * @param  array<string, mixed>  $savedData
     * @param  list<string>  $keys
     */
    protected function applyAutosavedProperties(array $savedData, array $keys): void
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $savedData)) {
                $this->{$key} = $savedData[$key];
            }
        }
    }

    protected function notifyAutosaveRestored(): void
    {
        $this->dispatch('cote-toast', type: 'success', message: 'Borrador restaurado.');
    }

    protected function notifyAutosaveRestoreFailed(): void
    {
        $this->dispatch('cote-toast', type: 'error', message: 'No se pudo restaurar el borrador.');
    }
}
