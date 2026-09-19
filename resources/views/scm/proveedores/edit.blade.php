@extends('layouts.app')
@section('title', 'Editar proveedor | ECore Agents')
@section('content')<section class="section narrow-section"><div class="section-heading"><span class="section-eyebrow">SCM</span><h1>Editar proveedor</h1></div><form class="form-card" method="post" action="{{ route('scm.proveedores.update', $proveedor) }}">@include('scm.proveedores._form')</form></section>@endsection
