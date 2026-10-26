@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Editar cliente')

@section('title', $erpContext ? 'Editar cliente | ERP | ECore Agents' : 'Editar cliente | ECore Agents')
@section('body-class', 'admin-page')

@section($erpContext ? 'erp-content' : 'content')
    @unless($erpContext)
<section class="admin-hero"><span class="section-eyebrow">CRM</span><h1>Editar cliente</h1><p>Actualiza la información centralizada de {{ $cliente->nombre }}.</p></section>
    <section class="admin-layout">
        @include('admin._sidebar')
        <div class="admin-content customer-content">
@endunless
            <form class="customer-form" method="post" action="{{ $moduleRoute('clientes.update', $cliente) }}">
                @include('clientes._form', ['cliente' => $cliente, 'method' => 'PUT', 'submitLabel' => 'Guardar cambios'])
            </form>
    @unless($erpContext)

        </div>
    </section>
@endunless
@endsection
