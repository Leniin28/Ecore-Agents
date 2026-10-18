@extends('erp.layout')
@section('erp-title', $orden->folio())
@section('erp-content')
<dl class="customer-data"><div><dt>Cliente</dt><dd>{{ $orden->cliente->nombre }}</dd></div><div><dt>Empresa</dt><dd>{{ $orden->cliente->empresa ?? '—' }}</dd></div><div><dt>Responsable</dt><dd>{{ $orden->usuario->name }}</dd></div><div><dt>Fecha</dt><dd>{{ $orden->fecha->format('d/m/Y H:i') }}</dd></div><div><dt>Estado</dt><dd>{{ ucfirst($orden->estado) }}</dd></div><div><dt>Total</dt><dd>${{ number_format((float) $orden->total, 2) }}</dd></div></dl>
<div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Cantidad</th><th>Precio pactado</th><th>Subtotal</th></tr></thead><tbody>@foreach($orden->detalles as $detalle)<tr><td>{{ $detalle->producto->nombre }}</td><td>{{ $detalle->cantidad }}</td><td>${{ number_format((float) $detalle->precio_unitario, 2) }}</td><td>${{ number_format((float) $detalle->subtotal, 2) }}</td></tr>@endforeach</tbody></table></div>
<div class="form-actions"><a class="back-button" href="{{ route('erp.ordenes.index') }}">Ir a órdenes</a></div>
@endsection
