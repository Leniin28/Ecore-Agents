@extends('layouts.app')
@section('title', 'Mis Compras Comerciales | ECore Agents')

@section('content')
<section class="section" style="max-width:1040px;margin:0 auto;padding-top:40px">
    <div class="section-heading" style="text-align:left;margin-bottom:28px">
        <span class="section-eyebrow">Portal de cliente</span>
        <h1>Mis compras de planes y cupos</h1>
        <p>Historial consolidado de órdenes empresariales y pagos generados en ECore Agents.</p>
    </div>

    <article class="checkout-card" style="padding:28px">
        @if($compras->isEmpty())
            <div style="text-align:center;padding:48px 20px">
                <span class="section-eyebrow">Sin compras registradas</span>
                <h2>Aún no has realizado ninguna compra</h2>
                <p style="color:var(--muted);max-width:440px;margin:8px auto 20px">
                    Consulta los planes de agentes y complementos en nuestra tienda para comenzar a automatizar tus conversaciones.
                </p>
                <a class="button" href="{{ route('catalogo.index') }}">Explorar catálogo</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Planes / Recursos</th>
                            <th>Método de pago</th>
                            <th>Estado</th>
                            <th style="text-align:right">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compras as $orden)
                            <tr>
                                <td style="font-weight:900;color:var(--blue-dark);white-space:nowrap">
                                    {{ $orden->folio() }}
                                </td>
                                <td style="font-size:.88rem;color:var(--muted);white-space:nowrap">
                                    {{ $orden->fecha->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div style="font-size:.9rem">
                                        @foreach($orden->detalles as $det)
                                            <div>
                                                <strong>{{ $det->cantidad }}x</strong> {{ $det->producto->nombre ?? 'Recurso' }}
                                                @if(!empty($det->configuracion['modulos']))
                                                    <small style="color:var(--muted)">({{ count($det->configuracion['modulos']) }} módulos)</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td style="font-size:.9rem">
                                    {{ $orden->pago?->metodoLabel() ?? 'Sin registrar' }}
                                </td>
                                <td>
                                    <span class="store-badge badge-{{ $orden->estado }}">
                                        {{ ucfirst($orden->estado) }}
                                    </span>
                                </td>
                                <td style="text-align:right;font-weight:900;white-space:nowrap">
                                    ${{ number_format((float) $orden->total, 2) }} MXN
                                </td>
                                <td style="text-align:right">
                                    <a class="button" href="{{ route('mis-compras.show', $orden) }}" style="padding:6px 14px;min-height:36px;font-size:.85rem">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px">
                {{ $compras->links() }}
            </div>
        @endif
    </article>
</section>
@endsection
