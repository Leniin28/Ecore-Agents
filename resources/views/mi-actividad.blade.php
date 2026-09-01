@extends('layouts.app')

@section('title', 'Mi actividad | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero">
        <span class="section-eyebrow">Seguimiento personal</span>
        <h1>Mi actividad</h1>
        <p>Interacciones que registraste, ordenadas desde la más reciente.</p>
    </section>

    <section class="admin-layout">
        @include('admin._sidebar')

        <div class="admin-content activity-content">
            <div class="admin-section-heading"><div><span class="section-eyebrow">Historial</span><h2>Mis interacciones</h2></div><p>{{ $interacciones->count() }} registradas por tu usuario.</p></div>

            @if($interacciones->isEmpty())
                <article class="admin-empty-state"><span class="placeholder-icon">0</span><h3>Sin actividad registrada</h3><p>Tus próximas llamadas, correos o reuniones aparecerán aquí.</p></article>
            @else
                <div class="activity-table-wrap">
                    <table class="customer-table activity-table">
                        <thead><tr><th>Fecha</th><th>Cliente</th><th>Tipo</th><th>Descripción</th><th>Etapa CRM</th></tr></thead>
                        <tbody>
                            @foreach($interacciones as $interaccion)
                                <tr>
                                    <td><time datetime="{{ $interaccion->fecha->toISOString() }}">{{ $interaccion->fecha->format('d/m/Y H:i') }}</time></td>
                                    <td><a href="{{ route('clientes.show', $interaccion->cliente) }}">{{ $interaccion->cliente->nombre }}</a></td>
                                    <td><span class="interaction-type type-{{ $interaccion->tipo }}">{{ $interaccion->tipoLabel() }}</span></td>
                                    <td>{{ $interaccion->descripcion }}</td>
                                    <td><span class="stage-badge stage-{{ $interaccion->cliente->etapa_crm }}">{{ $interaccion->cliente->etapaCrmLabel() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
