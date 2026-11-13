<?php

return [
    /*
    | Asistente de COTE: responde cómo se usa la app (manual) y preguntas sobre los datos.
    | Usa Gemini (Google AI). Salen la pregunta y las filas que devuelve cada consulta (con tope);
    | nunca RUT, correos, teléfonos, direcciones ni cuentas bancarias. Todo es de solo lectura.
    */
    'enabled' => (bool) env('AI_ASSISTANT_ENABLED', false),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
        // Si el modelo responde 503/429/500 o no conecta, se prueba el siguiente.
        'fallback_models' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'GEMINI_FALLBACK_MODELS',
            'gemini-3.6-flash,gemini-3.1-flash-lite,gemini-3.5-flash-lite'
        ))))),
        // Vueltas completas por la lista de modelos antes de rendirse, con una pausa entre vueltas.
        'retry_rounds' => (int) env('GEMINI_RETRY_ROUNDS', 2),
        'retry_wait_ms' => (int) env('GEMINI_RETRY_WAIT_MS', 2500),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 45),
        // Razonamiento interno de Gemini 3 (low, medium, high). Vacío deja el del modelo.
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'low'),
    ],

    // Rondas máximas modelo → consulta → modelo antes de cortar.
    'max_tool_rounds' => 5,

    // Filas máximas que se le entregan al modelo por consulta.
    'max_rows_to_model' => 40,

    // Tope del SELECT libre y tiempo máximo en PostgreSQL.
    'max_sql_rows' => 200,
    'sql_timeout_ms' => 8000,

    // El SELECT libre corre con un usuario de PostgreSQL de solo lectura (ReadOnlyDatabaseRole).
    // Sin PostgreSQL (desarrollo con SQLite) se usa la conexión normal dentro de una transacción que se revierte.
    'sql_connection' => env('AI_SQL_CONNECTION', 'pgsql_ai'),
    'sql_role' => env('AI_DB_USERNAME', 'coteso_ai'),
];
