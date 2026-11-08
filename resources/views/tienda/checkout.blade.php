@extends('layouts.app')
@section('title', 'Finalizar Compra Demo | ECore Agents')

@section('content')
<section class="section" style="max-width:1140px;margin:0 auto;padding-top:40px">
    <div class="section-heading" style="text-align:left;margin-bottom:28px">
        <span class="section-eyebrow">Checkout seguro demo</span>
        <h1>Finalizar compra</h1>
        <p>Completa tus datos de contacto y selecciona tu método de pago simulado.</p>
    </div>

    <form method="post" action="{{ route('checkout.store') }}">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ Str::uuid() }}">

        <div class="checkout-layout">
            <div>
                <!-- Paso 1: Datos de contacto y entrega del servicio -->
                <article class="checkout-card">
                    <span class="section-eyebrow">Paso 01</span>
                    <h2>Datos de contacto y entrega</h2>

                    <div style="display:grid;gap:14px">
                        <div>
                            <label for="nombre_contacto" style="font-weight:800;display:block;margin-bottom:6px">Nombre completo *</label>
                            <input id="nombre_contacto" name="nombre_contacto" type="text" value="{{ old('nombre_contacto', $cliente->nombre ?? auth()->user()->name) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                            <div>
                                <label for="correo_contacto" style="font-weight:800;display:block;margin-bottom:6px">Correo electrónico *</label>
                                <input id="correo_contacto" name="correo_contacto" type="email" value="{{ old('correo_contacto', $cliente->correo ?? auth()->user()->email) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                            </div>
                            <div>
                                <label for="telefono_contacto" style="font-weight:800;display:block;margin-bottom:6px">Teléfono de contacto / WhatsApp *</label>
                                <input id="telefono_contacto" name="telefono_contacto" type="text" value="{{ old('telefono_contacto', $cliente->telefono ?? '') }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                            </div>
                        </div>

                        <div>
                            <label for="empresa" style="font-weight:800;display:block;margin-bottom:6px">Empresa o negocio (opcional)</label>
                            <input id="empresa" name="empresa" type="text" value="{{ old('empresa', $cliente->empresa ?? '') }}" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        </div>

                        <div>
                            <label for="notas_servicio" style="font-weight:800;display:block;margin-bottom:6px">Instrucciones o notas para tu agente (opcional)</label>
                            <textarea id="notas_servicio" name="notas_servicio" rows="3" placeholder="Indica horarios, tono de voz o detalles particulares para el servicio..." style="width:100%;padding:10px 12px;border:1px solid #bcc9dc;border-radius:9px">{{ old('notas_servicio') }}</textarea>
                        </div>
                    </div>
                </article>

                <!-- Paso 2: Método de pago simulado -->
                <article class="checkout-card">
                    <span class="section-eyebrow">Paso 02</span>
                    <h2>Método de pago simulado</h2>
                    <p style="color:var(--muted);font-size:.9rem;margin-top:0">
                        Selecciona uno de los métodos de prueba académicos. No se debitará dinero real ni se solicitan contraseñas o CVV reales.
                    </p>

                    <div class="payment-methods-grid">
                        <!-- Tarjeta demo -->
                        <label class="payment-option">
                            <input type="radio" name="metodo_pago" value="tarjeta_demo" @checked(old('metodo_pago', 'tarjeta_demo') === 'tarjeta_demo') required>
                            <div style="flex:1">
                                <strong>Tarjeta de crédito / débito (Demo)</strong>
                                <p style="margin:4px 0 0;color:var(--muted);font-size:.85rem">
                                    Simulación instantánea con tarjeta bancaria de prueba (Aprobación inmediata).
                                </p>
                            </div>
                        </label>

                        <!-- PayPal demo -->
                        <label class="payment-option">
                            <input type="radio" name="metodo_pago" value="paypal_demo" @checked(old('metodo_pago') === 'paypal_demo')>
                            <div style="flex:1">
                                <strong>PayPal (Demo)</strong>
                                <p style="margin:4px 0 0;color:var(--muted);font-size:.85rem">
                                    Pago simulado con cuenta digital PayPal Sandbox.
                                </p>
                            </div>
                        </label>

                        <!-- Transferencia demo -->
                        <label class="payment-option">
                            <input type="radio" name="metodo_pago" value="transferencia_demo" @checked(old('metodo_pago') === 'transferencia_demo')>
                            <div style="flex:1">
                                <strong>Transferencia bancaria / SPEI (Demo)</strong>
                                <p style="margin:4px 0 0;color:var(--muted);font-size:.85rem">
                                    Genera referencia y CLABE simulada. La orden se registra como pendiente.
                                </p>
                            </div>
                        </label>

                        <!-- Monedero demo -->
                        @php
                            $puedePagarMonedero = $monedero->saldo_centavos >= $totales['total_centavos'];
                        @endphp
                        <label class="payment-option" style="@if(!$puedePagarMonedero) opacity:.65; @endif">
                            <input type="radio" name="metodo_pago" value="monedero_demo" @checked(old('metodo_pago') === 'monedero_demo') @disabled(!$puedePagarMonedero)>
                            <div style="flex:1">
                                <div style="display:flex;justify-content:space-between;align-items:center">
                                    <strong>Monedero virtual ECore</strong>
                                    <span style="font-weight:800;color:{{ $puedePagarMonedero ? 'var(--blue)' : '#b42318' }}">
                                        Saldo: {{ $monedero->saldo_formateado }}
                                    </span>
                                </div>
                                <p style="margin:4px 0 0;color:var(--muted);font-size:.85rem">
                                    @if($puedePagarMonedero)
                                        Se descontarán {{ $totales['total_formateado'] }} directamente de tu saldo disponible.
                                    @else
                                        Saldo insuficiente (requieres {{ $totales['total_formateado'] }}). <a href="{{ route('monedero.index') }}" target="_blank" style="color:var(--blue);font-weight:700">Recargar monedero</a>
                                    @endif
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Control de prueba de rechazo -->
                    <div style="margin-top:20px;padding:14px;background:#fff8f2;border:1px solid #ffd8bf;border-radius:10px">
                        <label style="display:flex;align-items:center;gap:8px;font-size:.9rem;font-weight:700;cursor:pointer;color:#d4380d">
                            <input type="checkbox" name="simular_fallo" value="1" @checked(old('simular_fallo'))>
                            <span>Simular rechazo de pago (Para verificar que se revierten los cambios y no se descuenta stock)</span>
                        </label>
                    </div>
                </article>
            </div>

            <!-- Resumen de compra final -->
            <aside class="cart-summary-card">
                <h2>Resumen final</h2>

                <div style="margin-bottom:18px;max-height:220px;overflow-y:auto;padding-right:4px">
                    @foreach($totales['lineas'] as $linea)
                        <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:10px;font-size:.9rem">
                            <div>
                                <strong>{{ $linea['cantidad'] }}x {{ $linea['nombre'] }}</strong>
                                @if(!empty($linea['configuracion']['modulos']))
                                    <small style="display:block;color:var(--muted)">
                                        {{ count($linea['configuracion']['modulos']) }} módulos extra
                                    </small>
                                @endif
                            </div>
                            <span style="font-weight:800">
                                ${{ number_format($linea['subtotal_linea_centavos'] / 100, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <table class="summary-table">
                    <tbody>
                        <tr>
                            <td>Subtotal</td>
                            <td>{{ $totales['subtotal_formateado'] }}</td>
                        </tr>

                        @if($totales['descuento_centavos'] > 0)
                            <tr style="color:#085a3a">
                                <td>Descuento ({{ $totales['codigo_promocion'] }})</td>
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

                <button class="button" type="submit" style="width:100%;min-height:50px;font-size:1.1rem;margin-top:10px">
                    Confirmar y pagar compra demo
                </button>

                <div style="margin-top:16px;text-align:center">
                    <a class="back-button" href="{{ route('carrito.index') }}" style="display:inline-block;width:100%">
                        &larr; Volver al carrito
                    </a>
                </div>
            </aside>
        </div>
    </form>
</section>
@endsection
