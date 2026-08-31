# ECore Agents

ECore Agents es el proyecto académico de la materia **Negocios Electrónicos 2**. Continúa conceptualmente el frontend elaborado para Negocios Electrónicos 1, pero se desarrolla como una aplicación Laravel nueva e independiente.

La Etapa 1 estableció la base visual en Laravel + Blade. La Etapa 2 incorpora registro, login, logout y perfil mediante sesiones Laravel, además de los roles básicos `admin` y `usuario`.

Todavía no existe CRM ni backend funcional de negocio. Los planes, compras, interacciones y demás módulos comerciales continúan como contenido visual o pendiente.

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
