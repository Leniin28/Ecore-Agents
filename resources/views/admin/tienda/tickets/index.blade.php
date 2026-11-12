@extends('layouts.app')
@section('title', 'Atención a Tickets | Admin ECore')

@section('content')
<div class="admin-layout">
    @if(request()->routeIs('soporte.*'))
        <aside class="admin-sidebar"><strong>Soporte</strong><p>Atención al cliente</p></aside>
    @elseif(request()->routeIs('crm.*'))
        @include('admin._sidebar')
    @else
        @include('admin.tienda._sidebar')
    @endif

    <section class="admin-content">
        <div class="admin-section-heading">
            <div>
                <span class="section-eyebrow">Atención y CRM</span>
                <h1>Tickets de atención al cliente</h1>
            </div>
        </div>

        <form class="customer-filters" method="get" action="{{ url()->current() }}" style="margin-bottom:24px">
            <div style="flex:2">
                <label for="buscar">Buscar ticket</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" type="search" placeholder="Asunto, nombre o correo del cliente...">
            </div>
            <div>
                <label for="estado">Filtrar por estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    @foreach(\App\Models\TicketAtencion::ESTADOS as $clave => $nombre)
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
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Asunto</th>
                        <th>Compra asociada</th>
                        <th>Estado</th>
                        <th>Atendido por</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $tck)
                        <tr>
                            <td style="font-weight:900;color:var(--blue-dark);white-space:nowrap">{{ $tck->folio() }}</td>
                            <td style="font-size:.85rem;color:var(--muted);white-space:nowrap">{{ $tck->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>{{ $tck->cliente->nombre }}</strong>
                                <small style="display:block;color:var(--muted)">{{ $tck->cliente->correo }}</small>
                            </td>
                            <td>
                                <strong>{{ $tck->asunto }}</strong>
                                <p style="margin:2px 0 0;font-size:.85rem;color:var(--muted)">{{ Str::limit($tck->mensaje, 50) }}</p>
                            </td>
                            <td>
                                @if($tck->ordenEmpresarial)
                                    <span style="font-weight:700;color:var(--blue)">{{ $tck->ordenEmpresarial->folio() }}</span>
                                @else
                                    <span style="color:var(--muted);font-size:.85rem">General</span>
                                @endif
                            </td>
                            <td>
                                <span class="store-badge badge-{{ $tck->estado }}">{{ $tck->estadoLabel() }}</span>
                            </td>
                            <td style="font-size:.85rem;color:var(--muted)">
                                {{ $tck->respondidoPor->name ?? 'Pendiente' }}
                            </td>
                            <td style="text-align:right">
                                <a class="button" href="{{ request()->routeIs('soporte.*') ? route('soporte.tickets.show', $tck) : (request()->routeIs('crm.*') ? route('crm.tickets.show', $tck) : route('admin.tienda.tickets.show', $tck)) }}" style="padding:6px 12px;min-height:36px;font-size:.85rem">
                                    Responder / Atender
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px">No se encontraron tickets con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">
            {{ $tickets->links() }}
        </div>
    </section>
</div>
@endsection
