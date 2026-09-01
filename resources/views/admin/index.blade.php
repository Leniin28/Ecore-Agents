@extends('layouts.app')

@section('title', 'Dashboard CRM | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero">
        <span class="section-eyebrow">CRM</span>
        <h1>Dashboard CRM</h1>
        <p>Indicadores reales de clientes e interacciones registrados en el sistema.</p>
    </section>

    <section class="admin-layout">
        @include('admin._sidebar')

        <div class="admin-content dashboard-content">
            <section aria-labelledby="metricas-title">
                <div class="admin-section-heading">
                    <div><span class="section-eyebrow">Vista general</span><h2 id="metricas-title">Métricas CRM</h2></div>
                    <p>Una interacción es reciente si ocurrió durante los últimos {{ $diasInteraccionReciente }} días.</p>
                </div>

                <div class="admin-summary-grid crm-summary-grid">
                    <article class="admin-summary-card"><span>Total de clientes</span><strong>{{ $metricas['total_clientes'] }}</strong><small>Registrados en SQLite</small></article>
                    <article class="admin-summary-card"><span>Clientes activos</span><strong>{{ $metricas['clientes_activos'] }}</strong><small>Estado activo</small></article>
                    <article class="admin-summary-card"><span>Clientes inactivos</span><strong>{{ $metricas['clientes_inactivos'] }}</strong><small>Estado inactivo</small></article>
                    <article class="admin-summary-card"><span>Interacciones</span><strong>{{ $metricas['total_interacciones'] }}</strong><small>Llamadas, correos y reuniones</small></article>
                    <article class="admin-summary-card"><span>Sin interacción reciente</span><strong>{{ $metricas['clientes_sin_interaccion_reciente'] }}</strong><small>Todos los estados</small></article>
                </div>
            </section>

            <section class="dashboard-panel" aria-labelledby="grafica-title">
                <div class="dashboard-panel-heading"><div><span class="section-eyebrow">Distribución</span><h2 id="grafica-title">Clientes activos vs. inactivos</h2></div><span>{{ $metricas['total_clientes'] }} en total</span></div>
                @if($metricas['total_clientes'] === 0)
                    <p class="dashboard-empty">Aún no hay clientes para mostrar en la gráfica.</p>
                @else
                    <div class="crm-bar-chart" role="img" aria-label="Clientes activos {{ $porcentajeActivos }} por ciento, clientes inactivos {{ $porcentajeInactivos }} por ciento">
                        <div class="crm-bar-row"><div class="crm-bar-label"><span>Activos</span><strong>{{ $metricas['clientes_activos'] }} · {{ $porcentajeActivos }}%</strong></div><div class="crm-bar-track"><span class="crm-bar-fill bar-active" style="width: {{ $porcentajeActivos }}%"></span></div></div>
                        <div class="crm-bar-row"><div class="crm-bar-label"><span>Inactivos</span><strong>{{ $metricas['clientes_inactivos'] }} · {{ $porcentajeInactivos }}%</strong></div><div class="crm-bar-track"><span class="crm-bar-fill bar-inactive" style="width: {{ $porcentajeInactivos }}%"></span></div></div>
                    </div>
                @endif
            </section>

            <div class="dashboard-columns">
                <section class="dashboard-panel" aria-labelledby="riesgo-title">
                    <div class="dashboard-panel-heading"><div><span class="section-eyebrow">Seguimiento</span><h2 id="riesgo-title">Clientes en riesgo</h2></div><span>{{ $clientesEnRiesgo->count() }}</span></div>
                    @if($clientesEnRiesgo->isEmpty())
                        <p class="dashboard-empty">No hay clientes activos sin contacto reciente.</p>
                    @else
                        <div class="dashboard-list">
                            @foreach($clientesEnRiesgo as $cliente)
                                <article class="dashboard-list-item">
                                    <div><a href="{{ route('clientes.show', $cliente) }}">{{ $cliente->nombre }}</a><small>{{ $cliente->empresa ?: 'Sin empresa' }}</small></div>
                                    <span class="stage-badge stage-{{ $cliente->etapa_crm }}">{{ $cliente->etapaCrmLabel() }}</span>
                                    @if($cliente->ultimaInteraccion)
                                        <small>Último contacto: {{ $cliente->ultimaInteraccion->fecha->format('d/m/Y') }} · {{ $cliente->dias_sin_interaccion }} días</small>
                                    @else
                                        <small>Sin interacciones registradas</small>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="dashboard-panel" aria-labelledby="actividad-title">
                    <div class="dashboard-panel-heading"><div><span class="section-eyebrow">Actividad</span><h2 id="actividad-title">Clientes con mayor actividad</h2></div><span>Top 5</span></div>
                    @if($clientesConMayorActividad->isEmpty())
                        <p class="dashboard-empty">Aún no hay clientes registrados.</p>
                    @else
                        <ol class="activity-ranking">
                            @foreach($clientesConMayorActividad as $cliente)
                                <li><a href="{{ route('clientes.show', $cliente) }}">{{ $cliente->nombre }}</a><strong>{{ $cliente->interacciones_count }} {{ $cliente->interacciones_count === 1 ? 'interacción' : 'interacciones' }}</strong></li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>
        </div>
    </section>
@endsection
