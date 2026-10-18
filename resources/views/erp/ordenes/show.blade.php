@extends('erp.layout')
@section('erp-title', $orden->folio())
@section('erp-content')
<dl class="customer-data"><div><dt>Cliente</dt><dd>{{ $orden->cliente->nombre }}</dd></div><div><dt>Empresa</dt><dd>{{ $orden->cliente->empresa ?? '—' }}</dd></div><div><dt>Responsable</dt><dd>{{ $orden->usuario->name }}</dd></div><div><dt>Fecha</dt><dd>{{ $orden->fecha->format('d/m/Y H:i') }}</dd></div><div><dt>Estado</dt><dd>{{ ucfirst($orden->estado) }}</dd></div><div><dt>Total</dt><dd>${{ number_format((float) $orden->total, 2) }}</dd></div></dl>
<div class="table-wrap"><table><thead><tr><th>Recurso</th><th>Cantidad</th><th>Precio pactado</th><th>Subtotal</th></tr></thead><tbody>@foreach($orden->detalles as $detalle)<tr><td>{{ $detalle->producto->nombre }}</td><td>{{ $detalle->cantidad }}</td><td>${{ number_format((float) $detalle->precio_unitario, 2) }}</td><td>${{ number_format((float) $detalle->subtotal, 2) }}</td></tr>@endforeach</tbody></table></div>
<section class="dashboard-panel"><h2>Historial operativo</h2><ol class="erp-checklist">@forelse($orden->procesosInternos as $proceso)<li>✓ {{ $proceso->descripcion }} · {{ $proceso->fecha->format('d/m/Y H:i') }} · {{ $proceso->usuario?->name ?? 'Sistema' }}</li>@empty<li>Sin procesos registrados.</li>@endforelse</ol></section>
<div class="form-actions">
@if($orden->estado === \App\Models\OrdenEmpresarial::PENDIENTE)
<form method="post" action="{{ route('erp.ordenes.procesar', $orden) }}">@csrf<button class="button" type="submit">Procesar orden</button></form>
<form method="post" action="{{ route('erp.ordenes.cancelar', $orden) }}">@csrf<button class="back-button" type="submit">Cancelar orden</button></form>
@endif
<a class="back-button" href="{{ route('erp.ordenes.index') }}">Ir a órdenes</a></div>
@endsection
