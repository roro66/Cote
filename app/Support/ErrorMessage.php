<?php

namespace App\Support;

use Throwable;

final class ErrorMessage
{
    /**
     * Mensaje seguro para mostrar al usuario: las reglas de negocio (\DomainException)
     * se muestran tal cual; cualquier otro error se registra y se oculta.
     */
    public static function for(Throwable $e, string $context): string
    {
        if ($e instanceof \DomainException) {
            return $e->getMessage();
        }

        report($e);

        return $context . '. Intenta de nuevo o contacta al administrador.';
    }
}
