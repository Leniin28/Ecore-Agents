@extends('layouts.app')

@section('title', 'Crea tu agente personalizado | ECore Agents')
@section('body-class', 'custom-agent-page')

@section('content')
    <section class="custom-hero">
        <span class="section-eyebrow">Configurador de servicios</span>
        <h1>Crea tu agente personalizado</h1>
        <p>Parte desde Agente Inicio y agrega las funciones que realmente necesita tu negocio. El precio se actualiza de forma demostrativa.</p>
    </section>

    <section class="custom-builder" data-custom-builder data-base-price="499" aria-label="Personalizador de agente">
        <div class="custom-options">
            <article class="base-plan-card">
                <div class="base-plan-heading">
                    <div><span class="base-required">Base obligatoria</span><h2>Agente Inicio</h2><p>Base obligatoria para todos los agentes personalizados.</p></div>
                    <p class="base-price">$499 <small>MXN/mes</small></p>
                </div>
                <ul class="feature-list compact">
                    <li>Responde mensajes con IA</li><li>Catálogo básico</li><li>Preguntas frecuentes y horarios</li><li>Historial conversacional básico</li><li>Escalamiento manual a humano</li>
                </ul>
            </article>

            @foreach($modules as $tier => $tierModules)
                <section class="module-group module-group-{{ $tier }}">
                    <div class="module-group-heading">
                        <div><span class="section-eyebrow">Módulos {{ $tier === 'plus' ? 'Plus' : 'Avanzados' }}</span><h2>{{ $tier === 'plus' ? 'Seguimiento y organización' : 'Automatización avanzada' }}</h2></div>
                        <span class="module-counter"><strong data-{{ $tier }}-count>0</strong> seleccionados</span>
                    </div>
                    <div class="module-grid">
                        @foreach($tierModules as $index => $module)
                            <label class="module-option">
                                <input class="module-checkbox" type="checkbox" data-module-name="{{ $module['name'] }}" data-module-price="{{ $module['price'] }}" data-module-tier="{{ $tier }}">
                                <span><strong>{{ $module['name'] }}</strong><small>+${{ number_format($module['price']) }} MXN/mes</small></span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <aside class="custom-summary">
            <span class="section-eyebrow">Resumen mensual</span>
            <h2>Tu configuración</h2>
            <dl>
                <div><dt>Agente Inicio</dt><dd>$499</dd></div>
                <div><dt>Funciones extra</dt><dd data-extras-total>$0</dd></div>
                <div class="summary-total"><dt>Total mensual</dt><dd data-monthly-total>$499</dd></div>
            </dl>
            <p><strong data-module-count>0</strong> funciones seleccionadas</p>
            <ul class="selected-modules" data-selected-modules><li class="empty-selection">Aún no has agregado funciones extra.</li></ul>
            <p class="recommendation" data-recommendation>Selecciona las funciones que necesita tu negocio para recibir una sugerencia visual.</p>
            <button class="button" type="button" data-demo-action="Configuración calculada como demostración. No se agregó al carrito.">Finalizar configuración (demo)</button>
            <p class="form-message" data-action-message role="status" aria-live="polite"></p>
        </aside>
    </section>
@endsection
