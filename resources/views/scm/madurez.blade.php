@extends('layouts.app')
@section('title', 'Madurez SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Nivel de madurez</h1><p>Nivel académico seleccionado para el módulo SCM.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content customer-content">
@if(session('success')) <p class="success-alert">{{ session('success') }}</p> @endif
<div class="admin-summary-grid"><article class="admin-summary-card"><span>Nivel actual</span><strong>{{ $configuracion->nivelLabel() }}</strong><small>{{ match($configuracion->nivel_scm) { 'inicial' => 'Procesos básicos definidos.', 'en_desarrollo' => 'Procesos integrados y medidos.', 'optimizado' => 'Procesos revisados para mejora continua.' } }}</small></article></div>
<form class="form-card" method="post" action="{{ route('scm.nivel.update') }}">@csrf @method('put')<label>Nivel SCM<select name="nivel_scm"><option value="inicial" @selected($configuracion->nivel_scm === 'inicial')>Inicial</option><option value="en_desarrollo" @selected($configuracion->nivel_scm === 'en_desarrollo')>En desarrollo</option><option value="optimizado" @selected($configuracion->nivel_scm === 'optimizado')>Optimizado</option></select></label><button class="button" type="submit">Actualizar nivel</button></form>
<section class="dashboard-panel"><h2>Checklist funcional</h2><ul class="activity-list"><li>✓ Productos y proveedores integrados</li><li>✓ Inventario funcionando</li><li>✓ Trazabilidad de movimientos</li><li>✓ Estrategia PUSH / PULL</li><li>✓ Reportes y métricas</li></ul></section>
</div></section>
@endsection
