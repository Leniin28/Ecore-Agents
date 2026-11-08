@extends('layouts.app')
@section('title', 'Comprobante y Auditoría de Pago | Admin ECore')

@section('content')
<div class="admin-layout">
    @if(request()->routeIs('erp.*'))
        @include('erp._sidebar')
    @else
        @include('admin.tienda._sidebar')
    @endif

    <section class="admin-content">
        <div style="margin-bottom:20px">
            @php
                $backRoute = request()->routeIs('erp.*')
                    ? route('erp.tienda.ordenes.index')
                    : route('admin.tienda.ordenes.index');
            @endphp
            <a class="back-button" href="{{ $backRoute }}">&larr; Volver a compras de tienda</a>
        </div>

        <article class="checkout-card" style="padding:clamp(20px, 4vw, 36px)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;border-bottom:1px solid var(--line);padding-bottom:20px;margin-bottom:24px">
                <div>
                    <span class="section-eyebrow">Auditoría comercial</span>
                    <h1 style="margin:4px 0 6px;font-size:2rem">Pago de {{ $pago->ordenEmpresarial->folio() }}</h1>
                    <p style="color:var(--muted);margin:0;font-size:.9rem">
                        Referencia única: <span style="font-family:monospace;font-weight:700">{{ $pago->referencia }}</span> · Registrado el {{ $pago->created_at->format('d/m/Y H:i:s') }}
                    </p>
                </div>
                <div style="text-align:right">
                    <span class="store-badge badge-{{ $pago->estado }}" style="font-size:.95rem;padding:6px 14px">
                        {{ $pago->estadoLabel() }}
                    </span>
                    <p style="margin:8px 0 0;font-size:1.5rem;font-weight:900;color:var(--blue-dark)">
                        ${{ number_format($pago->total_centavos / 100, 2) }} MXN
                    </p>
                </div>
            </div>

            <div style="background:#f8faff;border:1px solid #dbe6f6;border-radius:14px;padding:20px;margin-bottom:28px;display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;font-size:.9rem">
                <div>
                    <strong>Cliente CRM:</strong>
                    <div>{{ $pago->cliente->nombre }} ({{ $pago->cliente->correo }})</div>
                    <small style="color:var(--muted)">Empresa: {{ $pago->cliente->empresa ?? 'Particular' }}</small>
                </div>
                <div>
                    <strong>Método de pago:</strong>
                    <div>{{ $pago->metodoLabel() }}</div>
                </div>
                <div>
                    <strong>Orden ERP vinculada:</strong>
                    <div>
                        <a href="{{ route('erp.ordenes.show', $pago->ordenEmpresarial) }}" style="color:var(--blue);font-weight:800">
                            {{ $pago->ordenEmpresarial->folio() }} (Ver en ERP)
                        </a>
                    </div>
                </div>
                <div>
                    <strong>Movimiento monedero:</strong>
                    <div>
                        @if($pago->monederoMovimiento)
                            <span style="color:#085a3a;font-weight:800">{{ $pago->monederoMovimiento->monto_formateado }}</span>
                        @else
                            <span style="color:var(--muted)">No aplicó</span>
                        @endif
                    </div>
                </div>
            </div>

            <h3 style="margin:0 0 14px;font-size:1.15rem">Líneas y desglose comercial</h3>
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
                        @foreach($pago->desglose['lineas'] ?? [] as $linea)
                            <tr>
                                <td>
                                    <strong>{{ $linea['nombre'] }}</strong>
                                    <span style="display:block;font-size:.8rem;color:var(--muted)">{{ $linea['categoria'] }}</span>
                                    @if(!empty($linea['configuracion']['modulos']))
                                        <ul class="cart-item-config">
                                            @foreach($linea['configuracion']['modulos'] as $m)
                                                <li>+ {{ $m['name'] }} (+${{ number_format($m['price']) }} MXN)</li>
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
                                <td colspan="3" style="text-align:right">Descuento promoción:</td>
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
                            <td colspan="3" style="text-align:right;font-weight:900;padding-top:10px">Total cobrado:</td>
                            <td style="text-align:right;font-weight:900;padding-top:10px">{{ $pago->total_formateado }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Datos de entrega registrados -->
            <div style="background:#f9fafb;border:1px solid var(--line);border-radius:12px;padding:18px;margin-bottom:28px">
                <h4 style="margin:0 0 10px;font-size:1rem">Datos de contacto para el servicio:</h4>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:10px;font-size:.9rem">
                    <div><strong>Contacto:</strong> {{ $pago->datos_entrega['nombre_contacto'] ?? '-' }}</div>
                    <div><strong>Correo:</strong> {{ $pago->datos_entrega['correo_contacto'] ?? '-' }}</div>
                    <div><strong>Teléfono:</strong> {{ $pago->datos_entrega['telefono_contacto'] ?? '-' }}</div>
                    <div><strong>Empresa:</strong> {{ $pago->datos_entrega['empresa'] ?? 'Particular' }}</div>
                </div>
                @if(!empty($pago->datos_entrega['notas_servicio']))
                    <div style="margin-top:10px;font-size:.9rem">
                        <strong>Instrucciones:</strong> {{ $pago->datos_entrega['notas_servicio'] }}
                    </div>
                @endif
            </div>

            @if($pago->ordenEmpresarial->procesosInternos->isNotEmpty())
                <div style="border-top:1px solid var(--line);padding-top:20px">
                    <h3 style="margin:0 0 12px;font-size:1.1rem">Procesos operativos auditados</h3>
                    <div style="display:grid;gap:8px">
                        @foreach($pago->ordenEmpresarial->procesosInternos as $proc)
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
</div>
@endsection
