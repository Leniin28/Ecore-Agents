@extends('layouts.app')
@section('title', 'Catálogo Comercial | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div class="admin-section-heading">
            <div>
                <span class="section-eyebrow">Comercialización ECore</span>
                <h1>Catálogo y ofertas de tienda</h1>
            </div>
        </div>

        <div style="background:#eef5ff;border:1px solid #ccdcf6;border-radius:12px;padding:16px 20px;margin-bottom:24px;font-size:.9rem;color:#1e3a8a;line-height:1.6">
            <strong>Protección de inventario interno SCM:</strong> Los recursos técnicos del SCM permanecen internos y privados por defecto. Solamente los productos expresamente autorizados como <em>Visible en tienda</em> se publican en el catálogo comercial público.
        </div>

        <form class="customer-filters" method="get" action="{{ route('admin.tienda.productos.index') }}" style="margin-bottom:24px">
            <div style="flex:2">
                <label for="buscar">Buscar producto</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" type="search" placeholder="Nombre de plan o recurso...">
            </div>
            <div>
                <label for="tienda">Estado en tienda</label>
                <select id="tienda" name="tienda">
                    <option value="">Todos los productos</option>
                    <option value="visibles" @selected(request('tienda') === 'visibles')>Visibles en tienda</option>
                    <option value="ocultos" @selected(request('tienda') === 'ocultos')>Ocultos / Internos</option>
                </select>
            </div>
            <div>
                <label for="categoria_id">Categoría</label>
                <select id="categoria_id" name="categoria_id">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" @selected(request('categoria_id') == $cat->id || request('categoria_id') === $cat->nombre)>{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button class="button" type="submit">Filtrar</button>
                <a class="back-button" href="{{ route('admin.tienda.productos.index') }}">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio comercial</th>
                        <th>Stock disponible</th>
                        <th>Destacado</th>
                        <th>Estado comercial</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->nombre }}</strong>
                                <small style="display:block;color:var(--muted)">Prov: {{ $p->proveedor->nombre ?? 'Sin proveedor' }}</small>
                            </td>
                            <td>{{ $p->categoriaRelacion?->nombre ?? $p->categoria }}</td>
                            <td style="font-weight:800">${{ number_format((float) $p->precio_venta_unitario, 2) }} MXN</td>
                            <td>
                                @if($p->stock_actual > 0)
                                    <span style="font-weight:800;color:#085a3a">{{ $p->stock_actual }} cupos</span>
                                @else
                                    <span style="font-weight:800;color:#b42318">0 (Agotado)</span>
                                @endif
                            </td>
                            <td>
                                @if($p->destacado)
                                    <span class="store-badge badge-respondido">Destacado</span>
                                @else
                                    <span style="color:var(--muted);font-size:.85rem">Estándar</span>
                                @endif
                            </td>
                            <td>
                                @if($p->visible_tienda)
                                    <span class="store-badge badge-activo">Visible en tienda</span>
                                @else
                                    <span class="store-badge badge-inactivo">Oculto / Interno</span>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap">
                                <div style="display:inline-flex;gap:6px">
                                    <form method="post" action="{{ route('admin.tienda.productos.toggle', $p) }}">
                                        @csrf
                                        <button class="quantity-btn" type="submit" title="{{ $p->visible_tienda ? 'Ocultar de tienda' : 'Publicar en tienda' }}">
                                            {{ $p->visible_tienda ? 'Ocultar' : 'Publicar' }}
                                        </button>
                                    </form>
                                    <a class="button" href="{{ route('admin.tienda.productos.edit', $p) }}" style="padding:6px 12px;min-height:36px;font-size:.85rem">
                                        Editar oferta
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">
            {{ $productos->links() }}
        </div>
    </section>
</div>
@endsection
