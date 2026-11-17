@extends('layouts.app')
@section('title', 'Restablecer contraseña | ECore Agents')
@section('content')
<section class="auth-section"><article class="auth-card">
    <h1>Crear nueva contraseña</h1>
    <form class="auth-form" method="post" action="{{ route('password.update') }}">@csrf
        <label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" required>
        <label for="code">Código de seis dígitos</label><input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
        <label for="password">Nueva contraseña</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
        <label for="confirm">Confirmar contraseña</label><input id="confirm" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
        <button class="button" type="submit">Guardar contraseña</button>
    </form>
</article></section>
@endsection
