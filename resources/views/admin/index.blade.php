@extends('layouts.app')

@section('title', 'Shell administrativo | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero">
        <span class="section-eyebrow">Base visual administrativa</span>
        <h1>Panel de administrador</h1>
        <p>Shell preparado para construir el CRM real en las siguientes etapas. No muestra datos de negocio reales.</p>
    </section>

    <section class="admin-layout" data-admin-shell>
        <aside class="admin-sidebar" aria-label="Menú administrativo">
            <div><span class="demo-badge">Etapa 1</span><h2>Menú</h2></div>
            <button class="admin-menu-button is-active" type="button" data-admin-target="dashboard" aria-pressed="true">Dashboard</button>
            <button class="admin-menu-button" type="button" data-admin-target="customers" aria-pressed="false">Clientes</button>
            <button class="admin-menu-button" type="button" data-admin-target="interactions" aria-pressed="false">Interacciones</button>
            <button class="admin-menu-button" type="button" data-admin-target="reports" aria-pressed="false">Reportes</button>
            <button class="admin-menu-button" type="button" data-admin-target="settings" aria-pressed="false">Configuración</button>
        </aside>

        <div class="admin-content">
            <section class="admin-section is-active" data-admin-section="dashboard">
                <div class="admin-section-heading"><div><span class="section-eyebrow">Vista general</span><h2>Dashboard</h2></div><p>Contenedores listos para recibir datos reales del CRM.</p></div>
                <div class="admin-summary-grid">
                    <article class="admin-summary-card"><span>Clientes</span><strong>Sin datos</strong><small>Etapa CRM pendiente</small></article>
                    <article class="admin-summary-card"><span>Interacciones</span><strong>Sin datos</strong><small>Etapa CRM pendiente</small></article>
                    <article class="admin-summary-card"><span>Seguimientos</span><strong>Sin datos</strong><small>Etapa CRM pendiente</small></article>
                    <article class="admin-summary-card"><span>Actividad</span><strong>Sin datos</strong><small>Etapa CRM pendiente</small></article>
                </div>
                <div class="admin-placeholder-grid">
                    <article class="admin-placeholder"><span class="placeholder-icon">+</span><h3>Acciones rápidas</h3><p>Los accesos para crear clientes e interacciones se agregarán con el backend real.</p></article>
                    <article class="admin-placeholder"><span class="placeholder-icon">≡</span><h3>Actividad reciente</h3><p>No se muestran pedidos, clientes ni métricas ficticias como datos reales.</p></article>
                </div>
            </section>

            @foreach([
                'customers' => ['Clientes', 'Aquí se construirá el listado y seguimiento real de clientes del CRM.'],
                'interactions' => ['Interacciones', 'Aquí se registrará el historial real de llamadas, correos o reuniones.'],
                'reports' => ['Reportes', 'Las métricas aparecerán cuando existan datos persistidos y criterios definidos.'],
                'settings' => ['Configuración', 'La seguridad, roles y preferencias se implementarán en etapas posteriores.'],
            ] as $key => [$title, $copy])
                <section class="admin-section" data-admin-section="{{ $key }}" hidden>
                    <div class="admin-section-heading"><div><span class="section-eyebrow">Módulo pendiente</span><h2>{{ $title }}</h2></div></div>
                    <article class="admin-empty-state"><span class="placeholder-icon">{{ strtoupper(substr($title, 0, 1)) }}</span><h3>Base visual lista</h3><p>{{ $copy }}</p><span class="demo-badge">Sin datos simulados</span></article>
                </section>
            @endforeach
        </div>
    </section>
@endsection
