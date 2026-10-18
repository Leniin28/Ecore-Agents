@extends('erp.layout')
@section('erp-title', 'Orden procesada correctamente')
@section('erp-content')
<section class="dashboard-panel"><span class="section-eyebrow">Proceso completado</span><h2>✓ {{ $orden->folio() }}</h2><p>Cliente: {{ $orden->cliente->nombre }}</p><p>Total: ${{ number_format((float) $orden->total, 2) }}</p><p>Procesada: {{ $orden->procesada_at->format('d/m/Y H:i') }}</p><p>Se registraron {{ $orden->detalles->filter(fn ($detalle) => $detalle->movimiento !== null)->count() }} salidas de inventario.</p><div class="form-actions"><a class="button" href="{{ route('erp.ordenes.show', $orden) }}">Ver orden</a><a class="back-button" href="{{ route('erp.ordenes.index') }}">Ir a órdenes</a></div></section>
<section class="dashboard-panel"><h2>Pasos completados</h2><ul class="erp-checklist">@foreach($orden->procesosInternos as $proceso)<li>✓ {{ $proceso->descripcion }}</li>@endforeach</ul></section>
@endsection
