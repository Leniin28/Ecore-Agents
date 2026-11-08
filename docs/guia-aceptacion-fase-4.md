# Guía de Aceptación Manual - Fase 4: Comercio Electrónico Integrado (ECore Agents)

> **Nota importante sobre simulaciones:** Todos los pagos, recargas de monedero, CLABEs bancarias y referencias en esta guía son **estrictamente simulados para fines académicos**. No se conecta ninguna pasarela externa, API bancaria ni dinero real.

---

## 1. Preparación de pruebas y manejo de cuentas

Para la realización de estas pruebas **no ejecutes `php artisan db:seed` ni `migrate:fresh`**. Los seeders pueden sobrescribir datos locales, restablecer contraseñas o reiniciar estados ya avanzados.

### Cómo realizar las pruebas:
1. **Pruebas de cliente:**
   - Registrar una cuenta propia desde la pantalla pública de registro: `GET /registro`.
   - Proporcionar nombre, correo, teléfono de contacto y contraseña.
   - El sistema inicia sesión automáticamente, provisiona de inmediato la ficha de cliente CRM y habilita el monedero virtual con saldo inicial $0.00 MXN.
2. **Pruebas administrativas y de personal:**
   - Iniciar sesión en `GET /login` con una cuenta con rol de Administrador o con los permisos modulares pertinentes (`crm`, `scm`, `erp`) que ya existan en la base local.
   - Si se requiere una cuenta administrativa o de empleado adicional, el administrador puede crearla desde `/admin/usuarios` (o `/erp/usuarios`), donde se configuran rol, permisos y contraseña de manera auditada.

---

## 2. Pruebas por objetivos del PDF oficial

### 4.1.1 Catálogo
- **Rol:** Público (invitado) o Cliente autenticado.
- **Ruta real:** `GET /catalogo`, `GET /catalogo/{producto}` (navegando desde el listado).
- **Pasos:**
  1. Navegar a `/catalogo` desde el enlace del menú principal "Tienda / Catálogo".
  2. Aplicar un filtro por categoría seleccionando una opción del menú desplegable o buscar un producto mediante el campo de búsqueda por nombre o descripción.
  3. Comprobar que en la lista solo aparezcan productos comerciales con visibilidad activa (`visible_tienda = true`) y con sus respectivas imágenes o alternativas visuales, precio de venta en MXN y distintivos de disponibilidad.
  4. Hacer clic en "Ver detalles" de cualquier producto visible para abrir `/catalogo/{producto}`.
  5. En la ficha de detalle, inspeccionar la descripción comercial, categoría, precio y disponibilidad de cupos.
  6. Modificar el selector numérico de cantidad y verificar que no permita seleccionar una cantidad superior al stock actual disponible.
- **Resultado esperado:**
  - El catálogo responde con tarjetas responsivas, filtros funcionales que conservan la paginación y precios formateados en MXN.
  - Los recursos técnicos internos (modelos IA, capacidades internas) permanecen ocultos y protegidos fuera del catálogo comercial.
  - El selector de cantidad bloquea compras superiores a la disponibilidad real en inventario.

### 4.1.2 Procesos de pago
- **Rol:** Cliente autenticado.
- **Ruta real:** `GET /checkout`, `POST /checkout`, `GET /pagos/confirmacion/{referencia}`.
- **Pasos:**
  1. Con al menos un artículo en el carrito, ingresar a `/checkout` ("Finalizar compra").
  2. Completar los campos obligatorios de contacto: nombre completo, correo electrónico y teléfono.
  3. **Prueba 1 - Tarjeta de crédito / débito (Demo):**
     - Seleccionar "Tarjeta de crédito / débito (Demo)".
     - Hacer clic en "Confirmar y pagar compra demo".
     - Comprobar que la transacción se aprueba inmediatamente y redirige a `/pagos/confirmacion/{referencia}` con estado "Aprobado".
  4. **Prueba 2 - PayPal (Demo):**
     - Seleccionar "PayPal (Demo)" y confirmar.
     - Comprobar la aprobación inmediata y generación del comprobante con método PayPal Demo.
  5. **Prueba 3 - Transferencia bancaria / SPEI (Demo):**
     - Seleccionar "Transferencia bancaria / SPEI (Demo)" y confirmar.
     - Comprobar que la orden se genera como `pendiente`, el pago como `pendiente` y el comprobante despliega la CLABE interbancaria simulada, banco y referencia de rastreo, sin descontar stock anticipadamente.
  6. **Prueba 4 - Simulación de rechazo de pago:**
     - En `/checkout`, marcar la casilla *"Simular rechazo de pago (Para verificar que se revierten los cambios y no se descuenta stock)"*.
     - Confirmar el formulario.
- **Resultado esperado:**
  - Los métodos demo operan sin solicitar datos confidenciales reales (CVV, contraseñas bancarias ni números de tarjeta reales).
  - La simulación de rechazo aborta limpiamente la compra: devuelve un mensaje explicativo de validación, no crea registros en `ordenes_empresariales` ni en `pagos`, y no modifica el stock en inventario.

### 4.1.3 Carrito
- **Rol:** Cliente autenticado (o invitado que luego inicia sesión).
- **Ruta real:** `GET /carrito`, `POST /carrito`, `PUT /carrito/{detalle}`, `DELETE /carrito/{detalle}`, `POST /carrito/vaciar`, `GET /agente-personalizado`.
- **Pasos:**
  1. Desde `/catalogo` o la ficha de detalle, pulsar "Agregar al carrito" en cualquier plan o complemento.
  2. Acceder a `/carrito` mediante el enlace del encabezado (que muestra el conteo persistente de artículos).
  3. Modificar la cantidad de una línea y hacer clic en el botón de actualización.
  4. Eliminar una línea del carrito mediante el botón de eliminar.
  5. Abrir el configurador en `/agente-personalizado`:
     - Seleccionar un plan base en el selector.
     - Marcar módulos adicionales (ej. "Agenda de citas", "Respuestas en audio").
     - Pulsar "Agregar plan configurado al carrito".
  6. Probar agregar nuevamente el mismo plan base pero con una configuración de módulos distinta.
- **Resultado esperado:**
  - Carrito persistente en base de datos vinculado al cliente: los artículos se conservan al recargar o cambiar de navegador.
  - Soporte de múltiples líneas del mismo plan base con configuraciones independientes sin sobrescribirse.
  - El servidor calcula formalmente subtotales, descuentos, base gravable, impuestos y total general en centavos enteros con redondeo consistente.

### 4.1.4 Comercialización
- **Rol:** Administrador.
- **Ruta real:** `GET /admin/tienda/productos`, `GET /admin/tienda/productos/{producto}/edit`, `PUT /admin/tienda/productos/{producto}`, `POST /admin/tienda/productos/{producto}/toggle`, `GET /admin/tienda/promociones`, `GET /admin/tienda/categorias`.
- **Pasos:**
  1. Ingresar a `/admin/tienda/productos` desde el menú superior "Admin Tienda".
  2. Verificar que los recursos técnicos internos del sistema no tengan activa la casilla `visible_tienda` para que no se muestren a clientes públicos.
  3. Seleccionar un producto comercial y hacer clic en "Editar" (`/admin/tienda/productos/{producto}/edit`).
  4. Modificar su categoría comercial, precio de venta unitario, descripción, URL de imagen, y estado de destacado o visibilidad. Guardar cambios.
  5. Acceder a `/admin/tienda/categorias`: registrar o editar una categoría comercial normalizada.
  6. Acceder a `/admin/tienda/promociones`: crear o consultar promociones comerciales con porcentaje de descuento o monto fijo, estableciendo fechas de vigencia y productos autorizados.
- **Resultado esperado:**
  - Administración segura de la oferta comercial que protege la estructura interna del SCM/ERP.
  - Los productos modificados y las categorías normalizadas actualizan de forma inmediata el catálogo público `/catalogo`.
  - Las promociones creadas validan su vigencia del lado del servidor al aplicarse en `/carrito`.

### 4.1.5 Impuestos
- **Rol:** Administrador (gestión impositiva) y Cliente (desglose transparente).
- **Ruta real:** `GET /admin/tienda/impuestos`, `POST /admin/tienda/impuestos/{impuesto}/toggle`, `GET /carrito`, `GET /checkout`.
- **Pasos:**
  1. Como administrador, ingresar a `/admin/tienda/impuestos`.
  2. Consultar los esquemas impositivos configurados (ej. IVA 16% / 1600 puntos base) y alternar su estado activo/inactivo mediante el botón correspondiente.
  3. Como cliente, agregar productos a `/carrito` y avanzar a `/checkout`.
  4. Revisar la tabla de resumen de importes en el carrito y en el checkout.
- **Resultado esperado:**
  - Los impuestos se calculan estrictamente en el servidor sobre la base gravable (subtotal tras aplicar promociones vigentes).
  - La interfaz desglosa el porcentaje aplicado, nombre del impuesto y monto exacto en centavos.
  - El comprobante final de compra y la orden empresarial conservan el importe histórico de impuestos cobrados.

### 4.1.6 Atención
- **Rol:** Cliente (registro y consulta de tickets) y Administrador / Personal CRM (atención y resolución).
- **Ruta real:** `GET /tickets`, `GET /tickets/crear`, `POST /tickets`, `GET /tickets/{ticket}`, `GET /admin/tienda/tickets` (o `/crm/tickets`).
- **Pasos:**
  1. Como cliente autenticado, ingresar a `/tickets` ("Atención") desde el menú principal.
  2. Hacer clic en "Nuevo ticket de atención" (`/tickets/crear`), ingresar asunto, mensaje y prioridad, y enviar el formulario.
  3. Comprobar que el ticket se registra con estado inicial "abierto" y aparece en el listado personal de soporte.
  4. Iniciar sesión como Administrador o empleado con permiso CRM y navegar a `/admin/tienda/tickets` o `/crm/tickets`.
  5. Abrir el ticket recibido, ingresar una respuesta de soporte y actualizar el estado ("en proceso" o "cerrado").
  6. Como cliente, consultar `/tickets/{ticket}` para verificar la respuesta del equipo y el estado actualizado.
- **Resultado esperado:**
  - Canal de postventa integrado y seguro.
  - Aislamiento estricto: cada cliente accede únicamente a sus propios tickets; los administradores y agentes CRM gestionan la bandeja unificada.

---

### 4.2 Base de datos
- **Rol:** Verificación arquitectónica y técnica (Administrador / Desarrollador).
- **Estructura y diseño relacional:**
  - **Tablas de la tienda comercial:**
    - `carritos` y `carrito_detalles`: gestión del carrito persistente por cliente, incluyendo `version` (control de concurrencia), `cantidad`, `configuracion` (JSON con desglose de módulos) y `configuracion_hash` determinista.
    - `promociones`: cupones comerciales con código en mayúsculas, tipo de descuento (porcentaje o monto fijo), fechas de vigencia y alcance por producto.
    - `impuestos`: esquemas tributarios configurados en puntos base enteros (`puntos_base`, ej. 1600 para 16%) para evitar imprecisiones de redondeo.
    - `pagos`: entidad transaccional con `referencia` UUID única, método demo, importes en centavos (`subtotal_centavos`, `descuento_centavos`, `impuesto_centavos`, `total_centavos`) e inmutabilidad histórica.
    - `monederos` y `monedero_movimientos`: saldo en centavos y bitácora auditada con clave de idempotencia (`operacion_referencia`).
    - `tickets_atencion`: tickets de soporte vinculados al cliente CRM.
  - **Integración con tablas de CRM, SCM y ERP:**
    - `clientes` (CRM): clave foránea `user_id` con restricción única, auditoría de vinculación administrativa y relación con compras y tickets.
    - `productos` y `movimientos_inventario` (SCM): campos comerciales `visible_tienda`, `destacado`, `imagen_url`, `categoria_id`, preservando el stock en cupos/paquetes y registrando salidas por venta.
    - `ordenes_empresariales`, `orden_detalles` y `procesos_internos` (ERP): detalles con restricción única compuesta (`orden_empresarial_id` + `producto_id` + `configuracion_hash`), precios históricos y procesos operativos (`orden_creada`, `stock_validado`, `venta_registrada`).
- **Resultado esperado:**
  - Modelo relacional íntegro, normalizado, con claves foráneas protegidas y manejo monetario en centavos enteros para consistencia financiera.

---

### 4.3 Registro y autenticación
- **Rol:** Visitante / Nuevo usuario y Administrador.
- **Ruta real:** `GET /registro`, `POST /registro`, `GET /login`, `POST /login`, `POST /logout`, `GET /perfil`.
- **Pasos:**
  1. En el menú público, hacer clic en "Registro" (`/registro`).
  2. Ingresar nombre completo, correo electrónico, teléfono y contraseña (mínimo 8 caracteres con mayúscula, minúscula, número y símbolo). Enviar.
  3. Comprobar que tras el registro exitoso se inicia la sesión y se redirige a `/perfil`.
  4. Constatar que el menú superior ahora muestra las opciones del cliente: "Tienda / Catálogo", "Personalizado", "Carrito", "Mis compras", "Monedero", "Atención" y "Perfil".
  5. Probar cerrar sesión mediante "Cerrar sesión" (`POST /logout`).
  6. Iniciar sesión en `/login` con las credenciales creadas.
  7. Probar registrarse con un correo que coincida con un cliente CRM preexistente: comprobar que el sistema crea un cliente nuevo para el usuario web y **no usurpa** la ficha previa ni expone interacciones o saldos confidenciales.
- **Resultado esperado:**
  - Autenticación robusta basada en sesiones nativas de Laravel.
  - Control de acceso por roles (`cliente`, `empleado`, `admin`) con middlewares de autorización en todas las rutas privadas.
  - Protección estricta contra secuestro de cuentas CRM durante el registro público.

---

## 3. Extensiones y funcionalidades adicionales (Ampliaciones académicas)

Las siguientes funcionalidades complementan los objetivos del PDF y enriquecen la solución sin alterar los títulos numerados del curso:

### Extensión A: Monedero virtual ECore
- **Rol:** Cliente (recargas y pagos) y Administrador (ajustes auditados).
- **Rutas reales:** `GET /monedero`, `POST /monedero/recargar`, `POST /checkout`, `GET /admin/tienda/monederos`, `POST /admin/tienda/monederos/{monedero}/ajustar`.
- **Pasos:**
  1. Como cliente, entrar a `/monedero`.
  2. Solicitar una recarga simulada ingresando el monto (ej. $1,000.00 MXN) y método de abono demo.
  3. Verificar que el saldo se actualiza y aparece en el historial de movimientos.
  4. En `/checkout`, seleccionar "Monedero virtual ECore" para pagar una compra utilizando el saldo acumulado.
  5. Como administrador, acceder a `/admin/tienda/monederos`, seleccionar un monedero y aplicar un ajuste justificado (crédito o débito con motivo de al menos 5 caracteres).
- **Resultado esperado:**
  - Idempotencia en recargas garantizada por `operacion_referencia`.
  - Débito atómico del total cotizado al confirmar compras con monedero.
  - Ajustes administrativos auditados en la bitácora general del sistema.

### Extensión B: Control estricto de cotización y versión del carrito (Quote Drift)
- **Rol:** Cliente en proceso de compra.
- **Rutas reales:** `GET /checkout`, `POST /checkout`.
- **Pasos:**
  1. Abrir `/checkout` con artículos cotizados.
  2. En otra pestaña del navegador, modificar las cantidades del carrito o simular un cambio administrativo en precios o impuestos.
  3. En la pestaña original de checkout, enviar la confirmación de compra.
- **Resultado esperado:**
  - El endpoint exige de forma obligatoria `version_carrito`, `total_esperado_centavos` y `cotizacion_hash`.
  - Si los campos están ausentes, nulos o con formato inválido, la solicitud es rechazada inmediatamente.
  - Si se detecta un desfase entre la cotización mostrada y los importes vigentes, se aborta la operación con cero efectos secundarios (sin órdenes, pagos, salidas de inventario ni cargos al monedero).
  - Se redirige al checkout con un mensaje de advertencia y los importes actualizados para solicitar una confirmación consciente del usuario.
  - Si se reenvía una compra ya completada con el mismo `idempotency_key`, devuelve el comprobante existente sin duplicar registros.

### Extensión C: Cierre administrativo de transferencias bancarias demo
- **Rol:** Administrador.
- **Rutas reales:** `GET /admin/tienda/ordenes`, `GET /admin/tienda/ordenes/{pago}`, `POST /admin/tienda/ordenes/{pago}/confirmar-transferencia`, `POST /admin/tienda/ordenes/{pago}/rechazar-transferencia`.
- **Pasos:**
  1. Consultar una orden generada mediante transferencia demo con estado `pendiente`.
  2. Abrir la orden desde `/admin/tienda/ordenes/{pago}` (o `/erp/tienda/ordenes/{pago}`).
  3. **Caso de confirmación:** Pulsar "Confirmar transferencia recibida". Revalida el stock en ese momento, cambia pago a `aprobado`, orden a `procesada`, ejecuta la salida de inventario SCM y registra auditoría.
  4. **Caso de rechazo:** En otra transferencia pendiente, pulsar "Rechazar transferencia". Pago cambia a `rechazado`, orden a `cancelada`, el stock no se descuenta y se audita el evento.
- **Resultado esperado:**
  - Flujo administrativo completo, protegido y con revalidación de stock en el momento de la confirmación.

### Extensión D: Vinculación administrativa explícita de clientes CRM
- **Rol:** Administrador.
- **Rutas reales:** `GET /clientes/{cliente}/vincular` (o `/erp/clientes/{cliente}/vincular`), `POST /clientes/{cliente}/vincular`.
- **Pasos:**
  1. En el directorio de clientes CRM (`/clientes` o `/erp/clientes`), seleccionar un cliente que no cuente con usuario vinculado.
  2. Hacer clic en "Vincular a usuario" (`/clientes/{cliente}/vincular`).
  3. Seleccionar la cuenta de usuario en el menú desplegable, escribir el motivo administrativo (mínimo 5 caracteres) y confirmar.
- **Resultado esperado:**
  - Acceso restringido exclusivamente a administradores (403 para otros roles).
  - Bloqueo de vinculaciones incompatibles (clientes ya vinculados o usuarios con otro cliente asignado).
  - Registro de auditoría con fecha, usuario y justificación.
  - Preservación del layout y contexto ERP cuando se ejecuta desde `/erp/clientes/...`.

### Extensión E: Integración transversal con SCM y ERP
- **Rol:** Empleados de Logística (`empleado.scm@ecore.local`) y Ventas (`ventas.demo@ecore.local`).
- **Rutas reales:** `/scm/inventario`, `/scm/movimientos`, `/erp/ordenes`, `/erp/reportes`.
- **Pasos:**
  1. Consultar el inventario inicial en `/scm/inventario`.
  2. Realizar una compra comercial en la tienda.
  3. Revisar en `/scm/movimientos` la salida automática por venta y la actualización del stock.
  4. Consultar en `/erp/reportes` la contabilización de la venta en la fecha de procesamiento de la orden.
- **Resultado esperado:**
  - Reducción exacta del stock en cupos comerciales sin discrepancias con el SCM.
  - Registro de procesos internos ERP (`orden_creada`, `stock_validado`, `venta_registrada`).
  - Reportes de ventas consistentes que no contabilizan órdenes canceladas ni pendientes.
