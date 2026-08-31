@extends('layouts.app')

@section('title', 'Mi perfil | ECore Agents')
@section('body-class', 'profile-page')

@section('content')
    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-heading">
                <div><span class="section-eyebrow">Sesión autenticada</span><h1>Mi perfil</h1></div>
                <span class="demo-badge">{{ ucfirst($user->role) }}</span>
            </div>
            <p class="demo-notice">Tus datos de identidad provienen de la sesión activa. Los módulos comerciales permanecen pendientes para etapas posteriores.</p>
            <div class="profile-grid">
                <article class="profile-card"><span class="card-number">01</span><h2>Mis datos</h2><dl><dt>Nombre</dt><dd>{{ $user->name }}</dd><dt>Correo</dt><dd>{{ $user->email }}</dd><dt>Rol</dt><dd>{{ ucfirst($user->role) }}</dd></dl></article>
                <article class="profile-card"><span class="card-number">02</span><h2>Mis planes contratados</h2><p>Aún no hay contrataciones reales.</p></article>
                <article class="profile-card"><span class="card-number">03</span><h2>Historial</h2><p>El historial de compras permanece fuera del alcance de esta etapa.</p></article>
                <article class="profile-card"><span class="card-number">04</span><h2>Próximamente</h2><p>Planes contratados, compras y publicaciones asociadas a tu cuenta.</p></article>
            </div>
            <a class="back-button" href="{{ route('home') }}">Volver al inicio</a>
        </div>
    </section>
@endsection
