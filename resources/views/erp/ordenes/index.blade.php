@extends('erp.layout')
@section('erp-title', 'Órdenes empresariales')
@section('erp-description', 'Operaciones de capacidad IA para los clientes del CRM.')
@section('erp-content')
<div class="customer-heading"><h2>Órdenes de clientes</h2><a class="button" href="{{ route('erp.ordenes.create') }}">Nueva orden</a></div>
<form class="customer-filters" method="get"><label>Buscar folio o cliente<input name="buscar" value="{{ request('buscar') }}"></label><label>Estado<select name="estado"><option value="">Todos</option>@foreach(\App\Models\OrdenEmpresarial::ESTADOS as $estado)<option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>@endforeach</select></label><button class="button" type="submit">Filtrar</button></form>
<div class="table-wrap"><table><thead><tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Responsable</th><th>Total</th><th>Estado</th><th>Detalle</th></tr></thead><tbody>@forelse($ordenes as $orden)<tr><td>{{ $orden->folio() }}</td><td>{{ $orden->fecha->format('d/m/Y H:i') }}</td><td>{{ $orden->cliente->nombre }}</td><td>{{ $orden->usuario->name }}</td><td>${{ number_format((float) $orden->total, 2) }}</td><td>{{ ucfirst($orden->estado) }}</td><td><a href="{{ route('erp.ordenes.show', $orden) }}">Ver orden</a></td></tr>@empty<tr><td colspan="7">Sin órdenes registradas.</td></tr>@endforelse</tbody></table></div>{{ $ordenes->links() }}
@endsection
