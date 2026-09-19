@extends('layouts.app')
@section('title', 'Logística SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Logística PUSH / PULL</h1><p>PUSH genera reposición automática al llegar al mínimo; PULL responde a pedidos manuales.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content customer-content">
@if(session('success')) <p class="success-alert">{{ session('success') }}</p> @endif
<div class="admin-section-heading"><h2>Estrategia por recurso</h2><a class="button button-secondary" href="{{ route('scm.logistica.comparativa') }}">Ver comparativa</a></div>
<div class="table-wrap"><table><thead><tr><th>Producto</th><th>Stock</th><th>Mínimo</th><th>Estrategia</th><th>Actualizar</th></tr></thead><tbody>
@forelse($productos as $producto)<tr><td>{{ $producto->nombre }}</td><td>{{ $producto->stock_actual }}</td><td>{{ $producto->stock_minimo }}</td><td>{{ $producto->estrategia_logistica }}</td><td><form class="inline-form" method="post" action="{{ route('scm.productos.estrategia.update', $producto) }}">@csrf @method('put')<select name="estrategia_logistica"><option @selected($producto->estrategia_logistica === 'PUSH')>PUSH</option><option @selected($producto->estrategia_logistica === 'PULL')>PULL</option></select><button class="link-button" type="submit">Guardar</button></form></td></tr>@empty<tr><td colspan="5">No hay productos.</td></tr>@endforelse
</tbody></table></div>
</div></section>
@endsection
