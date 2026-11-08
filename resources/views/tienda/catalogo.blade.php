@extends('layouts.app')
@section('title', 'Tienda y Catálogo Comercial | ECore Agents')
@section('body-class', 'catalog-page')

@section('content')
<section class="section" style="padding-top:40px">
    <div class="section-heading">
        <span class="section-eyebrow">Tienda Comercial ECore</span>
        <h1>Planes y complementos de IA</h1>
        <p>Capacidades listas para implementar en tu atención y ventas comerciales por WhatsApp.</p>
    </div>

    <form class="customer-filters" method="get" action="{{ route('catalogo.index') }}" style="max-width:1140px;margin:0 auto 34px">
        <div style="flex:2;min-width:220px">
            <label for="buscar">Buscar planes o funciones</label>
            <input id="buscar" name="buscar" value="{{ request('buscar') }}" type="search" placeholder="Ej. Inicio, Plus, Citas, Reportes...">
        </div>
        <div>
            <label for="categoria_id">Categoría</label>
            <select id="categoria_id" name="categoria_id">
                <option value="">Todas las categorías</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" @selected(request('categoria_id') == $cat->id || request('categoria_id') === $cat->nombre)>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="orden">Ordenar por</label>
            <select id="orden" name="orden">
                <option value="destacados" @selected(request('orden') === 'destacados')>Destacados primero</option>
                <option value="novedades" @selected(request('orden') === 'novedades')>Novedades</option>
                <option value="precio_menor" @selected(request('orden') === 'precio_menor')>Precio: menor a mayor</option>
                <option value="precio_mayor" @selected(request('orden') === 'precio_mayor')>Precio: mayor a menor</option>
            </select>
        </div>
        <div style="min-width:160px;display:flex;flex-direction:column;justify-content:center;gap:6px">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="checkbox" name="destacados" value="1" @checked(request()->boolean('destacados'))>
                <span>Solo destacados</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                <input type="checkbox" name="disponibles" value="1" @checked(request()->boolean('disponibles'))>
                <span>Con stock disponible</span>
            </label>
        </div>
        <div style="display:flex;gap:8px;min-width:auto">
            <button class="button" type="submit" style="min-height:44px">Filtrar</button>
            <a class="back-button" href="{{ route('catalogo.index') }}" style="min-height:44px">Limpiar</a>
        </div>
    </form>

    <div class="plan-grid" style="max-width:1140px">
        @forelse($productos as $producto)
            @php
                $promocion = $promocionesVigentes->first(fn($p) => $p->aplicaAProducto($producto->id));
                $precioOriginalCentavos = $producto->precio_venta_centavos;
                $descuentoCentavos = $promocion ? $promocion->calcularDescuento($precioOriginalCentavos) : 0;
                $precioFinalCentavos = max(0, $precioOriginalCentavos - $descuentoCentavos);
            @endphp
            <article class="plan-card @if($producto->destacado) plan-card-plus @endif">
                @if($producto->destacado)
                    <span class="recommended-label">Destacado</span>
                @endif

                @if($producto->imagen_url)
                    <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" loading="lazy" style="width:100%;height:140px;object-fit:contain;margin-bottom:12px;border-radius:10px">
                @else
                    <div class="catalog-img-fallback" style="margin-bottom:12px">
                        <span>{{ $producto->categoria ?? 'ECore' }}</span>
                    </div>
                @endif

                <span class="plan-tier">{{ $producto->categoriaRelacion?->nombre ?? $producto->categoria }}</span>
                <div class="plan-card-heading">
                    <h2 style="font-size:1.3rem">{{ $producto->nombre }}</h2>
                </div>

                <p style="color:var(--muted);font-size:.9rem;line-height:1.5;margin:8px 0 16px;flex:1">{{ Str::limit($producto->descripcion, 100) }}</p>

                <div class="plan-pricing" style="margin-bottom:12px">
                    @if($descuentoCentavos > 0)
                        <span class="old-price">${{ number_format($precioOriginalCentavos / 100, 2) }}</span>
                        <p class="plan-price" style="color:var(--plus);margin:4px 0 0">
                            ${{ number_format($precioFinalCentavos / 100, 2) }} <small>MXN</small>
                        </p>
                        <span class="promotion-pill">{{ $promocion->nombre }} · {{ $promocion->codigo }} ({{ $promocion->etiquetaValor() }})</span>
                    @else
                        <p class="plan-price" style="margin:0">
                            ${{ number_format($precioOriginalCentavos / 100, 2) }} <small>MXN</small>
                        </p>
                    @endif
                </div>

                <div style="margin-bottom:16px">
                    @if($producto->stock_actual > 0)
                        <span class="store-badge badge-activo">Disponible: {{ $producto->stock_actual }} cupos</span>
                    @else
                        <span class="store-badge badge-rechazado">Agotado</span>
                    @endif
                </div>

                <div style="display:grid;gap:8px;margin-top:auto">
                    <a class="back-button" href="{{ route('catalogo.show', $producto) }}" style="width:100%">Ver detalle</a>
                    @if($producto->stock_actual > 0)
                        <form method="post" action="{{ route('carrito.store') }}">
                            @csrf
                            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                            <input type="hidden" name="cantidad" value="1">
                            <button class="button" type="submit" style="width:100%">+ Agregar al carrito</button>
                        </form>
                    @else
                        <button class="button" type="button" disabled style="opacity:.5;cursor:not-allowed;width:100%">Agotado</button>
                    @endif
                </div>
            </article>
        @empty
            <div style="grid-column:1 / -1;text-align:center;padding:48px 20px;background:#fff;border-radius:18px;border:1px solid var(--line)">
                <span class="section-eyebrow">Sin resultados</span>
                <h2>No encontramos ofertas disponibles</h2>
                <p style="color:var(--muted);max-width:480px;margin:8px auto 20px">No hay productos comerciales que coincidan con los criterios de búsqueda.</p>
                <a class="button" href="{{ route('catalogo.index') }}">Ver catálogo completo</a>
            </div>
        @endforelse
    </div>

    <div style="max-width:1140px;margin:32px auto 0">
        {{ $productos->links() }}
    </div>
</section>
@endsection
