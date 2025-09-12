Pruebas (tests) — Cómo ejecutarlas localmente usando Sail

Resumen rápido

Este proyecto usa Pest para tests. En desarrollo recomendamos ejecutar los tests dentro del contenedor de Sail (evita depender de PHP en el host).

Comandos útiles

- Ejecutar toda la suite (dentro de Sail):

```bash
./vendor/bin/sail exec laravel.test php ./vendor/bin/pest
```

- Ejecutar un test específico (por nombre de clase/spec):

```bash
./vendor/bin/sail exec laravel.test php ./vendor/bin/pest --filter ExpenseApprovalTest
```

- Ejecutar un test por descripción (texto del it):

```bash
./vendor/bin/sail exec laravel.test php ./vendor/bin/pest --filter "allows a treasurer to approve an expense and debits account balance"
```

- Listar tests descubiertos (útil para encontrar el identificador):

```bash
./vendor/bin/sail exec laravel.test php ./vendor/bin/pest --list-tests
```

- Ejecutar un archivo de test concreto:

```bash
./vendor/bin/sail exec laravel.test php ./vendor/bin/pest tests/Feature/ExpenseApprovalTest.php
```

Notas y buenas prácticas

- Preferir Pest (no ejecutar phpunit directamente) ya que este proyecto usa Pest como runner principal.
- La suite está configurada para usar la trait `RefreshDatabase` en `tests/Pest.php`, por lo que cada test ejecuta migraciones en memoria/DB configurada. No es necesario llamar a `artisan migrate` manualmente antes de cada test.
- Si ves errores de conexión a la base de datos en la máquina host, ejecuta los tests dentro de Sail tal como se muestra arriba.

Salida y flags útiles

- Para CI, evita colores y usa `--colors=never`:

```bash
./vendor/bin/sail exec laravel.test php ./vendor/bin/pest --colors=never --filter ExpenseApprovalTest
```

- Para depuración rápida, captura el código de salida:

```bash
./vendor/bin/sail exec laravel.test bash -lc "php ./vendor/bin/pest --filter ExpenseApprovalTest; echo EXIT:\$?"
```

Helpers y utilidades de test

- Helpers añadidos: `tests/Helpers/TestHelpers.php` con funciones útiles:
  - `create_role($name)`
  - `create_treasurer_user($attrs = [])`
  - `create_person_with_account($personAttrs = [], $accountAttrs = [])`

Problemas comunes

- "php: No existe el archivo o el directorio": indica que no tienes PHP en el host; usa Sail.
- Errores de migraciones o modelos en tests:
  - Asegúrate de que `tests/TestCase.php` incluya `CreatesApplication` y que `tests/Pest.php` use `RefreshDatabase` (ya configurado en este repo).
  - Si trabajas con seeders pesados, evita ejecutarlos en cada test: crea roles mínimos dentro del test (ver `ExpenseApprovalTest`).

Integración en CI

- Recomendación: ejecutar `./vendor/bin/pest --colors=never` dentro del job que use el contenedor (o instalar PHP y dependencias en runner). Puedo ayudarte a añadir un job de GitHub Actions si quieres (indícame la estructura que prefieres o una plantilla de ejemplo).

Contacto

- Si algún test falla en tu entorno, pega aquí el output (o usa `./vendor/bin/sail exec laravel.test bash -lc "php ./vendor/bin/pest --filter <filtro>; echo EXIT:\$?"`) y te ayudo a depurarlo.
