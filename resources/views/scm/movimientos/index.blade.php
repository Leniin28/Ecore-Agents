@extends('layouts.app')
@section('title', 'Movimientos SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Movimientos de inventario</h1><p>Entradas y salidas trazables de unidades de consumo IA.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content customer-content">
@if(session('success')) <p class="success-alert">{{ session('success') }}</p> @endif
<div class="admin-section-heading"><h2>Historial</h2><a class="button" href="{{ route('scm.movimientos.create') }}">Registrar movimiento</a></div>
<form class="filters" method="get"><label>Tipo<select name="tipo"><option value="">Todos</option><option value="entrada" @selected(request('tipo') === 'entrada')>Entrada</option><option value="salida" @selected(request('tipo') === 'salida')>Salida</option></select></label><label>Producto<select name="producto_id"><option value="">Todos</option>@foreach($productos as $producto)<option value="{{ $producto->id }}" @selected(request('producto_id') == $producto->id)>{{ $producto->nombre }}</option>@endforeach</select></label><button class="button" type="submit">Filtrar</button></form>
<div class="table-wrap"><table><thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Responsable</th></tr></thead><tbody>
@forelse($movimientos as $movimiento)<tr><td>{{ $movimiento->fecha->format('d/m/Y H:i') }}</td><td>{{ $movimiento->producto->nombre }}</td><td>{{ ucfirst($movimiento->tipo) }}</td><td>{{ $movimiento->cantidad }}</td><td>{{ $movimiento->motivo === 'venta' ? 'Venta / consumo' : ucfirst($movimiento->motivo) }}</td><td>{{ $movimiento->usuario->name }}</td></tr>@empty<tr><td colspan="6">No hay movimientos.</td></tr>@endforelse
</tbody></table></div>{{ $movimientos->links() }}
</div></section>
@endsection
