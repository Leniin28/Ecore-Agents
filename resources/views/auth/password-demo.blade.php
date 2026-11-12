@extends('layouts.app')
@section('title', 'Recuperar contraseña | ECore Agents')
@section('content')
<section class="auth-section"><article class="auth-card">
    <span class="section-eyebrow">Demostración local</span>
    <h1>Recuperar contraseña</h1>
    <p>En esta demostración local puedes cambiar la contraseña indicando el correo de la cuenta. No se envía un correo de verificación.</p>
    <form class="auth-form" method="post" action="{{ route('password.demo.store') }}">
        @csrf
        <label for="reset-email">Correo electrónico</label>
        <input id="reset-email" name="email" type="email" value="{{ old('email') }}" required>
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
        <label for="reset-password">Nueva contraseña</label>
        <input id="reset-password" name="password" type="password" minlength="8" autocomplete="new-password" required>
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
        <label for="reset-confirm">Confirmar contraseña</label>
        <input id="reset-confirm" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
        <button class="button" type="submit">Cambiar contraseña</button>
    </form>
    <p class="auth-link"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
</article></section>
@endsection
