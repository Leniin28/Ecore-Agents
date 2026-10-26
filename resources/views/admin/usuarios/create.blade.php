@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Crear usuario interno')
@section('title', $erpContext ? 'Crear usuario interno | ERP | ECore Agents' : 'Crear usuario interno | ECore Agents')
@section($erpContext ? 'erp-content' : 'content')
<section class="section narrow-section">
    <div class="section-heading">
@unless($erpContext)
<span class="section-eyebrow">Administración</span><h1>Crear usuario interno</h1>
@endunless
</div>
    <form class="form-card" method="post" action="{{ $moduleRoute('admin.usuarios.store') }}">@include('admin.usuarios._form')</form>
</section>
@endsection
