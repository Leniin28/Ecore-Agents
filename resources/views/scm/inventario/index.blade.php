@extends('layouts.app')
@section('title', 'Inventario SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Inventario de capacidad</h1><p>El stock vive en cada recurso IA; no se duplica en una segunda tabla.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content customer-content">
<form class="filters" method="get"><label>Buscar producto<input name="buscar" value="{{ $buscar }}"></label><button class="button" type="submit">Buscar</button></form>
<div class="table-wrap"><table><thead><tr><th>Producto</th><th>Proveedor</th><th>Stock actual</th><th>Stock mínimo</th><th>Estado</th><th>Historial</th></tr></thead><tbody>
@forelse($productos as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->proveedor->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>{{ $producto->stock_minimo }}</td><td><span class="demo-badge">{{ $producto->stock_actual <= $producto->stock_minimo ? 'STOCK BAJO' : 'NORMAL' }}</span></td><td><a href="{{ route('scm.productos.movimientos', $producto) }}">Ver movimientos</a></td></tr>@empty<tr><td colspan="6">No hay productos.</td></tr>@endforelse
</tbody></table></div>{{ $productos->links() }}
</div></section>
@endsection
