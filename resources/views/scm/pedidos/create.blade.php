@extends('layouts.app')
@section('title', 'Generar pedido | ECore Agents')
@section('content')
<section class="section narrow-section"><div class="section-heading"><span class="section-eyebrow">SCM</span><h1>Generar pedido manual</h1><p>Representa una solicitud por demanda (PULL).</p></div>
<form class="form-card" method="post" action="{{ route('scm.pedidos.store') }}">@csrf
<label>Producto<select name="producto_id" required><option value="">Selecciona</option>@foreach($productos as $producto)<option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->nombre }}</option>@endforeach</select></label>
<label>Cantidad<input type="number" min="1" name="cantidad" value="{{ old('cantidad', 1) }}" required></label>@error('cantidad') <p class="form-error">{{ $message }}</p> @enderror
<label>Tipo<select name="tipo" required><option value="reposicion">Reposición</option><option value="venta">Venta</option></select></label>
<button class="button" type="submit">Generar pedido</button></form></section>
@endsection
