<?php

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

Route::view('/login', 'auth.login')->name('login');
Route::view('/registro', 'auth.register')->name('register');
Route::view('/perfil', 'profile')->name('profile');
Route::view('/admin', 'admin.index')->name('admin');
