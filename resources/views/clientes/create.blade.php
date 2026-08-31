@extends('layouts.app')

@section('title', 'Nuevo cliente | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero"><span class="section-eyebrow">CRM</span><h1>Nuevo cliente</h1><p>Registra la información central del cliente.</p></section>
    <section class="admin-layout">
        @include('admin._sidebar')
        <div class="admin-content customer-content">
            <form class="customer-form" method="post" action="{{ route('clientes.store') }}">
                @include('clientes._form', ['cliente' => null, 'method' => 'POST', 'submitLabel' => 'Guardar cliente'])
            </form>
        </div>
    </section>
@endsection
