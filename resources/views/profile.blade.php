@extends('layouts.app')

@section('title', 'Perfil demostrativo | ECore Agents')
@section('body-class', 'profile-page')

@section('content')
    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-heading">
                <div><span class="section-eyebrow">Referencia visual</span><h1>Mi perfil</h1></div>
                <span class="demo-badge">Contenido demo</span>
            </div>
            <p class="demo-notice">Esta pantalla no representa una sesión activa. Los datos reales del usuario se mostrarán cuando se implemente autenticación en la Etapa 2.</p>
            <div class="profile-grid">
                <article class="profile-card"><span class="card-number">01</span><h2>Mis datos</h2><dl><dt>Nombre</dt><dd>Usuario demostrativo</dd><dt>Correo</dt><dd>Sin sesión activa</dd></dl></article>
                <article class="profile-card"><span class="card-number">02</span><h2>Mis planes contratados</h2><p>Aún no hay contrataciones reales.</p></article>
                <article class="profile-card"><span class="card-number">03</span><h2>Historial</h2><p>El historial de compras permanece fuera del alcance de esta etapa.</p></article>
                <article class="profile-card"><span class="card-number">04</span><h2>Próximamente</h2><p>Autenticación real, seguridad de sesión y datos asociados al usuario.</p></article>
            </div>
            <a class="back-button" href="{{ route('home') }}">Volver al inicio</a>
        </div>
    </section>
@endsection
