@extends('layouts.app')
@section('title', 'Dashboard SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">Etapa 2 · SCM</span><h1>Dashboard SCM</h1><p>Resumen operativo de capacidad, estrategias y reposición tecnológica.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content dashboard-content">
<div class="admin-summary-grid crm-summary-grid"><article class="admin-summary-card"><span>Recursos IA</span><strong>{{ $totales['productos'] }}</strong></article><article class="admin-summary-card"><span>Proveedores</span><strong>{{ $totales['proveedores'] }}</strong></article><article class="admin-summary-card"><span>Pedidos pendientes</span><strong>{{ $totales['pedidos_pendientes'] }}</strong></article><article class="admin-summary-card"><span>Stock bajo</span><strong>{{ $totales['inventario_critico'] }}</strong></article></div>

<section class="dashboard-panel"><div class="admin-section-heading"><h2>Comparativa PUSH / PULL</h2></div><div class="crm-bar-chart" role="img" aria-label="PUSH {{ $estrategias['porcentaje_push'] }} por ciento, PULL {{ $estrategias['porcentaje_pull'] }} por ciento"><div class="crm-bar-segment is-active" style="width: {{ $estrategias['porcentaje_push'] }}%">PUSH {{ $estrategias['push'] }}</div><div class="crm-bar-segment is-inactive" style="width: {{ $estrategias['porcentaje_pull'] }}%">PULL {{ $estrategias['pull'] }}</div></div></section>

<section class="dashboard-panel"><div class="admin-section-heading"><div><span class="section-eyebrow">Análisis</span><h2>Reportes SCM</h2></div><a class="button button-small" href="{{ route('scm.reportes') }}">Ver reportes</a></div><p>Consulta consumo, rotación, inventario crítico y comparativa mensual PUSH / PULL.</p></section>

<section class="dashboard-panel"><div class="admin-section-heading"><h2>Pedidos recientes</h2></div><ul class="activity-list">@forelse($pedidosRecientes as $pedido)<li><strong>#{{ $pedido->id }} · {{ $pedido->producto->nombre }}</strong><span>{{ $pedido->cantidad }} unidades · {{ $pedido->estado }}</span></li>@empty<li>Sin pedidos.</li>@endforelse</ul></section>
</div></section>
@endsection
