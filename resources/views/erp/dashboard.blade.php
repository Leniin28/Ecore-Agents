@extends('erp.layout')
@section('erp-title', 'Dashboard ERP')
@section('erp-description', 'Vista empresarial integrada de clientes, operaciones y capacidad IA.')
@section('erp-content')
@include('erp._metricas')
@include('erp._graficas')
<section class="dashboard-panel"><div class="customer-heading"><h2>Órdenes recientes</h2><a href="{{ route('erp.ordenes.create') }}" class="button">Nueva orden</a></div><ul class="erp-checklist">@forelse($recientes as $orden)<li><a href="{{ route('erp.ordenes.show', $orden) }}">{{ $orden->folio() }}</a> · {{ $orden->cliente->nombre }} · {{ ucfirst($orden->estado) }} · ${{ number_format((float) $orden->total, 2) }}</li>@empty<li>Sin órdenes registradas.</li>@endforelse</ul></section>
@endsection
