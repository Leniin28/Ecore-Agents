# ECore Agents

ECore Agents es el proyecto académico de la materia **Negocios Electrónicos 2**. Continúa conceptualmente el frontend elaborado para Negocios Electrónicos 1, pero se desarrolla como una aplicación Laravel nueva e independiente.

La Etapa 1 estableció la base visual en Laravel + Blade. La autenticación incorpora registro, login, logout y perfil mediante sesiones Laravel. Los tipos de cuenta son administrador, empleado (valor histórico `usuario`) y cliente. El registro público crea una cuenta cliente vinculada a su ficha CRM; los empleados y administradores se crean desde `/admin/usuarios`. Los permisos propios por módulo (`crm`, `scm`) protegen rutas web y API, mientras el administrador conserva acceso total. Las altas internas, cambios de rol/permisos y restablecimientos de contraseña quedan auditados sin guardar contraseñas ni hashes.

La Etapa 3 incorpora el primer módulo real del CRM: gestión de clientes con nombre, correo, teléfono, empresa, fecha de registro y estado. Incluye CRUD Blade, búsqueda por nombre/correo/empresa, filtro por estado y endpoints REST que utilizan autenticación por sesión Laravel.

La Etapa 4 agrega las etapas CRM `prospecto`, `activo`, `frecuente` e `inactivo`, además del registro de llamadas, correos y reuniones. Cada interacción conserva su fecha, cliente y usuario responsable, y aparece en el historial centralizado del cliente.

La Etapa 5 completa el bloque CRM académico con métricas calculadas desde SQLite, dashboard, gráfica de clientes activos e inactivos, clientes en riesgo, conteo de interacciones por cliente y la pantalla Mi actividad. Un cliente activo se considera en riesgo cuando nunca ha tenido una interacción o su última interacción ocurrió hace más de 30 días.

Los administradores y empleados con permiso CRM pueden consultar el dashboard CRM, gestionar clientes según las reglas existentes, cambiar etapas, registrar interacciones y revisar exclusivamente su propia actividad. Solamente `admin` puede eliminar clientes y consultar la bitácora completa. Los clientes públicos no acceden a módulos internos.

## Etapa 2 académica: SCM digital

SCM adapta la cadena de suministro al negocio digital: OpenRouter puede registrarse como proveedor tecnológico, y los productos representan recursos/modelos de IA administrados por ECore Agents. El inventario expresa capacidad interna en “unidades de consumo IA”; no representa tokens físicamente almacenados ni consulta saldos reales.

El módulo incluye proveedores, recursos IA, inventario calculado desde el stock de cada producto, movimientos trazables, pedidos y estrategias PUSH/PULL. PUSH genera una reposición pendiente al llegar al mínimo sin duplicarla; PULL utiliza pedidos manuales. Al surtir un pedido se crea atómicamente una entrada y aumenta el stock. El dashboard resume consumo de los últimos 30 días, baja utilización, inventario crítico, estrategias, pedidos y nivel de madurez SCM. No se usan API keys, cobros ni llamadas reales a OpenRouter.

## API REST del CRM

Los endpoints `GET`, `POST`, `PUT` y `DELETE` bajo `/api/clientes` consultan y persisten clientes mediante Eloquent. La respuesta de cada cliente incluye `etapa_crm` y la etapa puede actualizarse con `PUT /api/clientes/{cliente}/etapa`.

Las interacciones se consultan con `GET /api/clientes/{cliente}/interacciones` y se registran con `POST /api/interacciones`. El usuario responsable se obtiene siempre de la sesión autenticada.

Las métricas CRM se consultan con `GET /api/crm/metricas`. Incluyen total de clientes, activos, inactivos, total de interacciones y clientes sin interacción durante los últimos 30 días.

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

## Datos demo compartidos

En entorno local, el seeder general también crea cinco recursos ficticios, movimientos distribuidos en seis meses y pedidos con estados y orígenes distintos. Es idempotente: puede ejecutarse más de una vez sin duplicar esos registros.

```bash
git pull origin main
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoDataSeeder
```

Empleado SCM demo: `empleado.scm@ecore.local` / `ECoreDemo2026!`. Los datos no llaman servicios externos ni usan claves reales. El archivo `database/database.sqlite` permanece local e ignorado por Git.

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
