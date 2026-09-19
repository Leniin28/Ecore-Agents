@extends('layouts.app')
@section('title', 'Nuevo proveedor | ECore Agents')
@section('content')<section class="section narrow-section"><div class="section-heading"><span class="section-eyebrow">SCM</span><h1>Nuevo proveedor</h1></div><form class="form-card" method="post" action="{{ route('scm.proveedores.store') }}">@include('scm.proveedores._form')</form></section>@endsection
