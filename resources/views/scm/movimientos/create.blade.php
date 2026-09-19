@extends('layouts.app')
@section('title', 'Registrar movimiento | ECore Agents')
@section('content')
<section class="section narrow-section"><div class="section-heading"><span class="section-eyebrow">SCM</span><h1>Registrar movimiento</h1></div>
<form class="form-card" method="post" action="{{ route('scm.movimientos.store') }}">@csrf
<label>Producto<select name="producto_id" required><option value="">Selecciona</option>@foreach($productos as $producto)<option value="{{ $producto->id }}" @selected(old('producto_id', request('producto_id')) == $producto->id)>{{ $producto->nombre }} ({{ $producto->stock_actual }} unidades)</option>@endforeach</select></label>
<label>Tipo<select name="tipo" required><option value="entrada" @selected(old('tipo') === 'entrada')>Entrada</option><option value="salida" @selected(old('tipo') === 'salida')>Salida</option></select></label>
<label>Cantidad<input type="number" name="cantidad" min="1" value="{{ old('cantidad', 1) }}" required></label>@error('cantidad') <p class="form-error">{{ $message }}</p> @enderror
<label>Motivo<select name="motivo" required><option value="venta">Venta / consumo</option><option value="ajuste">Ajuste</option><option value="reposicion">Reposición</option></select></label>
<label>Fecha y hora<input type="datetime-local" name="fecha" value="{{ old('fecha') }}" required></label>@error('fecha') <p class="form-error">{{ $message }}</p> @enderror
<button class="button" type="submit">Registrar</button></form></section>
@endsection
