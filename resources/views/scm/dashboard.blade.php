@extends('layouts.app')
@section('title', 'Dashboard SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">Etapa 2 · SCM</span><h1>Dashboard SCM</h1><p>Métricas reales de capacidad, consumo y reposición tecnológica.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content dashboard-content">
<div class="admin-summary-grid crm-summary-grid"><article class="admin-summary-card"><span>Recursos IA</span><strong>{{ $totales['productos'] }}</strong></article><article class="admin-summary-card"><span>Proveedores</span><strong>{{ $totales['proveedores'] }}</strong></article><article class="admin-summary-card"><span>Pedidos pendientes</span><strong>{{ $totales['pedidos_pendientes'] }}</strong></article><article class="admin-summary-card"><span>Stock bajo</span><strong>{{ $totales['inventario_critico'] }}</strong></article></div>

<section class="dashboard-panel"><div class="admin-section-heading"><div><span class="section-eyebrow">Últimos 30 días</span><h2>Recursos IA más consumidos</h2></div></div><div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Categoría</th><th>Unidades consumidas</th></tr></thead><tbody>@forelse($masConsumidos as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->categoria }}</td><td>{{ (int) ($producto->consumo_30_dias ?? 0) }}</td></tr>@empty<tr><td colspan="3">Sin recursos registrados.</td></tr>@endforelse</tbody></table></div></section>

<section class="dashboard-panel"><div class="admin-section-heading"><h2>Inventario crítico</h2></div><div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Proveedor</th><th>Stock</th><th>Mínimo</th></tr></thead><tbody>@forelse($criticos as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->proveedor->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>{{ $producto->stock_minimo }}</td></tr>@empty<tr><td colspan="4">No hay inventario crítico.</td></tr>@endforelse</tbody></table></div></section>

<section class="dashboard-panel"><div class="admin-section-heading"><h2>Comparativa PUSH / PULL</h2></div><div class="crm-bar-chart" role="img" aria-label="PUSH {{ $estrategias['porcentaje_push'] }} por ciento, PULL {{ $estrategias['porcentaje_pull'] }} por ciento"><div class="crm-bar-segment is-active" style="width: {{ $estrategias['porcentaje_push'] }}%">PUSH {{ $estrategias['push'] }}</div><div class="crm-bar-segment is-inactive" style="width: {{ $estrategias['porcentaje_pull'] }}%">PULL {{ $estrategias['pull'] }}</div></div></section>

<section class="dashboard-panel"><div class="admin-section-heading"><div><span class="section-eyebrow">Últimos 30 días</span><h2>Recursos IA de baja utilización</h2></div></div><ul class="activity-list">@forelse($bajaUtilizacion as $producto)<li><strong>{{ $producto->nombre }}</strong><span>{{ (int) ($producto->consumo_30_dias ?? 0) }} unidades</span></li>@empty<li>Sin recursos registrados.</li>@endforelse</ul></section>

<section class="dashboard-panel"><div class="admin-section-heading"><h2>Pedidos recientes</h2></div><ul class="activity-list">@forelse($pedidosRecientes as $pedido)<li><strong>#{{ $pedido->id }} · {{ $pedido->producto->nombre }}</strong><span>{{ $pedido->cantidad }} unidades · {{ $pedido->estado }}</span></li>@empty<li>Sin pedidos.</li>@endforelse</ul></section>
</div></section>
@endsection
