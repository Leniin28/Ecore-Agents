@extends('layouts.app')
@section('title', 'Crear usuario interno | ECore Agents')
@section('content')
<section class="section narrow-section">
    <div class="section-heading"><span class="section-eyebrow">Administración</span><h1>Crear usuario interno</h1></div>
    <form class="form-card" method="post" action="{{ route('admin.usuarios.store') }}">@include('admin.usuarios._form')</form>
</section>
@endsection
