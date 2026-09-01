<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CrmDashboardController;
use App\Http\Controllers\InteraccionController;
use App\Http\Controllers\ProfileController;
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
