@extends('layouts.app')

@section('title', 'Planes | ECore Agents')

@section('content')
    <section class="catalog-page">
        <div class="section-heading catalog-heading">
            <span class="section-eyebrow">Planes para cada etapa</span>
            <h1>Un agente IA que crece con tu negocio</h1>
            <p>Comienza respondiendo dudas, mejora el seguimiento de clientes o automatiza procesos más avanzados.</p>
        </div>

        <div class="filter-bar" role="group" aria-label="Filtrar planes">
            <button class="filter-button is-active" type="button" data-plan-filter="all" aria-pressed="true">Todos</button>
            <button class="filter-button" type="button" data-plan-filter="basic" aria-pressed="false">Atención básica</button>
            <button class="filter-button" type="button" data-plan-filter="follow-up" aria-pressed="false">Seguimiento</button>
            <button class="filter-button" type="button" data-plan-filter="advanced" aria-pressed="false">Automatización</button>
            <button class="filter-button" type="button" data-plan-filter="custom" aria-pressed="false">Personalizable</button>
        </div>
        <p class="filter-status" data-filter-status aria-live="polite">Mostrando todos los planes</p>

        <div class="plan-grid" data-plan-grid>
            @foreach($plans as $plan)
                <article class="plan-card plan-card-{{ $plan['tier'] }}" data-category="{{ $plan['category'] }}">
                    @if($plan['slug'] === 'plus')
                        <span class="recommended-label">Más elegido</span>
                    @endif
                    <span class="plan-tier">{{ $plan['catalog_eyebrow'] }}</span>
                    <div class="plan-card-heading">
                        <h2>{{ $plan['name'] }}</h2>
                        <span class="plan-badge">{{ $plan['badge'] }}</span>
                    </div>
                    @if(isset($plan['promotional_price']))
                        <p class="promotion-pill">10% OFF con código {{ $plan['promotion_code'] }}</p>
                        <p class="plan-price"><span class="old-price">${{ number_format($plan['price']) }}</span> ${{ number_format($plan['promotional_price'], 2) }} <small>MXN/mes</small></p>
                    @else
                        <p class="plan-price">${{ number_format($plan['price']) }} <small>MXN/mes</small></p>
                    @endif
                    <p class="plan-summary">{{ $plan['summary'] }}</p>
                    <ul class="feature-list compact">
                        @foreach(array_slice($plan['features'], 0, 6) as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a class="button" href="{{ route('plans.show', $plan['slug']) }}">Ver detalle</a>
                </article>
            @endforeach

            <article class="plan-card plan-card-custom" data-category="custom">
                <span class="plan-tier">Una opción hecha a tu medida</span>
                <div class="plan-card-heading">
                    <h2>Personaliza tu agente</h2>
                    <span class="plan-badge">Flexible</span>
                </div>
                <p class="plan-price"><small>Desde</small> $499 <small>MXN/mes</small></p>
                <p class="plan-summary">Comienza con Agente Inicio y agrega solo las funciones que tu negocio necesita.</p>
                <ul class="feature-list compact">
                    <li>Base clara de funciones esenciales</li>
                    <li>Módulos Plus y Avanzados</li>
                    <li>Resumen mensual en tiempo real</li>
                </ul>
                <a class="button" href="{{ route('plans.custom') }}">Personalizar agente</a>
            </article>
        </div>
    </section>
@endsection
