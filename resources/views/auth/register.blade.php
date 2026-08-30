@extends('layouts.app')

@section('title', 'Registro | ECore Agents')
@section('body-class', 'auth-page')

@section('content')
    <section class="auth-section">
        <article class="auth-card">
            <span class="section-eyebrow">Próxima etapa</span>
            <h1>Crear cuenta</h1>
            <p class="demo-notice">Formulario visual preparado para la autenticación real. No crea usuarios ni simula una sesión.</p>
            <form class="auth-form" method="post" action="#" data-demo-form data-demo-message="Registro no disponible en la Etapa 1. No se enviaron ni guardaron datos.">
                @csrf
                <label for="register-name">Nombre</label>
                <input id="register-name" name="name" type="text" autocomplete="name">
                <label for="register-email">Correo electrónico</label>
                <input id="register-email" name="email" type="email" autocomplete="email">
                <label for="register-password">Contraseña</label>
                <input id="register-password" name="password" type="password" autocomplete="new-password">
                <button class="button" type="submit">Registrarme (próximamente)</button>
                <p class="form-message" data-form-message role="status" aria-live="polite"></p>
            </form>
            <p class="auth-link">¿Ya tienes cuenta? <a href="{{ route('login') }}">Ir a iniciar sesión</a></p>
        </article>
    </section>
@endsection
