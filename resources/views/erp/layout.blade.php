@extends('layouts.app')
@section('title', trim($__env->yieldContent('erp-title')).' | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">Etapa 3 · ERP</span><h1>@yield('erp-title')</h1><p>@yield('erp-description', 'Clientes, recursos y operaciones de ECore Agents en un mismo flujo.')</p></section>
<section class="admin-layout">@include('erp._sidebar')<div class="admin-content dashboard-content">
    @if(session('success')) <p class="success-alert" role="status">{{ session('success') }}</p> @endif
    @if($errors->any()) <div class="form-error" role="alert"><strong>Revisa la operación:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    @yield('erp-content')
</div></section>
@endsection
