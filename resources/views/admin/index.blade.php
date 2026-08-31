@extends('layouts.app')

@section('title', 'Administración | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero">
        <span class="section-eyebrow">Administración</span>
        <h1>Panel de administrador</h1>
        <p>Acceso administrativo y punto de entrada al CRM inicial de clientes.</p>
    </section>

    <section class="admin-layout">
        @include('admin._sidebar')

        <div class="admin-content">
            <section class="admin-section is-active">
                <div class="admin-section-heading"><div><span class="section-eyebrow">Vista general</span><h2>Dashboard</h2></div><p>Las métricas CRM se incorporarán en una etapa posterior.</p></div>
                <div class="admin-summary-grid">
                    <article class="admin-summary-card"><span>Clientes</span><strong>CRUD disponible</strong><small>Persistencia SQLite</small></article>
                    <article class="admin-summary-card"><span>Interacciones</span><strong>En clientes</strong><small>Historial CRM</small></article>
                    <article class="admin-summary-card"><span>Métricas</span><strong>Sin implementar</strong><small>Fuera del alcance actual</small></article>
                    <article class="admin-summary-card"><span>API</span><strong>REST disponible</strong><small>Protegida por sesión</small></article>
                </div>
                <div class="admin-placeholder-grid">
                    <article class="admin-placeholder"><span class="placeholder-icon">+</span><h3>Gestión de clientes</h3><p>Centraliza la información básica de los clientes en un CRUD real.</p><a class="button" href="{{ route('clientes.index') }}">Abrir clientes</a></article>
                    <article class="admin-placeholder"><span class="placeholder-icon">≡</span><h3>Historial de interacciones</h3><p>Las interacciones se implementarán en la siguiente etapa.</p></article>
                </div>
            </section>
        </div>
    </section>
@endsection
