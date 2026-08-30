@extends('layouts.app')

@section('title', 'Iniciar sesión | ECore Agents')
@section('body-class', 'auth-page')

@section('content')
    <section class="auth-section">
        <article class="auth-card">
            <span class="section-eyebrow">Próxima etapa</span>
            <h1>Iniciar sesión</h1>
            <p class="demo-notice">Vista preparada para la autenticación real de la Etapa 2. Actualmente no inicia ninguna sesión.</p>
            <form class="auth-form" method="post" action="#" data-demo-form data-demo-message="Inicio de sesión no disponible en la Etapa 1. No se enviaron ni guardaron datos.">
                @csrf
                <label for="login-email">Correo electrónico</label>
                <input id="login-email" name="email" type="email" autocomplete="email">
                <label for="login-password">Contraseña</label>
                <input id="login-password" name="password" type="password" autocomplete="current-password">
                <button class="button" type="submit">Entrar (próximamente)</button>
                <p class="form-message" data-form-message role="status" aria-live="polite"></p>
            </form>
            <p class="auth-link">¿Aún no tienes cuenta? <a href="{{ route('register') }}">Crear una cuenta</a></p>
        </article>
    </section>
@endsection
