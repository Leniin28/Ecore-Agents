# Transferir el estado actual de la demo ERP entre computadoras

Estos comandos copian una instantánea de **la demo ERP local**, incluidas las órdenes y pruebas posteriores. No son un seeder, una sincronización ni una restauración completa de SQLite. Solo están habilitados para SQLite en entornos `local` y `testing`.

## Qué se copia

- Las cuentas `ventas.erp.demo@ecore.local` y `logistica.erp.demo@ecore.local`, con sus permisos y hashes de contraseña actuales. No se exportan tokens de sesión ni `remember_token`.
- Los clientes demo Aurora y Nexo, el proveedor tecnológico demo y los cinco productos `DEMO ERP · …`, con su stock **actual**, mínimos, costos y precios.
- Todas las órdenes relacionadas con estos clientes/productos, sus detalles, estados, fechas y precios históricos; también procesos internos, movimientos, pedidos SCM, interacciones CRM y auditorías vinculadas a la demo.
- Las referencias por correo de otros usuarios que participaron en esas pruebas. No se exportan sus contraseñas, roles, permisos ni sesiones, y no se crean cuentas de administrador en destino.

No se exportan clientes/productos ajenos a la demo. Si una orden mezcla entidades demo y ajenas, la exportación se detiene para no incluir datos ajenos silenciosamente. El nivel ERP de origen queda como referencia en el paquete, pero no se reemplaza el nivel ni la configuración SCM del destino.

## 1. En la computadora de origen

Desde la raíz del proyecto, elige un archivo nuevo dentro de la carpeta privada (ignorada por Git):

```powershell
New-Item -ItemType Directory -Force .\storage\app\private\erp-demo-transfer
php artisan erp:demo-export .\storage\app\private\erp-demo-transfer\erp-demo-actual.json
```

La exportación toma una instantánea de lectura consistente y no modifica la base. Si el archivo ya existe, no se sobrescribe: usa otro nombre. Guarda la salida con el conteo de registros y la huella SHA-256 del contenido.

**Transfiere el JSON de forma privada**, por ejemplo mediante una memoria USB. Contiene hashes de las contraseñas de las cuentas demo: no lo publiques ni lo agregues a Git. La huella detecta corrupción, no autentica al remitente; importa únicamente un archivo propio/de confianza. No hace falta copiar `.env`, `APP_KEY` ni `database.sqlite`.

## 2. En la computadora de destino

Actualiza el código y las migraciones, sin usar `fresh`, `refresh`, `reset` ni seeders:

```powershell
git pull origin main
php artisan migrate
```

Copia el JSON a `storage\app\private\erp-demo-transfer\erp-demo-actual.json` dentro de ese proyecto. Crea la carpeta si falta.

Primero revisa la carga **sin escribir datos**:

```powershell
php artisan erp:demo-import .\storage\app\private\erp-demo-transfer\erp-demo-actual.json
```

Si la revisión es correcta, aplica:

```powershell
php artisan erp:demo-import .\storage\app\private\erp-demo-transfer\erp-demo-actual.json --apply
```

Antes de aplicar, se crea y valida automáticamente un respaldo SQLite consistente en `storage/app/private/erp-demo-backups/`. La ruta se muestra al terminar. No reemplaces una base en uso con ese respaldo sin revisar primero las operaciones posteriores que perderías.

### Si falta un actor histórico

Una prueba puede haber sido cancelada por `admin@ecore.local`. Si esa cuenta ya existe en destino, solo se usa su ID y **no se modifica**. Si en destino usas otro correo, puedes vincular ese evento a un usuario ya existente mediante una equivalencia explícita:

```powershell
php artisan erp:demo-import .\storage\app\private\erp-demo-transfer\erp-demo-actual.json --actor=admin@ecore.local=TU_CORREO_EXISTENTE
php artisan erp:demo-import .\storage\app\private\erp-demo-transfer\erp-demo-actual.json --actor=admin@ecore.local=TU_CORREO_EXISTENTE --apply
```

Sustituye `TU_CORREO_EXISTENTE` por el correo real de esa cuenta; no es una contraseña. Esta equivalencia atribuye los eventos históricos a la cuenta elegida; úsala solo si representa al mismo operador. Sin una coincidencia o equivalencia válida, no se importa nada.

## Garantías y límites

- **Aditivo y atómico:** solo se insertan registros nuevos. Un error revierte toda la importación. No se actualizan ni eliminan registros preexistentes.
- **Conflictos:** si ya hay una cuenta, cliente, proveedor o producto con la identidad demo del paquete, la carga completa se detiene. No se fusionan inventarios ni se sobrescriben contraseñas, incluso si los registros parecen iguales. Solicita una revisión antes de resolver un conflicto.
- **Repetición:** la importación agrega una constancia en `auditorias` dentro de la misma transacción. Repetir exactamente el mismo paquete es una operación sin cambios, incluso si luego se usó la demo. Una instantánea posterior no reemplaza la anterior: provocará un conflicto y requiere revisión; no es sincronización incremental.
- **Stock e historial:** se inserta el stock final y se copia el historial, sin volver a procesar las órdenes ni disparar reposiciones PUSH. Así no se descuenta dos veces el inventario.
- **IDs y folios:** se asignan IDs nuevos y se remapean todas las relaciones. La consola y la constancia de importación muestran la equivalencia de folios de origen/destino. Los textos de auditoría se conservan literalmente y pueden mencionar el folio de origen.
- **Configuración global:** `estado_erp`, `configuracion_scm`, sesiones y cuentas ajenas no se reemplazan. Si decides cambiar el nivel ERP del destino, hazlo expresamente desde su pantalla.

## 3. Verificación después de importar

1. Entra con la cuenta demo de Ventas y su contraseña actual de origen. Revisa `/erp/ordenes`, el estado de las órdenes y sus detalles.
2. Entra con la cuenta demo de Logística. Revisa `/scm` y el inventario: deben coincidir los stocks exportados, los movimientos y el pedido PUSH existente.
3. Revisa `/erp` y `/erp/reportes`: incluyen tanto los registros previos del destino como la demo agregada; por ello los totales generales pueden ser distintos a los de origen.
4. No vuelvas a procesar una orden ya procesada para comprobar la copia. Puedes crear una nueva orden de prueba después de revisar el estado importado.
