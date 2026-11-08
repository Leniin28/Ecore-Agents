@extends('layouts.app')
@section('title', 'Detalle de Compra '.$orden->folio().' | ECore Agents')

@section('content')
<section class="section" style="max-width:960px;margin:0 auto;padding-top:40px">
    <div style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <a class="back-button" href="{{ route('mis-compras.index') }}">
            &larr; Volver a mis compras
        </a>
        <a class="button" href="{{ route('tickets.create', ['orden_id' => $orden->id]) }}" style="background:var(--plus);border-color:var(--plus)">
            Abrir ticket sobre esta compra
        </a>
    </div>

    <article class="checkout-card" style="padding:clamp(20px, 4vw, 36px)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;border-bottom:1px solid var(--line);padding-bottom:20px;margin-bottom:24px">
            <div>
                <span class="section-eyebrow">Orden Empresarial</span>
                <h1 style="margin:4px 0 6px;font-size:2rem">{{ $orden->folio() }}</h1>
                <p style="color:var(--muted);margin:0;font-size:.9rem">
                    Fecha de emisión: {{ $orden->fecha->format('d/m/Y H:i') }}
                    @if($orden->procesada_at)
                        · Procesada: {{ $orden->procesada_at->format('d/m/Y H:i') }}
                    @endif
                </p>
            </div>
            <div style="text-align:right">
                <span class="store-badge badge-{{ $orden->estado }}" style="font-size:.9rem;padding:6px 14px">
                    {{ ucfirst($orden->estado) }}
                </span>
                <p style="margin:8px 0 0;font-size:1.4rem;font-weight:900;color:var(--blue-dark)">
                    ${{ number_format((float) $orden->total, 2) }} MXN
                </p>
            </div>
        </div>

        @if($pago)
            <div style="background:#f8faff;border:1px solid #dbe6f6;border-radius:14px;padding:18px;margin-bottom:28px;display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;font-size:.9rem">
                <div>
                    <strong>Referencia de pago:</strong>
                    <div style="font-family:monospace;color:var(--muted);word-break:break-all">{{ $pago->referencia }}</div>
                </div>
                <div>
                    <strong>Método utilizado:</strong>
                    <div>{{ $pago->metodoLabel() }}</div>
                </div>
                <div>
                    <strong>Estado del pago:</strong>
                    <div><span class="store-badge badge-{{ $pago->estado }}">{{ $pago->estadoLabel() }}</span></div>
                </div>
                <div>
                    <strong>Cliente CRM:</strong>
                    <div>{{ $orden->cliente->nombre }} ({{ $orden->cliente->correo }})</div>
                </div>
            </div>
        @endif

        <h3 style="margin:0 0 14px;font-size:1.15rem">Recursos y planes adquiridos</h3>
        <div class="table-responsive" style="margin-bottom:24px">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th style="text-align:center">Cantidad</th>
                        <th style="text-align:right">Precio unitario</th>
                        <th style="text-align:right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orden->detalles as $det)
                        <tr>
                            <td>
                                <strong>{{ $det->producto->nombre ?? 'Recurso' }}</strong>
                                <span style="display:block;font-size:.8rem;color:var(--muted)">{{ $det->producto->categoria ?? 'General' }}</span>
                                @if(!empty($det->configuracion['modulos']))
                                    <ul class="cart-item-config">
                                        @foreach($det->configuracion['modulos'] as $m)
                                            <li>+ {{ $m['name'] }} (+${{ number_format($m['price']) }} MXN)</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td style="text-align:center;font-weight:800">{{ $det->cantidad }}</td>
                            <td style="text-align:right">${{ number_format((float) $det->precio_unitario, 2) }}</td>
                            <td style="text-align:right;font-weight:900">${{ number_format((float) $det->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                @if($pago && !empty($pago->desglose))
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right;padding-top:14px">Subtotal:</td>
                            <td style="text-align:right;padding-top:14px;font-weight:800">{{ $pago->subtotal_formateado }}</td>
                        </tr>
                        @if($pago->descuento_centavos > 0)
                            <tr style="color:#085a3a">
                                <td colspan="3" style="text-align:right">Descuento aplicado:</td>
                                <td style="text-align:right;font-weight:800">-{{ $pago->descuento_formateado }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3" style="text-align:right">Base gravable:</td>
                            <td style="text-align:right;font-weight:800">${{ number_format($pago->base_gravable_centavos / 100, 2) }} MXN</td>
                        </tr>
                        @foreach($pago->desglose['impuestos'] ?? [] as $imp)
                            <tr>
                                <td colspan="3" style="text-align:right">{{ $imp['nombre'] }} ({{ $imp['porcentaje'] }}):</td>
                                <td style="text-align:right;font-weight:800">{{ $imp['monto_formateado'] }}</td>
                            </tr>
                        @endforeach
                        <tr style="font-size:1.2rem;border-top:2px solid var(--line);color:var(--blue-dark)">
                            <td colspan="3" style="text-align:right;font-weight:900;padding-top:10px">Total:</td>
                            <td style="text-align:right;font-weight:900;padding-top:10px">{{ $pago->total_formateado }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if($orden->procesosInternos->isNotEmpty())
            <div style="margin-top:30px;border-top:1px solid var(--line);padding-top:20px">
                <h3 style="margin:0 0 12px;font-size:1.1rem">Trazabilidad operativa ERP</h3>
                <div style="display:grid;gap:8px">
                    @foreach($orden->procesosInternos as $proc)
                        <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#f8fafc;border-radius:8px;font-size:.88rem">
                            <div>
                                <strong style="color:var(--blue)">{{ ucfirst(str_replace('_', ' ', $proc->tipo)) }}</strong>:
                                <span>{{ $proc->descripcion }}</span>
                            </div>
                            <span style="color:var(--muted)">{{ $proc->fecha->format('d/m/Y H:i:s') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </article>
</section>
@endsection
