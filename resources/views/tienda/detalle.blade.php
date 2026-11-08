@extends('layouts.app')
@section('title', $producto->nombre.' | ECore Agents')

@section('content')
<section class="section" style="max-width:1140px;margin:0 auto;padding-top:40px">
    <div style="margin-bottom:20px">
        <a class="back-button" href="{{ route('catalogo.index') }}" style="display:inline-flex;gap:6px">
            &larr; Volver al catálogo comercial
        </a>
    </div>

    <div style="display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,1fr);gap:36px;align-items:start">
        <article class="form-card" style="padding:32px">
            @if($producto->imagen_url)
                <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" style="max-width:100%;max-height:360px;object-fit:contain;margin-bottom:20px;border-radius:14px">
            @else
                <div class="catalog-img-fallback" style="height:260px;margin-bottom:20px;font-size:1.4rem">
                    <span>{{ $producto->categoriaRelacion?->nombre ?? $producto->categoria }}</span>
                </div>
            @endif

            <span class="section-eyebrow">{{ $producto->categoriaRelacion?->nombre ?? $producto->categoria }}</span>
            <h1 style="margin:6px 0 16px;font-size:clamp(1.8rem,4vw,2.4rem)">{{ $producto->nombre }}</h1>
            
            <p style="color:var(--muted);font-size:1.05rem;line-height:1.7;margin-bottom:24px">
                {{ $producto->descripcion }}
            </p>

            @php
                // Verificar si coincide con algún plan en config/ecore.php para mostrar sus características
                $planMatch = null;
                foreach(config('ecore.plans', []) as $p) {
                    if (str_contains(strtolower($producto->nombre), strtolower($p['name']))) {
                        $planMatch = $p;
                        break;
                    }
                }
            @endphp

            @if($planMatch && !empty($planMatch['features']))
                <div style="margin-top:20px;border-top:1px solid var(--line);padding-top:20px">
                    <h3 style="font-size:1.1rem;margin-bottom:12px">Capacidades incluidas:</h3>
                    <ul class="feature-list" style="display:grid;gap:8px">
                        @foreach($planMatch['features'] as $f)
                            <li>{{ $f }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </article>

        <aside class="cart-summary-card">
            <span class="section-eyebrow">Disponibilidad comercial</span>
            <h2 style="margin-top:4px">Adquirir cupos</h2>

            <div style="margin:18px 0">
                @if($precioPromocionalCentavos !== null && $precioPromocionalCentavos < $producto->precio_venta_centavos)
                    <div style="display:flex;align-items:baseline;gap:10px">
                        <span class="old-price" style="font-size:1.2rem">${{ number_format($producto->precio_venta_centavos / 100, 2) }}</span>
                        <p class="plan-price" style="margin:0;color:var(--plus)">
                            ${{ number_format($precioPromocionalCentavos / 100, 2) }} <small>MXN</small>
                        </p>
                    </div>
                    <div style="margin-top:8px">
                        <span class="promotion-pill">{{ $promocion->nombre }} · {{ $promocion->codigo }} ({{ $promocion->etiquetaValor() }})</span>
                    </div>
                @else
                    <p class="plan-price" style="margin:0">
                        ${{ number_format($producto->precio_venta_centavos / 100, 2) }} <small>MXN</small>
                    </p>
                @endif
            </div>

            <div style="margin-bottom:20px">
                @if($producto->stock_actual > 0)
                    <span class="store-badge badge-activo" style="font-size:.85rem;padding:6px 12px">
                        ✓ Disponible en inventario: {{ $producto->stock_actual }} cupos
                    </span>
                @else
                    <span class="store-badge badge-rechazado" style="font-size:.85rem;padding:6px 12px">
                        ✗ Agotado por el momento
                    </span>
                @endif
            </div>

            @if($producto->stock_actual > 0)
                <form method="post" action="{{ route('carrito.store') }}" style="display:grid;gap:14px">
                    @csrf
                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">

                    <div>
                        <label for="cantidad" style="font-weight:800;display:block;margin-bottom:6px">Cantidad de cupos:</label>
                        <input id="cantidad" name="cantidad" type="number" min="1" max="{{ $producto->stock_actual }}" value="1" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px;font-weight:800">
                        <small style="color:var(--muted);display:block;margin-top:4px">Máximo {{ $producto->stock_actual }} por pedido.</small>
                    </div>

                    <button class="button" type="submit" style="width:100%;min-height:48px;font-size:1.05rem">
                        + Agregar a mi carrito
                    </button>
                </form>
            @else
                <div style="padding:16px;background:#fef2f2;border-radius:10px;border:1px solid #fecaca;color:#991b1b;font-weight:700">
                    Este producto no tiene cupos disponibles en este momento. Consulta con nuestro equipo o explora alternativas en el catálogo.
                </div>
            @endif

            <div style="margin-top:24px;border-top:1px solid var(--line);padding-top:18px;font-size:.86rem;color:var(--muted);line-height:1.5">
                <p style="margin:0">
                    <strong>Garantía académica ECore:</strong> Los cupos representan capacidad simulada de agentes IA integrada a tus módulos CRM, SCM y ERP.
                </p>
            </div>
        </aside>
    </div>

    @if(isset($relacionados) && $relacionados->isNotEmpty())
        <div style="margin-top:60px">
            <div class="section-heading" style="text-align:left;margin-bottom:20px">
                <span class="section-eyebrow">Recomendaciones</span>
                <h2 style="font-size:1.6rem">Productos relacionados</h2>
            </div>
            <div class="plan-grid" style="grid-template-columns:repeat(3, minmax(0, 1fr))">
                @foreach($relacionados as $rel)
                    <article class="plan-card">
                        <span class="plan-tier">{{ $rel->categoriaRelacion?->nombre ?? $rel->categoria }}</span>
                        <h2>{{ $rel->nombre }}</h2>
                        <p style="color:var(--muted);font-size:.88rem">{{ Str::limit($rel->descripcion, 80) }}</p>
                        <p class="plan-price" style="margin:8px 0">${{ number_format($rel->precio_venta_centavos / 100, 2) }} <small>MXN</small></p>
                        <a class="back-button" href="{{ route('catalogo.show', $rel) }}">Ver producto</a>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endsection
