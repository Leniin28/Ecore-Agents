@extends('layouts.app')
@section('title', 'Editar recurso IA | ECore Agents')
@section('content')<section class="section narrow-section"><div class="section-heading"><span class="section-eyebrow">SCM</span><h1>Editar producto / recurso IA</h1></div><form class="form-card" method="post" action="{{ route('scm.productos.update', $producto) }}">@include('scm.productos._form')</form></section>@endsection
