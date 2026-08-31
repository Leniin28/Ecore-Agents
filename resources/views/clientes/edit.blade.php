@extends('layouts.app')

@section('title', 'Editar cliente | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero"><span class="section-eyebrow">CRM</span><h1>Editar cliente</h1><p>Actualiza la información centralizada de {{ $cliente->nombre }}.</p></section>
    <section class="admin-layout">
        @include('admin._sidebar')
        <div class="admin-content customer-content">
            <form class="customer-form" method="post" action="{{ route('clientes.update', $cliente) }}">
                @include('clientes._form', ['cliente' => $cliente, 'method' => 'PUT', 'submitLabel' => 'Guardar cambios'])
            </form>
        </div>
    </section>
@endsection
