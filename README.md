# COTE

<p align="center">
  <img src="public/assets/img/logo01.png" alt="COTE - Control Tesorería" width="320">
</p>

<p align="center">
  <strong>Control de Tesorería</strong>
</p>

<p align="center">
  Aplicación web para la gestión de tesorería: cuentas, personas, transacciones, rendiciones y aprobaciones.
</p>

---

## Características

- **Panel de tesorería** — Resumen de cuentas, saldos, transacciones y rendiciones pendientes
- **Personas y cuentas** — Gestión de personas, cuentas por tipo (Personal, Tesorería, Cuadrillas) y saldos
- **Transacciones** — Registro y aprobación de transferencias y movimientos
- **Rendiciones de gastos** — Carga, envío y flujo de aprobación con documentos adjuntos
- **Aprobaciones** — Cola de transacciones y rendiciones pendientes de revisión
- **Estadísticas e informes** — Gráficos por persona, categoría y exportación a Excel
- **Usuarios y roles** — Permisos (boss, tesorero) con Spatie Permission
- **Modo oscuro** — Tema claro/oscuro con persistencia de preferencia
- **DataTables** — Tablas con búsqueda, orden, exportación (CSV, Excel, PDF)

## Stack

| Tecnología        | Uso                    |
|-------------------|------------------------|
| **Laravel 12**    | Backend, API, auth     |
| **Livewire 3**    | Componentes reactivos  |
| **Laravel Breeze**| Login y sesión         |
| **Bootstrap 5**   | UI y componentes       |
| **DataTables**    | Tablas avanzadas      |
| **Spatie**        | Permisos, activity log, medialibrary |
| **Laravel Sail**  | Entorno Docker         |

## Requisitos

- PHP 8.2+
- Composer
- Node.js y npm (para assets)
- Base de datos: SQLite (desarrollo) o PostgreSQL (producción)

## Instalación

### Con Laravel Sail (Docker)

```bash
# Clonar el repositorio
git clone https://github.com/roro66/cote.git
cd cote

# Instalar dependencias PHP
composer install

# Copiar entorno y configurar (editar .env si es necesario)
cp .env.example .env
php artisan key:generate

# Levantar contenedores
./vendor/bin/sail up -d

# Migraciones y seeders
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed

# Assets (dentro del contenedor)
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

La aplicación quedará disponible en `http://localhost` (o el puerto configurado en `APP_PORT` en `.env`).

### Sin Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configurar DB en .env (por defecto SQLite)
php artisan migrate
php artisan db:seed
npm install && npm run build
php artisan serve
```

## Usuarios por defecto (seeder)

| Email             | Rol     | Uso típico     |
|-------------------|--------|----------------|
| `admin@cote.com`  | boss   | Administración |
| `tesorero@cote.com` | tesorero | Operación   |

Contraseña por defecto: `password123` (cambiar en producción).

## Donaciones

Se aceptan donaciones de cualquier monto para apoyar el desarrollo y mantenimiento de COTE. Gracias desde ya por tu aporte.

[![Donar con PayPal](https://www.paypalobjects.com/es_ES/ES/i/btn/btn_donate_SM.gif)](https://www.paypal.com/donate/?hosted_button_id=4WY2DYMZL2HBW)

**[Donar con PayPal](https://www.paypal.com/donate/?hosted_button_id=4WY2DYMZL2HBW)**

## Licencia

Este proyecto está bajo la [licencia MIT](https://opensource.org/licenses/MIT).
