@extends('layouts.app')
@section('title', 'Pedidos SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Pedidos de capacidad</h1><p>Solicitudes manuales PULL y reposiciones automáticas PUSH.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content customer-content">
@if(session('success')) <p class="success-alert">{{ session('success') }}</p> @endif @error('estado') <p class="form-error">{{ $message }}</p> @enderror
<div class="admin-section-heading"><h2>Pedidos</h2><a class="button" href="{{ route('scm.pedidos.create') }}">Generar pedido</a></div>
<form class="filters" method="get"><label>Estado<select name="estado"><option value="">Todos</option><option value="pendiente" @selected(request('estado') === 'pendiente')>Pendiente</option><option value="surtido" @selected(request('estado') === 'surtido')>Surtido</option></select></label><button class="button" type="submit">Filtrar</button></form>
<div class="table-wrap"><table><thead><tr><th>Folio</th><th>Fecha</th><th>Producto</th><th>Proveedor</th><th>Cantidad</th><th>Tipo</th><th>Origen</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
@forelse($pedidos as $pedido)<tr><td>#{{ $pedido->id }}</td><td>{{ $pedido->created_at->format('d/m/Y H:i') }}</td><td>{{ $pedido->producto->nombre }}</td><td>{{ $pedido->producto->proveedor->nombre }}</td><td>{{ $pedido->cantidad }}</td><td>{{ $pedido->tipo }}</td><td>{{ $pedido->origen === 'automatico_push' ? 'Automático PUSH' : 'Manual' }}</td><td>{{ ucfirst($pedido->estado) }}</td><td>@if($pedido->estado === 'pendiente')<form method="post" action="{{ route('scm.pedidos.estado.update', $pedido) }}">@csrf @method('put')<input type="hidden" name="estado" value="surtido"><button class="link-button" type="submit">Surtir</button></form>@else—@endif</td></tr>@empty<tr><td colspan="9">No hay pedidos.</td></tr>@endforelse
</tbody></table></div>{{ $pedidos->links() }}
</div></section>
@endsection
