@extends('layouts.app')
@section('title', 'Comparativa PUSH PULL | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Comparativa PUSH / PULL</h1></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content dashboard-content">
<div class="admin-summary-grid"><article class="admin-summary-card"><span>PUSH</span><strong>{{ $push }}</strong><small>Reposición automática</small></article><article class="admin-summary-card"><span>PULL</span><strong>{{ $pull }}</strong><small>Reposición por demanda</small></article></div>
<div class="table-wrap"><table><thead><tr><th>Producto</th><th>Proveedor</th><th>Stock</th><th>Mínimo</th><th>Estrategia</th></tr></thead><tbody>@forelse($productos as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->proveedor->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>{{ $producto->stock_minimo }}</td><td>{{ $producto->estrategia_logistica }}</td></tr>@empty<tr><td colspan="5">No hay productos.</td></tr>@endforelse</tbody></table></div>
</div></section>
@endsection
