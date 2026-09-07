@extends('layouts.app')

@section('title', 'Registro | ECore Agents')
@section('body-class', 'auth-page')

@section('content')
    <section class="auth-section">
        <article class="auth-card">
            <span class="section-eyebrow">Cuenta personal</span>
            <h1>Crear cuenta</h1>
            <p class="demo-notice">Regístrate para acceder a tu perfil. Todas las cuentas públicas se crean con el rol usuario.</p>
            <form class="auth-form" method="post" action="{{ route('register') }}">
                @csrf
                <label for="register-name">Nombre</label>
                <input id="register-name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus @error('name') aria-invalid="true" @enderror>
                @error('name')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <label for="register-email">Correo electrónico</label>
                <input id="register-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required @error('email') aria-invalid="true" @enderror>
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <label for="register-telefono">Teléfono</label>
                <input id="register-telefono" name="telefono" type="text" value="{{ old('telefono') }}" autocomplete="tel" required @error('telefono') aria-invalid="true" @enderror>
                @error('telefono')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <label for="register-empresa">Empresa (opcional)</label>
                <input id="register-empresa" name="empresa" type="text" value="{{ old('empresa') }}" autocomplete="organization" @error('empresa') aria-invalid="true" @enderror>
                @error('empresa')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <label for="register-password">Contraseña</label>
                <input id="register-password" name="password" type="password" autocomplete="new-password" required @error('password') aria-invalid="true" @enderror>
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <label for="register-password-confirmation">Confirmar contraseña</label>
                <input id="register-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                <button class="button" type="submit">Registrarme</button>
            </form>
            <p class="auth-link">¿Ya tienes cuenta? <a href="{{ route('login') }}">Ir a iniciar sesión</a></p>
        </article>
    </section>
@endsection
