@extends('layouts.app')
@section('title', 'Restablecer contraseña | ECore Agents')
@section('content')
<section class="section narrow-section">
    <div class="section-heading"><span class="section-eyebrow">Administración</span><h1>Restablecer contraseña</h1><p>Usuario: {{ $usuario->name }} ({{ $usuario->email }})</p></div>
    <form class="form-card" method="post" action="{{ route('admin.usuarios.password.update', $usuario) }}">
        @csrf @method('put')
        <label>Nueva contraseña<input type="password" name="password" required minlength="8"></label>
        <label>Confirmación<input type="password" name="password_confirmation" required minlength="8"></label>
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
        <button class="button" type="submit">Restablecer contraseña</button>
    </form>
</section>
@endsection
