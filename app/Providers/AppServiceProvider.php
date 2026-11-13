<?php

namespace App\Providers;

use App\Models\Person;
use App\Observers\PersonObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Person::observe(PersonObserver::class);

        // Permisos del usuario de solo lectura del asistente sobre las tablas nuevas
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Database\Events\MigrationsEnded::class,
            fn () => app(\App\Services\Ai\ReadOnlyDatabaseRole::class)->sync()
        );
    }
}
