@extends('layouts.app')

@section('title', $cliente->nombre.' | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero"><span class="section-eyebrow">Ficha de cliente</span><h1>{{ $cliente->nombre }}</h1><p>Información centralizada del cliente.</p></section>
    <section class="admin-layout">
        @include('admin._sidebar')
        <div class="admin-content customer-content">
            @if(session('success'))
                <p class="success-alert" role="status">{{ session('success') }}</p>
            @endif
            <article class="customer-detail">
                <div class="customer-heading"><div><span class="section-eyebrow">Datos generales</span><h2>Cliente</h2></div><span class="status-badge status-{{ $cliente->estado }}">{{ ucfirst($cliente->estado) }}</span></div>
                <dl class="customer-data">
                    <div><dt>Nombre</dt><dd>{{ $cliente->nombre }}</dd></div>
                    <div><dt>Correo</dt><dd>{{ $cliente->correo }}</dd></div>
                    <div><dt>Teléfono</dt><dd>{{ $cliente->telefono }}</dd></div>
                    <div><dt>Empresa</dt><dd>{{ $cliente->empresa ?: 'Sin empresa' }}</dd></div>
                    <div><dt>Fecha de registro</dt><dd>{{ $cliente->fecha_registro->format('d/m/Y') }}</dd></div>
                    <div><dt>Estado</dt><dd>{{ ucfirst($cliente->estado) }}</dd></div>
                </dl>
                <div class="form-actions"><a class="button" href="{{ route('clientes.edit', $cliente) }}">Editar cliente</a><a class="back-button" href="{{ route('clientes.index') }}">Volver al listado</a></div>
            </article>
            <article class="interaction-placeholder"><span class="section-eyebrow">Próximamente</span><h2>Historial de interacciones</h2><p>Las interacciones se implementarán en la siguiente etapa.</p></article>
        </div>
    </section>
@endsection
