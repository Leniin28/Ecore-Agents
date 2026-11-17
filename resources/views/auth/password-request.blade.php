@extends('layouts.app')
@section('title', 'Recuperar contraseña | ECore Agents')
@section('content')
<section class="auth-section"><article class="auth-card">
    <h1>Recuperar contraseña</h1>
    <p>Escribe el correo de tu cuenta. Si está registrado, recibirás un código para crear una nueva contraseña.</p>
    <form class="auth-form" method="post" action="{{ route('password.email') }}">@csrf
        <label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" required>
        <button class="button" type="submit">Solicitar código</button>
    </form>
    <p class="auth-link"><a href="{{ route('login') }}">Volver a iniciar sesión</a></p>
</article></section>
@endsection
