<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\Tienda\CatalogoAdminController;
use App\Http\Controllers\Admin\Tienda\CategoriaAdminController;
use App\Http\Controllers\Admin\Tienda\ImpuestoAdminController;
use App\Http\Controllers\Admin\Tienda\MonederoAdminController;
use App\Http\Controllers\Admin\Tienda\OrdenAdminController;
use App\Http\Controllers\Admin\Tienda\PromocionAdminController;
use App\Http\Controllers\Admin\Tienda\TicketAdminController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CrmDashboardController;
use App\Http\Controllers\Erp\DashboardController as ErpDashboardController;
use App\Http\Controllers\Erp\EstadoController as ErpEstadoController;
use App\Http\Controllers\Erp\OrdenController as ErpOrdenController;
use App\Http\Controllers\Erp\ProcesoController as ErpProcesoController;
use App\Http\Controllers\Erp\RecursoController as ErpRecursoController;
use App\Http\Controllers\InteraccionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Scm\DashboardController as ScmDashboardController;
use App\Http\Controllers\Scm\InventarioController;
use App\Http\Controllers\Scm\LogisticaController;
use App\Http\Controllers\Scm\MadurezController;
use App\Http\Controllers\Scm\MovimientoInventarioController;
use App\Http\Controllers\Scm\PedidoController;
use App\Http\Controllers\Scm\ProductoController;
use App\Http\Controllers\Scm\ProveedorController;
use App\Http\Controllers\Tienda\CarritoController;
use App\Http\Controllers\Tienda\CatalogoController;
use App\Http\Controllers\Tienda\CheckoutController;
use App\Http\Controllers\Tienda\ConfiguradorController;
use App\Http\Controllers\Tienda\MisComprasController;
use App\Http\Controllers\Tienda\MonederoController;
use App\Http\Controllers\Tienda\TicketController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de Catálogo y Tienda
Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/catalogo/{producto}', [CatalogoController::class, 'show'])->name('catalogo.show');

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/planes', function () {
    return view('plans.index', ['plans' => config('ecore.plans')]);
})->name('plans.index');

Route::get('/planes/{plan}', function (string $plan) {
    $planData = config("ecore.plans.{$plan}");

    abort_unless($planData, 404);

    return view('plans.show', ['plan' => $planData]);
})->name('plans.show');

// Configurador interactivo integrado al carrito
Route::get('/agente-personalizado', [ConfiguradorController::class, 'index'])->name('plans.custom');
Route::post('/agente-personalizado/agregar', [ConfiguradorController::class, 'agregar'])->name('plans.custom.agregar');

// Carrito persistente
Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
Route::post('/carrito', [CarritoController::class, 'store'])->name('carrito.store');
Route::put('/carrito/{detalle}', [CarritoController::class, 'update'])->name('carrito.update');
Route::delete('/carrito/{detalle}', [CarritoController::class, 'destroy'])->name('carrito.destroy');
Route::post('/carrito/vaciar', [CarritoController::class, 'vaciar'])->name('carrito.vaciar');
Route::post('/carrito/promocion', [CarritoController::class, 'aplicarPromocion'])->name('carrito.promocion.aplicar');
Route::delete('/carrito/promocion', [CarritoController::class, 'quitarPromocion'])->name('carrito.promocion.quitar');

Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/registro', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    // Portal del Cliente: Checkout, Mis Compras, Monedero y Tickets
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/pagos/confirmacion/{referencia}', [CheckoutController::class, 'confirmacion'])->name('pagos.confirmacion');

    Route::get('/mis-compras', [MisComprasController::class, 'index'])->name('mis-compras.index');
    Route::get('/mis-compras/{orden}', [MisComprasController::class, 'show'])->name('mis-compras.show');

    Route::get('/monedero', [MonederoController::class, 'index'])->name('monedero.index');
    Route::post('/monedero/recargar', [MonederoController::class, 'recargar'])->name('monedero.recargar');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/crear', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    Route::prefix('erp')->name('erp.')->middleware('module:erp')->group(function () {
        // Mismas operaciones y tablas, presentadas dentro del área ERP.
        Route::middleware('module:crm')->group(function () {
            Route::resource('clientes', ClienteController::class);
            Route::put('/clientes/{cliente}/etapa', [ClienteController::class, 'updateEtapa'])->name('clientes.etapa.update');
            Route::get('/clientes/{cliente}/interacciones', [InteraccionController::class, 'index'])->name('clientes.interacciones.index');
            Route::post('/interacciones', [InteraccionController::class, 'store'])->name('interacciones.store');
        });
        Route::middleware('module:scm')->group(function () {
            Route::resource('productos', ProductoController::class)->except('show');
            Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
            Route::get('/movimientos', [MovimientoInventarioController::class, 'index'])->name('movimientos.index');
            Route::get('/movimientos/create', [MovimientoInventarioController::class, 'create'])->name('movimientos.create');
            Route::post('/movimientos', [MovimientoInventarioController::class, 'store'])->name('movimientos.store');
            Route::get('/productos/{producto}/movimientos', [MovimientoInventarioController::class, 'porProducto'])->name('productos.movimientos');
        });
        Route::middleware('admin')->group(function () {
            Route::resource('usuarios', AdminUserController::class)->except('show');
            Route::get('/usuarios/{usuario}/password', [AdminUserController::class, 'editPassword'])->name('usuarios.password.edit');
            Route::put('/usuarios/{usuario}/password', [AdminUserController::class, 'updatePassword'])->name('usuarios.password.update');
        });
        Route::get('/recursos', [ErpRecursoController::class, 'index'])->name('recursos');
        Route::get('/', [ErpDashboardController::class, 'index'])->name('dashboard');
        Route::resource('ordenes', ErpOrdenController::class)->only(['index', 'create', 'store', 'show'])->parameters(['ordenes' => 'orden']);
        Route::post('/ordenes/{orden}/procesar', [ErpOrdenController::class, 'procesar'])->name('ordenes.procesar');
        Route::post('/ordenes/{orden}/cancelar', [ErpOrdenController::class, 'cancelar'])->name('ordenes.cancelar');
        Route::get('/ordenes/{orden}/exito', [ErpOrdenController::class, 'exito'])->name('ordenes.exito');
        Route::get('/procesos', [ErpProcesoController::class, 'index'])->name('procesos');
        Route::get('/nivel', [ErpEstadoController::class, 'index'])->name('nivel');
        Route::get('/estado', [ErpEstadoController::class, 'estado'])->name('estado');
        Route::put('/estado', [ErpEstadoController::class, 'update'])->name('estado.update');
        Route::get('/reportes', [ErpDashboardController::class, 'reportes'])->name('reportes');

        // Módulo de compras comerciales visto desde ERP
        Route::get('/tienda/ordenes', [OrdenAdminController::class, 'index'])->name('tienda.ordenes.index');
        Route::get('/tienda/ordenes/{pago}', [OrdenAdminController::class, 'show'])->name('tienda.ordenes.show');
    });

    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('api/erp')->name('api.erp.')->middleware('module:erp')->group(function () {
        Route::get('/metricas', [ErpDashboardController::class, 'metricas'])->name('metricas');
        Route::get('/estado', [ErpEstadoController::class, 'estado'])->name('estado');
        Route::put('/estado', [ErpEstadoController::class, 'apiUpdate'])->name('estado.update');
        Route::get('/ordenes', [ErpOrdenController::class, 'apiIndex'])->name('ordenes.index');
        Route::post('/ordenes', [ErpOrdenController::class, 'apiStore'])->name('ordenes.store');
        Route::get('/ordenes/{orden}', [ErpOrdenController::class, 'apiShow'])->name('ordenes.show');
        Route::post('/ordenes/{orden}/procesar', [ErpOrdenController::class, 'apiProcesar'])->name('ordenes.procesar');
        Route::post('/ordenes/{orden}/cancelar', [ErpOrdenController::class, 'apiCancelar'])->name('ordenes.cancelar');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('module:crm')->group(function () {
        Route::get('/admin', [CrmDashboardController::class, 'index'])->name('admin');
        Route::get('/mi-actividad', [CrmDashboardController::class, 'miActividad'])->name('mi-actividad');

        Route::resource('clientes', ClienteController::class);
        Route::put('/clientes/{cliente}/etapa', [ClienteController::class, 'updateEtapa'])->name('clientes.etapa.update');
        Route::get('/clientes/{cliente}/interacciones', [InteraccionController::class, 'index'])->name('clientes.interacciones.index');
        Route::post('/interacciones', [InteraccionController::class, 'store'])->name('interacciones.store');

        // Atención a tickets de soporte para CRM
        Route::get('/crm/tickets', [TicketAdminController::class, 'index'])->name('crm.tickets.index');
        Route::get('/crm/tickets/{ticket}', [TicketAdminController::class, 'show'])->name('crm.tickets.show');
        Route::post('/crm/tickets/{ticket}/responder', [TicketAdminController::class, 'responder'])->name('crm.tickets.responder');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/crm/metricas', [CrmDashboardController::class, 'apiMetricas'])->name('crm.metricas');
            Route::get('/clientes', [ClienteController::class, 'apiIndex'])->name('clientes.index');
            Route::post('/clientes', [ClienteController::class, 'apiStore'])->name('clientes.store');
            Route::get('/clientes/{cliente}', [ClienteController::class, 'apiShow'])->name('clientes.show');
            Route::put('/clientes/{cliente}', [ClienteController::class, 'apiUpdate'])->name('clientes.update');
            Route::delete('/clientes/{cliente}', [ClienteController::class, 'apiDestroy'])->name('clientes.destroy');
            Route::put('/clientes/{cliente}/etapa', [ClienteController::class, 'apiUpdateEtapa'])->name('clientes.etapa.update');
            Route::get('/clientes/{cliente}/interacciones', [InteraccionController::class, 'apiIndex'])->name('clientes.interacciones.index');
            Route::post('/interacciones', [InteraccionController::class, 'apiStore'])->name('interacciones.store');
        });
    });

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::resource('usuarios', AdminUserController::class)->except(['show']);
        Route::get('/usuarios/{usuario}/password', [AdminUserController::class, 'editPassword'])->name('usuarios.password.edit');
        Route::put('/usuarios/{usuario}/password', [AdminUserController::class, 'updatePassword'])->name('usuarios.password.update');
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');

        // Módulo de Administración de la Tienda Comercial ECore
        Route::prefix('tienda')->name('tienda.')->group(function () {
            // Catálogo comercial y publicación segura
            Route::get('/productos', [CatalogoAdminController::class, 'index'])->name('productos.index');
            Route::get('/productos/{producto}/edit', [CatalogoAdminController::class, 'edit'])->name('productos.edit');
            Route::put('/productos/{producto}', [CatalogoAdminController::class, 'update'])->name('productos.update');
            Route::post('/productos/{producto}/toggle', [CatalogoAdminController::class, 'toggleVisibilidad'])->name('productos.toggle');

            // Categorías normalizadas
            Route::resource('categorias', CategoriaAdminController::class)->except(['show']);

            // Promociones
            Route::resource('promociones', PromocionAdminController::class)->except(['show']);

            // Impuestos
            Route::resource('impuestos', ImpuestoAdminController::class)->except(['show']);
            Route::post('/impuestos/{impuesto}/toggle', [ImpuestoAdminController::class, 'toggle'])->name('impuestos.toggle');

            // Monederos de clientes y ajustes auditados
            Route::get('/monederos', [MonederoAdminController::class, 'index'])->name('monederos.index');
            Route::get('/monederos/{monedero}', [MonederoAdminController::class, 'show'])->name('monederos.show');
            Route::post('/monederos/{monedero}/ajustar', [MonederoAdminController::class, 'ajustar'])->name('monederos.ajustar');

            // Tickets de soporte
            Route::get('/tickets', [TicketAdminController::class, 'index'])->name('tickets.index');
            Route::get('/tickets/{ticket}', [TicketAdminController::class, 'show'])->name('tickets.show');
            Route::post('/tickets/{ticket}/responder', [TicketAdminController::class, 'responder'])->name('tickets.responder');

            // Compras y pagos de la tienda
            Route::get('/ordenes', [OrdenAdminController::class, 'index'])->name('ordenes.index');
            Route::get('/ordenes/{pago}', [OrdenAdminController::class, 'show'])->name('ordenes.show');
            Route::post('/ordenes/{pago}/confirmar-transferencia', [OrdenAdminController::class, 'confirmarTransferencia'])->name('ordenes.confirmar-transferencia');
            Route::post('/ordenes/{pago}/rechazar-transferencia', [OrdenAdminController::class, 'rechazarTransferencia'])->name('ordenes.rechazar-transferencia');
        });
    });

    Route::prefix('scm')->name('scm.')->middleware('module:scm')->group(function () {
        Route::get('/', [ScmDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reportes', [ScmDashboardController::class, 'reportes'])->name('reportes');
        Route::resource('proveedores', ProveedorController::class)->except('show')->parameters(['proveedores' => 'proveedor']);
        Route::resource('productos', ProductoController::class)->except('show');
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::get('/movimientos', [MovimientoInventarioController::class, 'index'])->name('movimientos.index');
        Route::get('/movimientos/create', [MovimientoInventarioController::class, 'create'])->name('movimientos.create');
        Route::post('/movimientos', [MovimientoInventarioController::class, 'store'])->name('movimientos.store');
        Route::get('/productos/{producto}/movimientos', [MovimientoInventarioController::class, 'porProducto'])->name('productos.movimientos');
        Route::get('/logistica', [LogisticaController::class, 'index'])->name('logistica.index');
        Route::get('/logistica/comparativa', [LogisticaController::class, 'comparativa'])->name('logistica.comparativa');
        Route::put('/productos/{producto}/estrategia', [LogisticaController::class, 'update'])->name('productos.estrategia.update');
        Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/create', [PedidoController::class, 'create'])->name('pedidos.create');
        Route::post('/pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
        Route::put('/pedidos/{pedido}/estado', [PedidoController::class, 'surtir'])->name('pedidos.estado.update');
        Route::get('/madurez', [MadurezController::class, 'index'])->name('madurez.index');
        Route::put('/nivel', [MadurezController::class, 'update'])->name('nivel.update');
        Route::get('/estado', [MadurezController::class, 'estado'])->name('estado');
    });

    Route::prefix('api/scm')->name('api.scm.')->middleware('module:scm')->group(function () {
        Route::get('/proveedores', [ProveedorController::class, 'apiIndex'])->name('proveedores.index');
        Route::post('/proveedores', [ProveedorController::class, 'apiStore'])->name('proveedores.store');
        Route::put('/proveedores/{proveedor}', [ProveedorController::class, 'apiUpdate'])->name('proveedores.update');
        Route::delete('/proveedores/{proveedor}', [ProveedorController::class, 'apiDestroy'])->name('proveedores.destroy');
        Route::get('/productos', [ProductoController::class, 'apiIndex'])->name('productos.index');
        Route::post('/productos', [ProductoController::class, 'apiStore'])->name('productos.store');
        Route::put('/productos/{producto}', [ProductoController::class, 'apiUpdate'])->name('productos.update');
        Route::delete('/productos/{producto}', [ProductoController::class, 'apiDestroy'])->name('productos.destroy');
        Route::get('/movimientos', [MovimientoInventarioController::class, 'apiIndex'])->name('movimientos.index');
        Route::post('/inventario/movimiento', [MovimientoInventarioController::class, 'apiStore'])->name('movimientos.store');
        Route::get('/productos/{producto}/movimientos', [MovimientoInventarioController::class, 'apiPorProducto'])->name('productos.movimientos');
        Route::put('/productos/{producto}/estrategia', [LogisticaController::class, 'apiUpdate'])->name('productos.estrategia.update');
        Route::get('/pedidos', [PedidoController::class, 'apiIndex'])->name('pedidos.index');
        Route::post('/pedidos', [PedidoController::class, 'apiStore'])->name('pedidos.store');
        Route::put('/pedidos/{pedido}/estado', [PedidoController::class, 'apiUpdateEstado'])->name('pedidos.estado.update');
        Route::get('/metricas', [ScmDashboardController::class, 'apiMetricas'])->name('metricas');
        Route::get('/estado', [MadurezController::class, 'estado'])->name('estado');
        Route::put('/nivel', [MadurezController::class, 'apiUpdate'])->name('nivel.update');
    });
});
