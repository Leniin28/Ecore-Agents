# ECore Agents

ECore Agents es el proyecto académico de la materia **Negocios Electrónicos 2**. Continúa conceptualmente el frontend elaborado para Negocios Electrónicos 1, pero se desarrolla como una aplicación Laravel nueva e independiente.

La Etapa 1 estableció la base visual en Laravel + Blade. La Etapa 2 incorpora registro, login, logout y perfil mediante sesiones Laravel, además de los roles básicos `admin` y `usuario`.

La Etapa 3 incorpora el primer módulo real del CRM: gestión de clientes con nombre, correo, teléfono, empresa, fecha de registro y estado. Incluye CRUD Blade, búsqueda por nombre/correo/empresa, filtro por estado y endpoints REST que utilizan autenticación por sesión Laravel.

La Etapa 4 agrega las etapas CRM `prospecto`, `activo`, `frecuente` e `inactivo`, además del registro de llamadas, correos y reuniones. Cada interacción conserva su fecha, cliente y usuario responsable, y aparece en el historial centralizado del cliente.

Los roles `admin` y `usuario` pueden consultar, crear y editar clientes, cambiar su etapa y registrar interacciones. Solamente `admin` puede eliminar clientes. El CRM todavía no está terminado: las métricas y la pantalla Mi actividad continúan pendientes.

## API REST del CRM

Los endpoints `GET`, `POST`, `PUT` y `DELETE` bajo `/api/clientes` consultan y persisten clientes mediante Eloquent. La respuesta de cada cliente incluye `etapa_crm` y la etapa puede actualizarse con `PUT /api/clientes/{cliente}/etapa`.

Las interacciones se consultan con `GET /api/clientes/{cliente}/interacciones` y se registran con `POST /api/interacciones`. El usuario responsable se obtiene siempre de la sesión autenticada.

Los endpoints permanecen dentro de la aplicación monolítica y están protegidos por la sesión Laravel existente; las operaciones que modifican datos también requieren el token CSRF de la sesión web. No se utiliza JWT, Sanctum ni otro paquete de autenticación.

## Stack actual

- Laravel 13
- PHP 8.3 o posterior
- Blade
- CSS tradicional
- JavaScript vanilla
- Vite
- SQLite como configuración local inicial de Laravel
- PHPUnit
- Git

## Requisitos

- PHP 8.3 o posterior
- Composer
- Node.js y npm

## Instalación

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run build
```

En macOS o Linux, sustituye `copy .env.example .env` por `cp .env.example .env`.

La configuración inicial utiliza SQLite. Si `database/database.sqlite` no existe, créalo antes de ejecutar las migraciones y el seeder local.

```bash
php artisan migrate --seed
```

Para reconstruir completamente una base local sin datos que deban conservarse:

```bash
php artisan migrate:fresh --seed
```

## Administrador demo local

El seeder crea un administrador únicamente cuando `APP_ENV=local`:

- Correo: `admin@ecore.local`
- Contraseña: `ECoreDemo2026!`

Estas credenciales son exclusivamente académicas y locales. Deben eliminarse o sustituirse antes de cualquier despliegue real; el seeder se omite automáticamente fuera del entorno local.

## Ejecución local

Para iniciar el servidor, el worker de colas y Vite mediante el comando convencional del proyecto:

```bash
composer run dev
```

También puedes iniciar únicamente Laravel:

```bash
php artisan serve
```

## Pruebas

```bash
composer test
```

## Build de assets

```bash
npm run build
```
