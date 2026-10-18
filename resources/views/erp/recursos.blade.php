@extends('erp.layout')
@section('erp-title', 'Recursos ERP')
@section('erp-description', 'Catálogo integrado de capacidad IA, inventario y precios del SCM.')
@section('erp-content')
<form method="get" class="customer-filters"><label>Buscar recurso<input name="buscar" value="{{ request('buscar') }}"></label><button class="button" type="submit">Buscar</button></form>
<div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Categoría</th><th>Proveedor</th><th>Stock</th><th>Mínimo</th><th>Costo interno</th><th>Precio de venta</th><th>Estrategia</th><th>Estado stock</th></tr></thead><tbody>
@forelse($productos as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->categoria }}</td><td>{{ $producto->proveedor->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>{{ $producto->stock_minimo }}</td><td>${{ number_format((float) $producto->costo_unitario, 2) }}</td><td>${{ number_format((float) $producto->precio_venta_unitario, 2) }}</td><td>{{ $producto->estrategia_logistica }}</td><td>{{ $producto->stock_actual <= $producto->stock_minimo ? 'Stock bajo' : 'Disponible' }}</td></tr>@empty<tr><td colspan="9">Sin recursos registrados en SCM.</td></tr>@endforelse
</tbody></table></div>{{ $productos->links() }}
@endsection
