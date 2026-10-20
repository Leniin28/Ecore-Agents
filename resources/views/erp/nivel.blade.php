@extends('erp.layout')
@section('erp-title', 'Nivel de implementación ERP')
@section('erp-content')
@php
    $niveles = \App\Models\EstadoErp::NIVELES;
    $pasoActual = array_search($estado->nivel, array_keys($niveles), true) + 1;
    $porcentaje = (int) (($pasoActual / count($niveles)) * 100);
@endphp
<section class="dashboard-panel erp-level-panel">
    <div class="erp-level-heading"><label id="erp-level-progress-label" for="erp-level-progress">Progreso del nivel ERP</label><strong>{{ $porcentaje }}%</strong></div>
    <progress id="erp-level-progress" class="erp-level-progress" max="100" value="{{ $porcentaje }}" aria-labelledby="erp-level-progress-label" aria-valuetext="{{ $niveles[$estado->nivel] }}, nivel {{ $pasoActual }} de {{ count($niveles) }}, {{ $porcentaje }}%">{{ $porcentaje }}%</progress>
    <p class="erp-level-caption">Nivel {{ $pasoActual }} de {{ count($niveles) }} · Avance según el nivel declarado por el equipo.</p>
    <ol class="erp-steps erp-level-steps" aria-label="Niveles ERP">
        @foreach($niveles as $value => $label)
            <li @class(['is-complete' => $loop->iteration < $pasoActual, 'is-active' => $estado->nivel === $value]) @if($estado->nivel === $value) aria-current="step" @endif>
                <span class="erp-level-number" aria-hidden="true">{{ $loop->iteration < $pasoActual ? '✓' : $loop->iteration }}</span>
                <span>{{ $label }}<small>{{ $loop->iteration < $pasoActual ? 'Etapa previa' : ($estado->nivel === $value ? 'Nivel actual' : 'Siguiente etapa') }}</small></span>
            </li>
        @endforeach
    </ol>
</section>
<section class="dashboard-panel"><span class="section-eyebrow">Nivel actual</span><h2>{{ \App\Models\EstadoErp::NIVELES[$estado->nivel] }}</h2><p>{{ \App\Models\EstadoErp::DESCRIPCIONES[$estado->nivel] }}</p><p>El nivel es una evaluación académica declarada por el equipo.</p></section>
<form method="post" action="{{ route('erp.estado.update') }}" class="customer-form">@csrf @method('put')<label>Nivel ERP<select name="nivel" required>@foreach(\App\Models\EstadoErp::NIVELES as $value => $label)<option value="{{ $value }}" @selected($estado->nivel === $value)>{{ $label }}</option>@endforeach</select></label><button class="button" type="submit">Actualizar nivel</button></form>
<section class="dashboard-panel"><h2>Checklist de avance</h2><ul class="erp-checklist">@foreach($estado->checklist() as $label => $completed)<li>{{ $completed ? '✓' : '○' }} {{ $label }}</li>@endforeach</ul></section>
@endsection
