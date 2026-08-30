# ECore Agents

ECore Agents es el proyecto académico de la materia **Negocios Electrónicos 2**. Continúa conceptualmente el frontend elaborado para Negocios Electrónicos 1, pero se desarrolla como una aplicación Laravel nueva e independiente.

La Etapa 1 establece la base visual en Laravel + Blade: layout compartido, home, catálogo, detalles de planes, agente personalizado, vistas de acceso y perfil, y un shell administrativo representativo.

Todavía no existe backend funcional de negocio. La autenticación real, el CRM, sus validaciones, permisos, modelos y API se implementarán posteriormente. Los formularios y datos visibles en esta etapa son demostrativos y están identificados como tales.

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

La configuración inicial utiliza SQLite. Si `database/database.sqlite` no existe, créalo antes de ejecutar las migraciones.

```bash
php artisan migrate
```

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
