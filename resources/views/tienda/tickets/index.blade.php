@extends('layouts.app')
@section('title', 'Atención y Tickets de Soporte | ECore Agents')

@section('content')
<section class="section" style="max-width:1040px;margin:0 auto;padding-top:40px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:28px">
        <div>
            <span class="section-eyebrow">Atención a clientes</span>
            <h1 style="margin:4px 0 6px">Mis tickets de atención</h1>
            <p style="color:var(--muted);margin:0">Comunícate con nuestro equipo para dudas sobre tus planes, complementos o facturación simulada.</p>
        </div>
        <a class="button" href="{{ route('tickets.create') }}">+ Nuevo ticket</a>
    </div>

    <article class="checkout-card" style="padding:28px">
        @if($tickets->isEmpty())
            <div style="text-align:center;padding:48px 20px">
                <span class="section-eyebrow">Bandeja al día</span>
                <h2>No tienes tickets de soporte registrados</h2>
                <p style="color:var(--muted);max-width:440px;margin:8px auto 20px">
                    ¿Tienes dudas sobre la configuración de tus agentes o tus compras? Abre un ticket y te responderemos a la brevedad.
                </p>
                <a class="button" href="{{ route('tickets.create') }}">Abrir mi primer ticket</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Asunto</th>
                            <th>Compra relacionada</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $tck)
                            <tr>
                                <td style="font-weight:900;color:var(--blue-dark);white-space:nowrap">
                                    {{ $tck->folio() }}
                                </td>
                                <td style="font-size:.88rem;color:var(--muted);white-space:nowrap">
                                    {{ $tck->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <strong>{{ $tck->asunto }}</strong>
                                    <p style="margin:2px 0 0;font-size:.85rem;color:var(--muted)">{{ Str::limit($tck->mensaje, 70) }}</p>
                                </td>
                                <td>
                                    @if($tck->ordenEmpresarial)
                                        <a href="{{ route('mis-compras.show', $tck->ordenEmpresarial) }}" style="color:var(--blue);font-weight:700">
                                            {{ $tck->ordenEmpresarial->folio() }}
                                        </a>
                                    @else
                                        <span style="color:var(--muted);font-size:.85rem">General</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="store-badge badge-{{ $tck->estado }}">
                                        {{ $tck->estadoLabel() }}
                                    </span>
                                </td>
                                <td style="text-align:right">
                                    <a class="back-button" href="{{ route('tickets.show', $tck) }}" style="padding:6px 14px;min-height:36px;font-size:.85rem">
                                        Ver conversación
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px">
                {{ $tickets->links() }}
            </div>
        @endif
    </article>
</section>
@endsection
