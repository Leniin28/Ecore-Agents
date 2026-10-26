@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Editar usuario')
@section('title', $erpContext ? 'Editar usuario | ERP | ECore Agents' : 'Editar usuario | ECore Agents')
@section($erpContext ? 'erp-content' : 'content')
<section class="section narrow-section">
    <div class="section-heading">
@unless($erpContext)
<span class="section-eyebrow">Administración</span><h1>Editar {{ $usuario->name }}</h1>
@endunless
<p>El correo no se modifica desde esta pantalla.</p></div>
    <form class="form-card" method="post" action="{{ $moduleRoute('admin.usuarios.update', $usuario) }}">@include('admin.usuarios._form')</form>
</section>
@endsection
