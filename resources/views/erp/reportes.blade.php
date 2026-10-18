@extends('erp.layout')
@section('erp-title', 'Reportes ERP')
@section('erp-description', 'Ventas de capacidad IA e información empresarial integrada del CRM y SCM.')
@section('erp-content')
@include('erp._metricas')
@include('erp._graficas')
<section class="dashboard-panel"><h2>Clientes con mayor actividad comercial</h2><div class="table-wrap"><table><thead><tr><th>Cliente</th><th>Empresa</th><th>Órdenes procesadas</th><th>Ventas</th></tr></thead><tbody>@forelse($clientesComerciales as $cliente)<tr><td>{{ $cliente->nombre }}</td><td>{{ $cliente->empresa ?? '—' }}</td><td>{{ $cliente->ordenes_procesadas }}</td><td>${{ number_format((float) $cliente->ventas_totales, 2) }}</td></tr>@empty<tr><td colspan="4">Sin actividad comercial procesada.</td></tr>@endforelse</tbody></table></div></section>
<section class="dashboard-panel"><h2>Inventario disponible SCM</h2><div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Proveedor</th><th>Unidades disponibles</th><th>Stock mínimo</th><th>Estado</th></tr></thead><tbody>@forelse($inventario as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->proveedor->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>{{ $producto->stock_minimo }}</td><td>{{ $producto->stock_actual <= $producto->stock_minimo ? 'Stock bajo' : 'Disponible' }}</td></tr>@empty<tr><td colspan="5">Sin inventario registrado.</td></tr>@endforelse</tbody></table></div>{{ $inventario->links() }}</section>
@endsection
