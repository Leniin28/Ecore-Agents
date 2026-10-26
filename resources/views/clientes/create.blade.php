@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Nuevo cliente')

@section('title', $erpContext ? 'Nuevo cliente | ERP | ECore Agents' : 'Nuevo cliente | ECore Agents')
@section('body-class', 'admin-page')

@section($erpContext ? 'erp-content' : 'content')
    @unless($erpContext)
<section class="admin-hero"><span class="section-eyebrow">CRM</span><h1>Nuevo cliente</h1><p>Registra la información central del cliente.</p></section>
    <section class="admin-layout">
        @include('admin._sidebar')
        <div class="admin-content customer-content">
@endunless
            <form class="customer-form" method="post" action="{{ $moduleRoute('clientes.store') }}">
                @include('clientes._form', ['cliente' => null, 'method' => 'POST', 'submitLabel' => 'Guardar cliente'])
            </form>
    @unless($erpContext)

        </div>
    </section>
@endunless
@endsection
