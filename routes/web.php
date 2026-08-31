<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
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

    Route::view('/admin', 'admin.index')->middleware('admin')->name('admin');
});
