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

## Etapa 3 académica: ERP integrado

ERP reutiliza clientes CRM y productos, inventario, movimientos y reposición SCM. `/erp` muestra métricas empresariales; `/erp/recursos` es una consulta del catálogo SCM. El precio de venta se guarda separado del costo interno.

Las órdenes se crean en `/erp/ordenes/create` con cliente, recursos y resumen. El servidor calcula el total en centavos y conserva el precio de cada línea. Crear deja la orden pendiente; procesarla valida todas las existencias y ejecuta salidas mediante `InventarioService` dentro de una sola transacción. Una falla revierte stock, movimientos, pedidos PUSH, procesos y auditoría. Solo las pendientes pueden procesarse o cancelarse. Las procesadas no admiten un segundo procesamiento ni devoluciones.

`/erp/procesos` muestra los pasos operativos de las órdenes; la auditoría identifica acciones de usuarios en todo el sistema. `/erp/nivel` permite declarar el nivel académico Básico, Integrado, Automatizado u Optimizado. No es una certificación automática de madurez.

Los empleados necesitan permiso `erp`. Ventas sugiere CRM + ERP y Logística SCM + ERP; el administrador puede ajustar esos permisos. El perfil no otorga acceso por sí mismo. Los clientes públicos no acceden a módulos internos.

API ERP con sesión Laravel y CSRF: `GET/POST /api/erp/ordenes`, `GET /api/erp/ordenes/{orden}`, `POST .../{orden}/procesar`, `POST .../{orden}/cancelar`, `GET /api/erp/metricas` y `GET/PUT /api/erp/estado`. Las ventas y unidades vendidas cuentan solo órdenes procesadas; el gráfico de seis meses usa la fecha de procesamiento. Las cantidades son unidades internas de capacidad IA, sin cobros ni llamadas externas.

## Correspondencia con el PDF de Etapa 3

Referencia académica: **Etapa 3 - ERP (Semanas 7-9)**, apartados 3.1 a 3.5. Las siguientes equivalencias documentan la adaptación a ECore Agents; no se agregan alias ni se duplican controladores o tablas para reproducir literalmente los nombres del ejemplo.

### Rutas equivalentes

Todas las rutas ERP requieren sesión y permiso `erp`. Las escrituras web y JSON conservan CSRF. La administración de roles y permisos sigue reservada al administrador.

| Apartado | Ruta indicada en el PDF | Implementación ECore Agents | Motivo |
|---|---|---|---|
| 3.1 | `POST /ordenes` | Web: `POST /erp/ordenes`. JSON: `POST /api/erp/ordenes`. | Distingue las órdenes de clientes ERP de los pedidos de reposición SCM. |
| 3.1 | `GET /ordenes` | Web: `GET /erp/ordenes`. JSON: `GET /api/erp/ordenes`. | Listado con sesión y permiso ERP. |
| 3.1 | `GET /ordenes/{id}` | Web: `GET /erp/ordenes/{orden}`. JSON: `GET /api/erp/ordenes/{orden}`. | El parámetro usa el modelo existente de la orden. |
| 3.1 | `PUT /ordenes/{id}/estado` | Web: `POST /erp/ordenes/{orden}/procesar` o `POST /erp/ordenes/{orden}/cancelar`. JSON: las mismas acciones bajo `/api/erp/ordenes/{orden}`. | Son acciones específicas, no un cambio libre de estado: procesar actualiza stock y cancelar solo admite pendientes. |
| 3.2 | `POST /ordenes/procesar` | Web: `POST /erp/ordenes/{orden}/procesar`. JSON: `POST /api/erp/ordenes/{orden}/procesar`. | Identifica la orden a procesar y reutiliza la misma lógica transaccional SCM. |
| 3.3 | `GET /erp/estado`, `PUT /erp/estado` | Se conservan ambas rutas. JSON: `GET /api/erp/estado`, `PUT /api/erp/estado`. Pantalla: `GET /erp/nivel`. | Separa la pantalla de nivel de la respuesta JSON y de su actualización. |
| 3.4 | `GET /erp/metricas` | JSON: `GET /api/erp/metricas`. Pantallas: `GET /erp` y `GET /erp/reportes`. | Sigue el prefijo `/api` ya usado por CRM y SCM. |
| 3.5 | `/auth/login` | `GET /login` y `POST /login`. | Reutiliza la autenticación Laravel existente. |
| 3.5 | `/usuarios/rol` | Edición: `GET /admin/usuarios/{usuario}/edit`. Guardado: `PUT/PATCH /admin/usuarios/{usuario}`. | El mismo panel administra rol, perfil empresarial y permisos; no hay un segundo sistema de usuarios. |

Las rutas web devuelven Blade o redirecciones; las rutas `/api/erp/*` devuelven JSON y usan la misma sesión. La creación deja la orden pendiente, sin modificar inventario. El descuento se ejecuta al procesar la orden, junto con movimientos, reposición PUSH, procesos y auditoría. El cliente se vincula al registro CRM existente.

### Tablas y datos compartidos

| Elemento del PDF | Implementación ECore Agents | Reutilización o adaptación |
|---|---|---|
| Clientes | `clientes` | Tabla CRM existente; la orden conserva `cliente_id`. |
| Productos | `productos` | Recursos de capacidad IA del SCM; se añadió `precio_venta_unitario` separado del costo interno. |
| Inventario | `productos.stock_actual`, `productos.stock_minimo` y `movimientos_inventario` | No hay una segunda tabla de inventario ERP. |
| Pedidos | `pedidos` | Reposición SCM existente. No se confunde con órdenes de clientes ERP. |
| Usuarios | `users` | Usuarios compartidos. Ventas y Logística son perfiles de empleados; `permissions` determina el acceso. |
| Órdenes empresariales, nuevas | `ordenes_empresariales` y `orden_detalles` | Tablas nuevas con relaciones al cliente, usuario y producto existentes. Los detalles conservan precios históricos. |
| Recursos, enumerados como nuevos | Vista `/erp/recursos` sobre `productos` y `proveedores` | Se cubre la función sin crear una tabla duplicada `recursos`. |
| Procesos internos, nuevos | `procesos_internos` | Historial operativo por orden; distinto de la auditoría general. |
| Reportes ERP, enumerados como nuevos | Consultas sobre órdenes, detalles, clientes y productos | Métricas y gráficas calculadas; no se crea una tabla `reportes_erp` ni se almacenan totales duplicados. |
| EstadoERP del apartado 3.3 | `estado_erp` | Registro global del nivel declarado por el equipo. |

Así se cumple el objetivo funcional de integrar información, con diferencias explícitas frente al esquema literal del PDF. La simulación comercial del apartado 3.5 utiliza los módulos y permisos propios; no requiere conectar SAP, Oracle, Odoo ni Dynamics.

### Barra de progreso del nivel ERP

La pantalla `/erp/nivel` muestra una barra acumulativa, el porcentaje y las cuatro etapas, distinguiendo etapas previas, nivel actual y etapas siguientes. La equivalencia visual es:

| Nivel declarado | Posición | Barra |
|---|---|---|
| Básico | 1 de 4 | 25% |
| Integrado | 2 de 4 | 50% |
| Automatizado | 3 de 4 | 75% |
| Optimizado | 4 de 4 | 100% |

El porcentaje expresa la posición del **nivel declarado**, no el porcentaje de requisitos implementados ni una evaluación automática de la empresa. La barra no cambia niveles, permisos o datos por sí misma. Estos ajustes de presentación y documentación no requieren migraciones ni ejecutar seeders.

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

El seeder agrega también dos clientes ficticios y seis órdenes ERP (cuatro procesadas, una pendiente y una cancelada), sus detalles, movimientos vinculados, procesos internos y nivel Automatizado. Reejecutarlo restablece los datos demo; úsalo sobre una base local destinada a demostración.

Credenciales exclusivamente locales, todas con contraseña `ECoreDemo2026!`:

- Administrador existente: `admin@ecore.local` (creado por `php artisan db:seed` en entorno local).
- Logística SCM + ERP: `empleado.scm@ecore.local`.
- Ventas CRM + ERP: `ventas.demo@ecore.local`.

Los datos no llaman servicios externos ni usan claves reales. El archivo `database/database.sqlite` permanece local e ignorado por Git. Los seeders omiten datos demo fuera de local/testing; nunca ejecutar `migrate:fresh` sobre una base que deba conservarse.

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
