<?php

namespace App\Observers;

use App\Models\Person;
use Illuminate\Support\Facades\Log;

class PersonObserver
{
    /**
     * Handle the Person "creating" event.
     */
    public function creating(Person $person): void
    {
        // Log person creation
        Log::info('Creating person', [
            'rut' => $person->rut,
            'email' => $person->email,
            'role_type' => $person->role_type
        ]);
        
        // Ensure is_enabled has a default value
        if (is_null($person->is_enabled)) {
            $person->is_enabled = true;
        }
    }

    /**
     * Handle the Person "created" event.
     */
    public function created(Person $person): void
    {
        Log::info('Person created successfully', [
            'id' => $person->id,
            'full_name' => $person->full_name,
            'rut' => $person->rut
        ]);
    }

    /**
     * Handle the Person "updated" event.
     */
    public function updated(Person $person): void
    {
        $changes = $person->getChanges();
        
        if (!empty($changes)) {
            Log::info('Person updated', [
                'id' => $person->id,
                'full_name' => $person->full_name,
                'changes' => array_keys($changes)
            ]);
        }
    }

    /**
     * Handle the Person "deleted" event.
     */
    public function deleted(Person $person): void
    {
        Log::info('Person deleted', [
            'id' => $person->id,
            'full_name' => $person->full_name,
            'rut' => $person->rut
        ]);
    }

    /**
     * Handle the Person "restored" event.
     */
    public function restored(Person $person): void
    {
        Log::info('Person restored', [
            'id' => $person->id,
            'full_name' => $person->full_name
        ]);
    }

    /**
     * Handle the Person "force deleted" event.
     */
    public function forceDeleted(Person $person): void
    {
        Log::warning('Person force deleted', [
            'id' => $person->id,
            'rut' => $person->rut
        ]);
    }
}
