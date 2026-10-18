@extends('erp.layout')
@section('erp-title', 'Nueva orden')
@section('erp-content')
@if($clientes->isEmpty() || $productos->isEmpty())
<p>Se necesita al menos un cliente CRM y un recurso SCM para crear una orden.</p>
@else
<form method="post" action="{{ route('erp.ordenes.store') }}" class="erp-order-form" data-order-wizard>
@csrf
<ol class="erp-steps" aria-label="Pasos de la orden"><li data-step-label>1. Cliente</li><li data-step-label>2. Recursos</li><li data-step-label>3. Confirmar</li></ol>
<section data-order-step><h2>Seleccionar cliente</h2><label>Buscar cliente<input type="search" data-client-search placeholder="Nombre, empresa o correo"></label><label>Cliente CRM<select name="cliente_id" required data-order-client><option value="">Selecciona un cliente</option>@foreach($clientes as $cliente)<option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id) data-detail="{{ $cliente->empresa }} · {{ $cliente->correo }} · {{ $cliente->telefono }}">{{ $cliente->nombre }}{{ $cliente->empresa ? ' — '.$cliente->empresa : '' }}</option>@endforeach</select></label><p data-client-detail></p><button class="button" type="button" data-next-step hidden>Siguiente</button></section>
<section data-order-step><h2>Agregar recursos</h2><p>Las cantidades son unidades internas de consumo IA.</p><div class="table-wrap"><table><thead><tr><th>Agregar</th><th>Recurso</th><th>Stock</th><th>Precio unitario</th><th>Cantidad</th></tr></thead><tbody>@foreach($productos as $producto)
@php($selected = old('detalles.'.$producto->id.'.producto_id'))
<tr data-order-line data-name="{{ $producto->nombre }}" data-price="{{ $producto->precio_venta_unitario }}"><td><input type="checkbox" data-product-select aria-label="Agregar {{ $producto->nombre }}" @checked($selected)><input type="hidden" name="detalles[{{ $producto->id }}][producto_id]" value="{{ $producto->id }}" data-line-field disabled></td><td>{{ $producto->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>${{ number_format((float) $producto->precio_venta_unitario, 2) }}</td><td><input class="erp-quantity" type="number" name="detalles[{{ $producto->id }}][cantidad]" min="1" max="1000000" value="{{ old('detalles.'.$producto->id.'.cantidad', 1) }}" data-line-field disabled aria-label="Cantidad {{ $producto->nombre }}"></td></tr>
@endforeach</tbody></table></div><p data-order-message role="status"></p><div class="form-actions"><button type="button" class="back-button" data-previous-step hidden>Anterior</button><button type="button" class="button" data-next-step hidden>Revisar resumen</button></div></section>
<section data-order-step><h2>Resumen y confirmación</h2><p data-summary-client></p><div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody data-order-summary></tbody></table></div><p>Total estimado: <strong data-order-total>$0.00</strong></p><p>La orden se creará pendiente. El inventario se descontará al procesarla. Se aplicarán los precios vigentes al confirmar.</p><div class="form-actions"><button type="button" class="back-button" data-previous-step hidden>Anterior</button><button type="submit" class="button">Confirmar orden</button></div></section>
</form>
@endif
@endsection
