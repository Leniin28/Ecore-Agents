@extends('erp.layout')
@section('erp-title', 'Nivel de implementación ERP')
@section('erp-content')
<ol class="erp-steps" aria-label="Niveles ERP">@foreach(\App\Models\EstadoErp::NIVELES as $value => $label)<li @class(['is-active' => $estado->nivel === $value]) @if($estado->nivel === $value) aria-current="step" @endif>{{ $label }}</li>@endforeach</ol>
<section class="dashboard-panel"><span class="section-eyebrow">Nivel actual</span><h2>{{ \App\Models\EstadoErp::NIVELES[$estado->nivel] }}</h2><p>{{ \App\Models\EstadoErp::DESCRIPCIONES[$estado->nivel] }}</p><p>El nivel es una evaluación académica declarada por el equipo.</p></section>
<form method="post" action="{{ route('erp.estado.update') }}" class="customer-form">@csrf @method('put')<label>Nivel ERP<select name="nivel" required>@foreach(\App\Models\EstadoErp::NIVELES as $value => $label)<option value="{{ $value }}" @selected($estado->nivel === $value)>{{ $label }}</option>@endforeach</select></label><button class="button" type="submit">Actualizar nivel</button></form>
<section class="dashboard-panel"><h2>Checklist de avance</h2><ul class="erp-checklist">@foreach($estado->checklist() as $label => $completed)<li>{{ $completed ? '✓' : '○' }} {{ $label }}</li>@endforeach</ul></section>
@endsection
