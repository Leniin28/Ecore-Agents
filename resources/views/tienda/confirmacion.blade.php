@extends('layouts.app')
@section('title', 'Comprobante de Compra Demo | ECore Agents')

@section('content')
<section class="section" style="max-width:880px;margin:0 auto;padding-top:40px">
    <div style="background:#fff;border:1px solid var(--line);border-radius:22px;padding:clamp(24px, 5vw, 44px);box-shadow:var(--shadow)">
        <div style="text-align:center;margin-bottom:32px">
            @if($pago->estado === 'pendiente')
                <div style="width:68px;height:68px;margin:0 auto 16px;background:#fef3c7;color:#b45309;border-radius:50%;display:grid;place-items:center;font-size:2rem;font-weight:900">
                    ⏳
                </div>
                <span class="section-eyebrow" style="color:#b45309">Transferencia demo registrada</span>
                <h1 style="margin:4px 0 10px;font-size:clamp(1.8rem,4vw,2.5rem)">Orden pendiente de confirmación</h1>
                <p style="color:var(--muted);font-size:1.05rem;margin:0">
                    Tu orden por transferencia demo ha sido registrada. La administración validará el comprobante bancario antes de confirmar los cupos.
                </p>
            @else
                <div style="width:68px;height:68px;margin:0 auto 16px;background:#e6f9f0;color:#085a3a;border-radius:50%;display:grid;place-items:center;font-size:2rem;font-weight:900">
                    ✓
                </div>
                <span class="section-eyebrow">Compra simulada confirmada</span>
                <h1 style="margin:4px 0 10px;font-size:clamp(1.8rem,4vw,2.5rem)">¡Gracias por tu compra!</h1>
                <p style="color:var(--muted);font-size:1.05rem;margin:0">
                    Tu orden ha sido registrada exitosamente en el sistema ERP e integrada a los módulos de ECore Agents.
                </p>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;padding:20px;background:#f8faff;border:1px solid #dbe6f6;border-radius:14px;margin-bottom:28px">
            <div>
                <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Folio de Orden ERP</small>
                <p style="margin:4px 0 0;font-size:1.25rem;font-weight:900;color:var(--blue-dark)">{{ $orden->folio() }}</p>
            </div>
            <div>
                <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Referencia de Pago</small>
                <p style="margin:4px 0 0;font-family:monospace;font-size:.9rem;font-weight:700;word-break:break-all">{{ $pago->referencia }}</p>
            </div>
            <div>
                <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Método de Pago</small>
                <p style="margin:4px 0 0;font-weight:800">{{ $pago->metodoLabel() }}</p>
            </div>
            <div>
                <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Estado de la Orden</small>
                <div style="margin-top:4px">
                    <span class="store-badge badge-{{ $pago->estado }}">{{ $pago->estadoLabel() }}</span>
                </div>
            </div>
        </div>

        <h3 style="margin:0 0 14px;font-size:1.2rem">Desglose de la compra</h3>
        <div class="table-responsive" style="margin-bottom:24px">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Producto / Plan</th>
                        <th style="text-align:center">Cantidad</th>
                        <th style="text-align:right">Precio unitario</th>
                        <th style="text-align:right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pago->desglose['lineas'] ?? [] as $linea)
                        <tr>
                            <td>
                                <strong>{{ $linea['nombre'] }}</strong>
                                <span style="display:block;font-size:.8rem;color:var(--muted)">{{ $linea['categoria'] }}</span>
                                @if(!empty($linea['configuracion']['modulos']))
                                    <ul class="cart-item-config">
                                        @foreach($linea['configuracion']['modulos'] as $mod)
                                            <li>+ {{ $mod['name'] }} (+${{ number_format($mod['price']) }} MXN)</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td style="text-align:center;font-weight:800">{{ $linea['cantidad'] }}</td>
                            <td style="text-align:right">${{ number_format($linea['precio_unitario_centavos'] / 100, 2) }}</td>
                            <td style="text-align:right;font-weight:900">${{ number_format($linea['subtotal_linea_centavos'] / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
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
                    <tr style="font-size:1.25rem;border-top:2px solid var(--line);color:var(--blue-dark)">
                        <td colspan="3" style="text-align:right;font-weight:900;padding-top:12px">Total pagado:</td>
                        <td style="text-align:right;font-weight:900;padding-top:12px">{{ $pago->total_formateado }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="background:#f9fafb;border:1px solid var(--line);border-radius:12px;padding:18px;margin-bottom:28px">
            <h4 style="margin:0 0 10px;font-size:1rem">Datos registrados para el servicio:</h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:10px;font-size:.9rem">
                <div><strong>Contacto:</strong> {{ $pago->datos_entrega['nombre_contacto'] ?? '-' }}</div>
                <div><strong>Correo:</strong> {{ $pago->datos_entrega['correo_contacto'] ?? '-' }}</div>
                <div><strong>Teléfono:</strong> {{ $pago->datos_entrega['telefono_contacto'] ?? '-' }}</div>
                <div><strong>Empresa:</strong> {{ $pago->datos_entrega['empresa'] ?? 'Particular' }}</div>
            </div>
            @if(!empty($pago->datos_entrega['notas_servicio']))
                <div style="margin-top:10px;font-size:.9rem">
                    <strong>Notas:</strong> {{ $pago->datos_entrega['notas_servicio'] }}
                </div>
            @endif
        </div>

        <div style="padding:14px 18px;background:#eef4ff;border:1px solid #ccdcf6;border-radius:12px;font-size:.88rem;color:#1e3a8a;margin-bottom:28px;line-height:1.6">
            <strong>Aviso de simulación académica:</strong> Esta transacción es una simulación comercial. Los cupos y movimientos se integran en el inventario SCM y órdenes ERP de tu cuenta, sin realizar cobros a tarjetas reales ni llamadas externas a WhatsApp. Tu comprobante permanece consultable en todo momento.
        </div>

        <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:12px">
            <a class="button" href="{{ route('mis-compras.show', $orden) }}">Ver compra en mi portal</a>
            <a class="back-button" href="{{ route('tickets.create', ['orden_id' => $orden->id]) }}">Abrir ticket de atención</a>
            <a class="back-button" href="{{ route('catalogo.index') }}">Volver al catálogo</a>
        </div>
    </div>
</section>
@endsection
