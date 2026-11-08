@extends('layouts.app')
@section('title', 'Mi Carrito de Compras | ECore Agents')

@section('content')
<section class="section" style="max-width:1140px;margin:0 auto;padding-top:40px">
    <div class="section-heading" style="text-align:left;margin-bottom:28px">
        <span class="section-eyebrow">Tu pedido comercial</span>
        <h1>Carrito de compras</h1>
        <p>Revisa tus planes, complementos y capacidades seleccionadas antes de proceder al pago.</p>
    </div>

    @if(!empty($totales['alertas_stock']))
        <div class="flash-alerts" style="margin-bottom:20px;padding:0">
            @foreach($totales['alertas_stock'] as $alerta)
                <div class="alert-banner alert-warning">{{ $alerta }}</div>
            @endforeach
        </div>
    @endif

    @if(empty($totales['lineas']))
        <div style="text-align:center;padding:64px 20px;background:#fff;border-radius:18px;border:1px solid var(--line);box-shadow:var(--shadow)">
            <span class="section-eyebrow">Carrito vacío</span>
            <h2>Aún no has agregado planes o complementos</h2>
            <p style="color:var(--muted);max-width:480px;margin:10px auto 24px">
                Explora el catálogo comercial de ECore Agents para encontrar agentes de IA, módulos de transcripción, agendas y más.
            </p>
            <a class="button" href="{{ route('catalogo.index') }}">Explorar catálogo</a>
        </div>
    @else
        <div class="cart-layout">
            <div>
                <div class="cart-items-card">
                    <div class="table-responsive">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Producto / Plan</th>
                                    <th>Precio unitario</th>
                                    <th style="text-align:center">Cantidad</th>
                                    <th style="text-align:right">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($totales['lineas'] as $linea)
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:flex-start;gap:12px">
                                                @if(!empty($linea['imagen_url']))
                                                    <img src="{{ $linea['imagen_url'] }}" alt="{{ $linea['nombre'] }}" style="width:50px;height:50px;object-fit:contain;border-radius:8px;background:#f5f8fc;padding:4px">
                                                @else
                                                    <div style="width:50px;height:50px;border-radius:8px;background:#edf4ff;color:var(--blue);display:grid;place-items:center;font-weight:900;font-size:.8rem">
                                                        IA
                                                    </div>
                                                @endif
                                                <div>
                                                    <h3 class="cart-item-title">{{ $linea['nombre'] }}</h3>
                                                    <span style="font-size:.78rem;font-weight:800;color:var(--muted);text-transform:uppercase">{{ $linea['categoria'] }}</span>

                                                    @if(!empty($linea['configuracion']['modulos']))
                                                        <ul class="cart-item-config">
                                                            @foreach($linea['configuracion']['modulos'] as $mod)
                                                                <li>+ {{ $mod['name'] }} (+${{ number_format($mod['price']) }} MXN)</li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td style="white-space:nowrap;font-weight:700">
                                            ${{ number_format($linea['precio_unitario_centavos'] / 100, 2) }}
                                        </td>
                                        <td style="text-align:center">
                                            <form class="quantity-form" method="post" action="{{ route('carrito.update', $linea['detalle_id']) }}" style="justify-content:center">
                                                @csrf
                                                @method('PUT')
                                                <input class="quantity-input" type="number" name="cantidad" value="{{ $linea['cantidad'] }}" min="1" max="{{ $linea['stock_actual'] }}">
                                                <button class="quantity-btn" type="submit" title="Actualizar cantidad">Actualizar</button>
                                            </form>
                                            <small style="color:var(--muted);font-size:.75rem">Máx: {{ $linea['stock_actual'] }}</small>
                                        </td>
                                        <td style="text-align:right;white-space:nowrap;font-weight:900;color:var(--blue-dark)">
                                            ${{ number_format($linea['subtotal_linea_centavos'] / 100, 2) }}
                                        </td>
                                        <td style="text-align:right">
                                            <form method="post" action="{{ route('carrito.destroy', $linea['detalle_id']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-delete-item" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--line);flex-wrap:wrap;gap:12px">
                        <a class="back-button" href="{{ route('catalogo.index') }}">&larr; Continuar comprando</a>
                        <form method="post" action="{{ route('carrito.vaciar') }}">
                            @csrf
                            <button class="btn-delete-item" type="submit" style="color:#b42318">Vaciar todo el carrito</button>
                        </form>
                    </div>
                </div>
            </div>

            <aside class="cart-summary-card">
                <h2>Resumen de compra</h2>

                <!-- Formulario de cupón de descuento -->
                <div style="margin-bottom:20px;padding-bottom:18px;border-bottom:1px solid var(--line)">
                    <label for="codigo_promocion" style="font-weight:800;font-size:.9rem;display:block;margin-bottom:6px">Cupón de promoción</label>
                    @if(!empty($totales['codigo_promocion']))
                        <div style="display:flex;align-items:center;justify-content:space-between;background:#e6f9f0;padding:10px 14px;border-radius:10px;border:1px solid #a3e9c7">
                            <div>
                                <strong style="color:#085a3a">{{ $totales['codigo_promocion'] }}</strong>
                                <small style="display:block;color:#085a3a">{{ $totales['mensaje_promocion'] }}</small>
                            </div>
                            <form method="post" action="{{ route('carrito.promocion.quitar') }}" style="margin:0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="background:transparent;border:0;color:#9e1c1c;font-weight:800;cursor:pointer">&times; Quitar</button>
                            </form>
                        </div>
                    @else
                        <form method="post" action="{{ route('carrito.promocion.aplicar') }}" style="display:flex;gap:8px">
                            @csrf
                            <input id="codigo_promocion" name="codigo" type="text" placeholder="Ej. AVANZADO10" style="flex:1;min-height:40px;padding:6px 10px;border:1px solid #bcc9dc;border-radius:8px;text-transform:uppercase;font-weight:800">
                            <button class="button" type="submit" style="min-height:40px;padding:6px 14px">Aplicar</button>
                        </form>
                    @endif
                </div>

                <table class="summary-table">
                    <tbody>
                        <tr>
                            <td>Subtotal ({{ $totales['total_items'] }} cupos)</td>
                            <td>{{ $totales['subtotal_formateado'] }}</td>
                        </tr>

                        @if($totales['descuento_centavos'] > 0)
                            <tr style="color:#085a3a">
                                <td>Descuento promoción</td>
                                <td>-{{ $totales['descuento_formateado'] }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td>Base gravable</td>
                            <td>{{ $totales['base_gravable_formateado'] }}</td>
                        </tr>

                        @foreach($totales['impuestos'] as $imp)
                            <tr>
                                <td>{{ $imp['nombre'] }} ({{ $imp['porcentaje'] }})</td>
                                <td>{{ $imp['monto_formateado'] }}</td>
                            </tr>
                        @endforeach

                        <tr class="summary-total-row">
                            <td>Total a pagar</td>
                            <td>{{ $totales['total_formateado'] }}</td>
                        </tr>
                    </tbody>
                </table>

                <div style="display:grid;gap:10px">
                    <a class="button" href="{{ route('checkout.index') }}" style="width:100%;min-height:48px;font-size:1.05rem">
                        Proceder al checkout &rarr;
                    </a>
                </div>

                <div style="margin-top:18px;font-size:.8rem;color:var(--muted);text-align:center;line-height:1.4">
                    Pago simulado con Tarjeta demo, PayPal demo, Transferencia demo o Monedero.
                </div>
            </aside>
        </div>
    @endif
</section>
@endsection
