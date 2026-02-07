<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Operador SQL para búsqueda case-insensitive: ILIKE en PostgreSQL, LIKE en SQLite/MySQL.
     * En SQLite LIKE es case-insensitive por defecto.
     */
    public static function likeOperator(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    /**
     * Expresión SQL para "columna LIKE valor" case-insensitive.
     * Para PostgreSQL: "column ILIKE ?", para otros: "LOWER(column) LIKE LOWER(?)".
     */
    public static function likeExpression(string $column): string
    {
        return DB::getDriverName() === 'pgsql'
            ? "{$column} ILIKE ?"
            : "LOWER({$column}) LIKE LOWER(?)";
    }

    /**
     * Expresión SQL para truncar una fecha al inicio del mes (agrupar por mes).
     * PostgreSQL: date_trunc('month', column), SQLite: date(column, 'start of month').
     */
    public static function monthTruncExpression(string $column): string
    {
        return DB::getDriverName() === 'pgsql'
            ? "date_trunc('month', {$column})"
            : "date({$column}, 'start of month')";
    }
}
