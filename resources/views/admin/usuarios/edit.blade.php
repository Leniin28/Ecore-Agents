@extends('layouts.app')
@section('title', 'Editar usuario | ECore Agents')
@section('content')
<section class="section narrow-section">
    <div class="section-heading"><span class="section-eyebrow">Administración</span><h1>Editar {{ $usuario->name }}</h1><p>El correo no se modifica desde esta pantalla.</p></div>
    <form class="form-card" method="post" action="{{ route('admin.usuarios.update', $usuario) }}">@include('admin.usuarios._form')</form>
</section>
@endsection
