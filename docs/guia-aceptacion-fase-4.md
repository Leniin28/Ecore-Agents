# Guía de Aceptación Manual - Fase 4: Comercio Electrónico Integrado (ECore Agents)

> **Nota importante sobre simulaciones:** Todos los pagos, recargas de monedero, CLABEs bancarias y referencias en esta guía son **estrictamente simulados para fines académicos**. No se conecta ninguna pasarela externa, API bancaria ni dinero real.

---

## 1. Cuentas y roles del sistema

Para ejecutar las pruebas en un entorno local con datos demo precargados (`php artisan db:seed`), se utilizan las siguientes credenciales (contraseña compartida en entorno demo local: `ECoreDemo2026!`):

| Rol | Correo | Módulos autorizados | Propósito de prueba |
|---|---|---|---|
| **Administrador** | `admin@ecore.local` | Acceso global (Admin Tienda, ERP, CRM, SCM, Auditoría) | Gestión comercial, aprobación de transferencias, vinculación CRM y ajustes de monedero |
| **Ventas (Empleado)** | `ventas.demo@ecore.local` | CRM + ERP | Verificación de permisos y visualización de órdenes ERP |
| **Logística (Empleado)** | `empleado.scm@ecore.local` | SCM + ERP | Consulta de stock en inventario y movimientos SCM |
| **Cliente registrado** | Registro público en `/registro` o usuario creado con rol `cliente` | Portal del cliente (Tienda, Carrito, Mis compras, Monedero, Tickets, Perfil) | Compra de planes, uso de monedero y tickets de soporte |

---

## 2. Pruebas por objetivos del PDF académico

### 4.1 Catálogo comercial y tienda online

#### 4.1.1 Catálogo comercial y visualización de detalles
- **Rol:** Público (sin autenticación requerida) o Cliente autenticado.
- **Pasos:**
  1. Navegar a `/catalogo` desde el encabezado "Tienda / Catálogo".
  2. Filtrar por categoría mediante el selector (ej. "Planes") o ingresar una palabra clave en el buscador.
  3. Comprobar que solo se muestren productos con `visible_tienda = true`.
  4. Hacer clic en "Ver detalles" en cualquier producto visible.
- **Resultado esperado:**
  - El catálogo responde con tarjetas responsivas, precios formateados en MXN, insignias de disponibilidad y stock.
  - La vista de detalle `/catalogo/{producto}` muestra descripción completa, disponibilidad, precio vigente y selector de cantidad con límite según el stock actual disponible.

#### 4.1.2 Configurador comercial integrado al carrito
- **Rol:** Cliente autenticado o visitante.
- **Pasos:**
  1. Ingresar a `/agente-personalizado` desde el menú principal.
  2. Seleccionar un plan base en el selector (ej. "Agente Plus").
  3. Marcar módulos adicionales opcionales (ej. "Agenda de citas", "Respuestas en audio").
  4. Observar el desglose reactivo de precios por módulo.
  5. Hacer clic en "Agregar plan configurado al carrito".
- **Resultado esperado:**
  - Se valida del lado del servidor que los módulos pertenezcan a la configuración oficial (`config/ecore.php`) sin aceptar importes manipulados desde el cliente.
  - El producto se agrega al carrito con la configuración persistida y el contador de artículos del encabezado se actualiza.

#### 4.1.3 Carrito persistente y cálculo formal en servidor
- **Rol:** Cliente autenticado (o redirigido tras iniciar sesión).
- **Pasos:**
  1. Ir a `/carrito`.
  2. Modificar la cantidad de una línea o eliminar un artículo.
  3. Probar agregar el mismo plan con una configuración distinta desde `/agente-personalizado`.
  4. Ingresar un código de promoción (si existe uno activo en administración) y hacer clic en "Aplicar".
- **Resultado esperado:**
  - El carrito conserva líneas independientes para configuraciones distintas del mismo plan base.
  - El subtotal, descuento por promoción, base gravable e impuestos activos (IVA 16%) se calculan con redondeo consistente en el servidor.
  - Se incrementa la versión interna del carrito tras cada modificación.

#### 4.1.4 Checkout, cotización vinculada y detección de cambios (Quote Drift)
- **Rol:** Cliente autenticado con artículos en el carrito.
- **Pasos:**
  1. En `/carrito`, hacer clic en "Continuar al checkout" para abrir `/checkout`.
  2. Mantener abierta la pantalla de checkout. En otra pestaña del navegador con la misma cuenta, ir a `/carrito` y modificar la cantidad o eliminar un artículo.
  3. Regresar a la pestaña original de checkout y pulsar "Confirmar y pagar compra demo".
- **Resultado esperado:**
  - El sistema detecta que la versión del carrito o el total esperado difieren de la cotización que el cliente estaba visualizando.
  - La compra **no se procesa**, no se genera orden ni pago, no se descuenta inventario ni saldo de monedero.
  - Se redirige a `/checkout` mostrando el mensaje: *"Tu carrito cambió mientras revisabas el pedido. Hemos actualizado tu resumen con los cambios recientes; por favor revísalo y confirma tu compra."*
  - Al pulsar nuevamente con el resumen actualizado, la compra procede con éxito.

#### 4.1.5 Pagos simulados con Tarjeta Demo y PayPal Demo
- **Rol:** Cliente autenticado.
- **Pasos:**
  1. En `/checkout`, llenar los datos de contacto y entrega (nombre, correo, teléfono).
  2. Seleccionar el método "Tarjeta de crédito / débito (Demo)" o "PayPal (Demo)".
  3. Hacer clic en "Confirmar y pagar compra demo".
- **Resultado esperado:**
  - Transacción atómica inmediata: la orden se registra como `procesada` y el pago como `aprobado`.
  - Redirección automática a la vista de confirmación `/pagos/confirmacion/{referencia}`.

#### 4.1.6 Simulación de rechazo de pago (Verificación de integridad)
- **Rol:** Cliente autenticado en `/checkout`.
- **Pasos:**
  1. En el formulario de `/checkout`, marcar la casilla *"Simular rechazo de pago (Para verificar que se revierten los cambios y no se descuenta stock)"*.
  2. Enviar el formulario.
- **Resultado esperado:**
  - Se muestra error de validación indicando que el pago demo fue rechazado según lo solicitado.
  - **No** se crea ninguna orden empresarial en la base de datos, no se crean pagos ni cargos y el inventario permanece inalterado.

---

### 4.2 Integración transversal con CRM, SCM y ERP

#### 4.2.1 SCM: Deducción atómica de cupos y movimientos de salida
- **Rol:** Cliente (compra) y Empleado Logística (`empleado.scm@ecore.local` en SCM).
- **Pasos:**
  1. Consultar en `/scm/inventario` el stock inicial de un producto visible en tienda.
  2. Como cliente, comprar 2 unidades de ese producto con pago inmediato simulado.
  3. Regresar a la sesión de logística en `/scm/inventario` y `/scm/movimientos`.
- **Resultado esperado:**
  - El stock actual del producto se descuenta exactamente en 2 unidades.
  - Se registra un movimiento de tipo `salida` con motivo `venta` vinculado a la orden empresarial generada.

#### 4.2.2 ERP: Órdenes empresariales, precios históricos y reportes
- **Rol:** Administrador (`admin@ecore.local`) o Empleado Ventas (`ventas.demo@ecore.local`).
- **Pasos:**
  1. Navegar a `/erp/ordenes` o `/erp/tienda/ordenes`.
  2. Abrir la orden generada por la compra comercial.
  3. Revisar en `/erp/reportes` las métricas de ventas.
- **Resultado esperado:**
  - La orden refleja el cliente CRM, los detalles con sus precios unitarios históricos y configuraciones particulares.
  - Se han registrado los procesos internos correspondientes (`orden_creada`, `stock_validado`, `venta_registrada`).
  - Las métricas y gráficas de ventas del ERP contabilizan la orden procesada sin duplicar totales.

#### 4.2.3 CRM: Vinculación administrativa de clientes y soporte
- **Rol:** Administrador (`admin@ecore.local`).
- **Pasos:**
  1. Crear un cliente nuevo en CRM desde `/clientes/create` (o `/erp/clientes/create`) sin asociarlo a un usuario.
  2. Consultar la ficha del cliente en `/clientes/{cliente}` (o `/erp/clientes/{cliente}`).
  3. Hacer clic en el botón "Vincular a usuario" para acceder a `/clientes/{cliente}/vincular`.
  4. Seleccionar una cuenta de usuario disponible en el selector, ingresar un motivo justificado (mínimo 5 caracteres) y confirmar.
  5. Comprobar la bitácora en `/admin/auditoria`.
- **Resultado esperado:**
  - La interfaz de vinculación es exclusiva para administradores (un empleado CRM recibe 403 Forbidden).
  - La vinculación asocia el ID de usuario al cliente CRM y registra el evento `vincular_cliente_usuario` en la auditoría con el motivo ingresado.
  - Al acceder desde el menú `/erp/clientes/...`, se mantiene intacto el contexto y la barra lateral de navegación ERP.

---

### 4.3 Gestión administrativa de la tienda comercial

#### 4.3.1 Publicación segura y edición de productos comerciales
- **Rol:** Administrador (`admin@ecore.local`).
- **Pasos:**
  1. Ingresar a `/admin/tienda/productos`.
  2. Localizar un recurso interno del sistema que no deba estar en catálogo y comprobar que `visible_tienda` está desactivado.
  3. Para un producto comercial, hacer clic en "Editar" (`/admin/tienda/productos/{producto}/edit`).
  4. Modificar categoría comercial, precio de venta, descripción, imagen y estado de visibilidad o destacado. Guardar.
- **Resultado esperado:**
  - Los recursos técnicos internos no se publican automáticamente.
  - Las modificaciones se reflejan de inmediato en la tienda pública `/catalogo`.

#### 4.3.2 Categorías normalizadas, promociones e impuestos
- **Rol:** Administrador.
- **Pasos:**
  1. Ir a `/admin/tienda/categorias` y crear o editar una categoría.
  2. Ir a `/admin/tienda/promociones` y registrar un cupón de prueba (ej. `PROMO10` con 10% de descuento).
  3. Ir a `/admin/tienda/impuestos` y verificar el estado del IVA o activar/desactivar impuestos aplicables.
- **Resultado esperado:**
  - Las categorías alimentan los filtros del catálogo público.
  - Las promociones validan fechas de vigencia y productos aplicables.
  - Los impuestos activos se reflejan en el desglose formal del carrito y checkout.

#### 4.3.3 Flujo completo de transferencias bancarias demo (Aprobar / Rechazar)
- **Rol:** Cliente (compra) y Administrador (confirmación/rechazo).
- **Pasos:**
  1. Como cliente, realizar una compra en `/checkout` seleccionando el método "Transferencia bancaria / SPEI (Demo)".
  2. Comprobar que la orden y el pago se crean con estado `pendiente`, mostrando instrucciones y CLABE simulada sin descontar stock todavía.
  3. Iniciar sesión como Administrador y navegar a `/admin/tienda/ordenes` (o `/erp/tienda/ordenes`).
  4. Abrir la orden pendiente. Se visualizarán los botones "Confirmar transferencia recibida" y "Rechazar transferencia".
  5. **Caso A (Aprobación):** Hacer clic en "Confirmar transferencia recibida".
     - Se revalida el stock en ese momento.
     - Pago cambia a `aprobado`, orden a `procesada`, se descuenta el stock en SCM y se registra auditoría.
  6. **Caso B (Rechazo):** En otra transferencia pendiente, hacer clic en "Rechazar transferencia".
     - Pago cambia a `rechazado`, orden a `cancelada`, el stock no se descuenta y se registra auditoría.
  7. Probar confirmar o rechazar dos veces una orden ya resuelta: la acción se bloquea con aviso informativo sin duplicar movimientos.
- **Resultado esperado:**
  - Ciclo de vida completo, auditado y consistente sin desincronizaciones entre el ERP y el inventario SCM.

---

### 4.4 Extensiones funcionales: Monedero virtual ECore

#### 4.4.1 Recargas simuladas e idempotencia
- **Rol:** Cliente autenticado.
- **Pasos:**
  1. Navegar a `/monedero` desde el menú del cliente.
  2. En el panel de recarga simulada, ingresar un monto (ej. $1,000.00 MXN) y seleccionar método simulado.
  3. Hacer clic en "Recargar saldo demo".
  4. Comprobar que el saldo se acredita y aparece en el historial de movimientos del monedero.
  5. Repetir la solicitud con la misma clave de idempotencia (o doble clic rápido): no se duplica la recarga.
- **Resultado esperado:**
  - El saldo se actualiza en centavos de forma atómica y auditable.

#### 4.4.2 Pago en checkout con saldo del monedero
- **Rol:** Cliente autenticado con saldo suficiente en monedero.
- **Pasos:**
  1. Agregar productos al carrito y proceder a `/checkout`.
  2. Seleccionar la opción "Monedero virtual ECore".
  3. Confirmar la compra.
- **Resultado esperado:**
  - El total se debita exactamente del saldo del monedero.
  - Se registra el movimiento de débito en `monedero_movimientos` vinculado a la orden.
  - La orden y el pago se procesan inmediatamente.

#### 4.4.3 Ajuste administrativo de monederos por soporte
- **Rol:** Administrador (`admin@ecore.local`).
- **Pasos:**
  1. Entrar a `/admin/tienda/monederos`.
  2. Seleccionar el monedero del cliente (`/admin/tienda/monederos/{monedero}`).
  3. Ingresar un ajuste (crédito o débito) con motivo justificado (ej. "Bonificación demo por resolución de ticket #12").
  4. Guardar ajuste.
- **Resultado esperado:**
  - El saldo se actualiza, se registra el movimiento tipo `ajuste` y queda constancia en la bitácora de auditoría.
