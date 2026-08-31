@extends('layouts.app')

@section('title', $cliente->nombre.' | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero"><span class="section-eyebrow">Ficha de cliente</span><h1>{{ $cliente->nombre }}</h1><p>Información centralizada del cliente.</p></section>
    <section class="admin-layout">
        @include('admin._sidebar')
        <div class="admin-content customer-content">
            @if(session('success'))
                <p class="success-alert" role="status">{{ session('success') }}</p>
            @endif
            <article class="customer-detail">
                <div class="customer-heading">
                    <div><span class="section-eyebrow">Datos generales</span><h2>Cliente</h2></div>
                    <div class="customer-badges"><span class="status-badge status-{{ $cliente->estado }}">{{ ucfirst($cliente->estado) }}</span><span class="stage-badge stage-{{ $cliente->etapa_crm }}">{{ $cliente->etapaCrmLabel() }}</span></div>
                </div>
                <dl class="customer-data">
                    <div><dt>Nombre</dt><dd>{{ $cliente->nombre }}</dd></div>
                    <div><dt>Correo</dt><dd>{{ $cliente->correo }}</dd></div>
                    <div><dt>Teléfono</dt><dd>{{ $cliente->telefono }}</dd></div>
                    <div><dt>Empresa</dt><dd>{{ $cliente->empresa ?: 'Sin empresa' }}</dd></div>
                    <div><dt>Fecha de registro</dt><dd>{{ $cliente->fecha_registro->format('d/m/Y') }}</dd></div>
                    <div><dt>Estado</dt><dd>{{ ucfirst($cliente->estado) }}</dd></div>
                    <div><dt>Etapa CRM</dt><dd>{{ $cliente->etapaCrmLabel() }}</dd></div>
                </dl>
                <div class="form-actions"><a class="button" href="{{ route('clientes.edit', $cliente) }}">Editar cliente</a><a class="back-button" href="{{ route('clientes.index') }}">Volver al listado</a></div>
            </article>

            <article class="stage-panel">
                <div><span class="section-eyebrow">Seguimiento</span><h2>Etapa CRM</h2><p>Clasifica la relación actual con este cliente.</p></div>
                <form class="stage-form" method="post" action="{{ route('clientes.etapa.update', $cliente) }}">
                    @csrf
                    @method('PUT')
                    <label for="etapa_crm">Etapa actual</label>
                    <select id="etapa_crm" name="etapa_crm" required>
                        @foreach(\App\Models\Cliente::etapasCrm() as $value => $label)
                            <option value="{{ $value }}" @selected(old('etapa_crm', $cliente->etapa_crm) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="button" type="submit">Actualizar etapa</button>
                    @error('etapa_crm') <p class="field-error">{{ $message }}</p> @enderror
                </form>
            </article>

            <section class="interaction-layout" id="historial">
                <article class="interaction-form-card">
                    <span class="section-eyebrow">Seguimiento</span>
                    <h2>Registrar interacción</h2>
                    <form class="customer-form" method="post" action="{{ route('interacciones.store') }}">
                        @csrf
                        <input name="cliente_id" type="hidden" value="{{ $cliente->id }}">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" required @error('tipo') aria-invalid="true" @enderror>
                            @foreach(\App\Models\Interaccion::tipos() as $value => $label)
                                <option value="{{ $value }}" @selected(old('tipo') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('tipo') <p class="field-error">{{ $message }}</p> @enderror

                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="5" maxlength="2000" required @error('descripcion') aria-invalid="true" @enderror>{{ old('descripcion') }}</textarea>
                        @error('descripcion') <p class="field-error">{{ $message }}</p> @enderror

                        <label for="fecha">Fecha y hora</label>
                        <input id="fecha" name="fecha" type="datetime-local" value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}" required @error('fecha') aria-invalid="true" @enderror>
                        @error('fecha') <p class="field-error">{{ $message }}</p> @enderror

                        <button class="button" type="submit">Guardar interacción</button>
                    </form>
                </article>

                <article class="timeline-card">
                    <div class="timeline-heading"><span class="section-eyebrow">Historial centralizado</span><h2>Historial de interacciones</h2></div>
                    @if($cliente->interacciones->isEmpty())
                        <div class="timeline-empty"><p>Aún no existen interacciones para este cliente.</p></div>
                    @else
                        <ol class="interaction-timeline">
                            @foreach($cliente->interacciones as $interaccion)
                                <li class="timeline-item timeline-{{ $interaccion->tipo }}">
                                    <div class="timeline-marker" aria-hidden="true"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-meta"><span class="interaction-type type-{{ $interaccion->tipo }}">{{ $interaccion->tipoLabel() }}</span><time datetime="{{ $interaccion->fecha->toISOString() }}">{{ $interaccion->fecha->format('d/m/Y H:i') }}</time></div>
                                        <p>{{ $interaccion->descripcion }}</p>
                                        <small>Registró: <strong>{{ $interaccion->usuario->name }}</strong></small>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </article>
            </section>
        </div>
    </section>
@endsection
