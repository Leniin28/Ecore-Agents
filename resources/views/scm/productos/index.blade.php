@extends('layouts.app')
@section('title', 'Productos SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Productos / Recursos IA</h1><p>Capacidad académica expresada en unidades de consumo IA; no representa tokens almacenados.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content customer-content">
    @if(session('success')) <p class="success-alert">{{ session('success') }}</p> @endif
    <div class="admin-section-heading"><h2>Recursos</h2><a class="button" href="{{ route('scm.productos.create') }}">Nuevo recurso IA</a></div>
    <form class="filters" method="get"><label>Buscar<input name="buscar" value="{{ request('buscar') }}"></label><label>Categoría<select name="categoria"><option value="">Todas</option>@foreach($categorias as $categoria)<option @selected(request('categoria') === $categoria)>{{ $categoria }}</option>@endforeach</select></label><label>Estrategia<select name="estrategia"><option value="">Todas</option><option @selected(request('estrategia') === 'PUSH')>PUSH</option><option @selected(request('estrategia') === 'PULL')>PULL</option></select></label><button class="button" type="submit">Filtrar</button></form>
    <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Categoría</th><th>Proveedor</th><th>Stock</th><th>Mínimo</th><th>Costo</th><th>Estrategia</th><th>Acciones</th></tr></thead><tbody>
    @forelse($productos as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->categoria }}</td><td>{{ $producto->proveedor->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>{{ $producto->stock_minimo }}</td><td>${{ number_format((float) $producto->costo_unitario, 2) }}</td><td>{{ $producto->estrategia_logistica }}</td><td class="table-actions"><a href="{{ route('scm.productos.edit', $producto) }}">Editar</a><form method="post" action="{{ route('scm.productos.destroy', $producto) }}">@csrf @method('delete')<button class="link-button" type="submit">Eliminar</button></form></td></tr>@empty<tr><td colspan="8">No hay recursos IA.</td></tr>@endforelse
    </tbody></table></div>{{ $productos->links() }}
</div></section>
@endsection
