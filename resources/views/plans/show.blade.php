@extends('layouts.app')

@section('title', $plan['name'].' | ECore Agents')
@section('body-class', 'plan-page plan-page-'.$plan['tier'])

@section('content')
    <section class="plan-detail">
        <article class="plan-detail-card">
            <div class="plan-detail-header">
                <div>
                    <span class="section-eyebrow">{{ $plan['eyebrow'] }}</span>
                    <h1>{{ $plan['name'] }}</h1>
                    @if(isset($plan['promotional_price']))
                        <p class="plan-price"><span class="old-price">${{ number_format($plan['price']) }}</span> ${{ number_format($plan['promotional_price'], 2) }} <small>MXN/mes</small></p>
                        <p class="promo-note">10% de descuento con código <strong>{{ $plan['promotion_code'] }}</strong> cuando exista el carrito real.</p>
                    @else
                        <p class="plan-price">${{ number_format($plan['price']) }} <small>MXN/mes</small></p>
                    @endif
                </div>
                <span class="plan-badge">{{ $plan['badge'] }}</span>
            </div>

            <p class="plan-detail-description">{{ $plan['summary'] }}</p>
            @if($plan['inheritance'])
                <p class="inheritance-note">{{ $plan['inheritance'] }}</p>
            @endif

            <div class="plan-detail-content">
                <section>
                    <h2>Qué incluye</h2>
                    <ul class="feature-list">
                        @foreach($plan['features'] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </section>
                <aside class="ideal-card">
                    <span class="ideal-number">{{ $plan['ideal_number'] }}</span>
                    <h2>Ideal para</h2>
                    <p>{{ $plan['ideal_for'] }}</p>
                </aside>
            </div>

            <section class="visual-examples">
                <span class="section-eyebrow">Así se vería</span>
                <h2>Ejemplos visuales</h2>
                <div class="demo-grid">
                    @if($plan['slug'] === 'inicio')
                        <article class="demo-card chat-demo">
                            <strong><span class="status-dot"></span> Chat básico por WhatsApp</strong>
                            <p class="chat-client"><span>Cliente</span>¿Cuál es su horario?</p>
                            <p class="chat-agent"><span>Agente IA</span>Atendemos de lunes a sábado de 9:00 a 6:00.</p>
                        </article>
                    @elseif($plan['slug'] === 'plus')
                        <article class="demo-card">
                            <strong>Audio recibido <span class="demo-chip">0:06</span></strong>
                            <div class="audio-wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                            <p><strong>Transcripción:</strong> Hola, quiero saber el precio del servicio.</p>
                        </article>
                        <article class="demo-card">
                            <span class="success-label">Cliente interesado</span>
                            <h3>Seguimiento automático</h3>
                            <p>Recordatorio enviado por WhatsApp.</p>
                        </article>
                    @else
                        <article class="demo-card"><span class="success-label">Cita confirmada</span><h3>Mañana 10:30 AM</h3><p>Recordatorio programado</p></article>
                        <article class="demo-card"><strong>Reportes y alertas</strong><div class="mini-bars"><i style="--value:88%"></i><i style="--value:58%"></i><i style="--value:28%"></i></div><p>Actividad demostrativa</p></article>
                        <article class="demo-card"><span class="demo-chip">Audio listo</span><h3>Respuesta en audio generada</h3><p>Llamada por WhatsApp: función avanzada/futura.</p></article>
                    @endif
                </div>
            </section>

            <div class="demo-action-panel">
                <div>
                    <strong>Contratación pendiente</strong>
                    <p>El carrito y el checkout se implementarán en una etapa posterior.</p>
                </div>
                <button class="button" type="button" data-demo-action="Esta acción es demostrativa. El carrito todavía no existe.">Agregar al carrito (demo)</button>
                <a class="back-button" href="{{ route('plans.index') }}">Regresar a planes</a>
                <p class="form-message" data-action-message role="status" aria-live="polite"></p>
            </div>
        </article>
    </section>
@endsection
