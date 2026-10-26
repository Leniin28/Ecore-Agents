@extends($erpContext ? 'erp.layout' : 'layouts.app')
@section('erp-title', 'Nuevo producto / recurso IA')
@section('title', $erpContext ? 'Nuevo producto / recurso IA | ERP | ECore Agents' : 'Nuevo recurso IA | ECore Agents')
@section($erpContext ? 'erp-content' : 'content')<section class="section narrow-section"><div class="section-heading">
@unless($erpContext)
<span class="section-eyebrow">SCM</span><h1>Nuevo producto / recurso IA</h1>
@endunless
</div><form class="form-card" method="post" action="{{ $moduleRoute('scm.productos.store') }}">@include('scm.productos._form')</form></section>@endsection
