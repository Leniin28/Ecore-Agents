@extends('layouts.app')
@section('title', 'Compras y Pagos de Tienda | Admin ECore')

@section('content')
<div class="admin-layout">
    @if(request()->routeIs('erp.*'))
        @include('erp._sidebar')
    @else
        @include('admin.tienda._sidebar')
    @endif

    <section class="admin-content">
        <div class="admin-section-heading">
            <div>
                <span class="section-eyebrow">Gestión comercial</span>
                <h1>Compras y pagos de la tienda</h1>
            </div>
        </div>

        <form class="customer-filters" method="get" action="{{ url()->current() }}" style="margin-bottom:24px">
            <div style="flex:2">
                <label for="buscar">Buscar compra o pago</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" type="search" placeholder="Referencia UUID, cliente o correo...">
            </div>
            <div>
                <label for="metodo">Método de pago</label>
                <select id="metodo" name="metodo">
                    <option value="">Todos los métodos</option>
                    @foreach($metodos as $clave => $nombre)
                        <option value="{{ $clave }}" @selected(request('metodo') === $clave)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="estado">Estado del pago</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach($estados as $clave => $nombre)
                        <option value="{{ $clave }}" @selected(request('estado') === $clave)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button class="button" type="submit">Filtrar</button>
                <a class="back-button" href="{{ url()->current() }}">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Orden ERP</th>
                        <th>Cliente CRM</th>
                        <th>Método</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Referencia</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pagos as $pago)
                        <tr>
                            <td style="white-space:nowrap;font-size:.85rem;color:var(--muted)">
                                {{ $pago->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td style="font-weight:900;color:var(--blue-dark);white-space:nowrap">
                                {{ $pago->ordenEmpresarial->folio() }}
                            </td>
                            <td>
                                <strong>{{ $pago->cliente->nombre }}</strong>
                                <small style="display:block;color:var(--muted)">{{ $pago->cliente->correo }}</small>
                            </td>
                            <td>{{ $pago->metodoLabel() }}</td>
                            <td style="font-weight:900;white-space:nowrap">${{ number_format($pago->total_centavos / 100, 2) }} MXN</td>
                            <td>
                                <span class="store-badge badge-{{ $pago->estado }}">{{ $pago->estadoLabel() }}</span>
                            </td>
                            <td>
                                <small style="font-family:monospace;color:var(--muted)">{{ Str::limit($pago->referencia, 13) }}</small>
                            </td>
                            <td style="text-align:right">
                                @php
                                    $showRoute = request()->routeIs('erp.*')
                                        ? route('erp.tienda.ordenes.show', $pago)
                                        : route('admin.tienda.ordenes.show', $pago);
                                @endphp
                                <a class="button" href="{{ $showRoute }}" style="padding:6px 12px;min-height:36px;font-size:.85rem">
                                    Ver comprobante
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px">No se encontraron pagos registrados con los filtros aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">
            {{ $pagos->links() }}
        </div>
    </section>
</div>
@endsection
