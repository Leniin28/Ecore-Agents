<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CrmDashboardController;
use App\Http\Controllers\InteraccionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Scm\ProductoController;
use App\Http\Controllers\Scm\ProveedorController;
use App\Http\Controllers\Scm\InventarioController;
use App\Http\Controllers\Scm\MovimientoInventarioController;
use App\Http\Controllers\Scm\LogisticaController;
use App\Http\Controllers\Scm\PedidoController;
use App\Http\Controllers\Scm\DashboardController as ScmDashboardController;
use App\Http\Controllers\Scm\MadurezController;
use Illuminate\Support\Facades\Route;

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

Route::get('/agente-personalizado', function () {
    return view('plans.custom', ['modules' => config('ecore.modules')]);
})->name('plans.custom');

Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/registro', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('module:crm')->group(function () {
        Route::get('/admin', [CrmDashboardController::class, 'index'])->name('admin');
        Route::get('/mi-actividad', [CrmDashboardController::class, 'miActividad'])->name('mi-actividad');

        Route::resource('clientes', ClienteController::class);
        Route::put('/clientes/{cliente}/etapa', [ClienteController::class, 'updateEtapa'])->name('clientes.etapa.update');
        Route::get('/clientes/{cliente}/interacciones', [InteraccionController::class, 'index'])->name('clientes.interacciones.index');
        Route::post('/interacciones', [InteraccionController::class, 'store'])->name('interacciones.store');

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
        Route::resource('usuarios', AdminUserController::class)->except(['show', 'destroy']);
        Route::get('/usuarios/{usuario}/password', [AdminUserController::class, 'editPassword'])->name('usuarios.password.edit');
        Route::put('/usuarios/{usuario}/password', [AdminUserController::class, 'updatePassword'])->name('usuarios.password.update');
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    });

    Route::prefix('scm')->name('scm.')->middleware('module:scm')->group(function () {
        Route::get('/', [ScmDashboardController::class, 'index'])->name('dashboard');
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
