<?php

return [
    // Nombre configurable para la cuenta que actúa como proveedor de fondos.
    // Usar este valor en lugar de comparar literales dispersos en el código.
    'fondeo_account_name' => env('COTESO_FONDEO_ACCOUNT_NAME', 'Fondeo del Sistema'),
];
