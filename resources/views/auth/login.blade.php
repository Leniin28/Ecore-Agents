@extends('layouts.app')

@section('title', 'Iniciar sesión | ECore Agents')
@section('body-class', 'auth-page')

@section('content')
    <section class="auth-section">
        <article class="auth-card">
            <span class="section-eyebrow">Acceso seguro</span>
            <h1>Iniciar sesión</h1>
            <p class="demo-notice">Ingresa con tu correo y contraseña. La sesión se gestiona de forma segura mediante Laravel.</p>
            <form class="auth-form" method="post" action="{{ route('login') }}">
                @csrf
                <label for="login-email">Correo electrónico</label>
                <input id="login-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus @error('email') aria-invalid="true" @enderror>
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <label for="login-password">Contraseña</label>
                <input id="login-password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <button class="button" type="submit">Entrar</button>
            </form>
            <p class="auth-link">¿Aún no tienes cuenta? <a href="{{ route('register') }}">Crear una cuenta</a></p>
        </article>
    </section>
@endsection
