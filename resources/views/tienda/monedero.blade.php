@extends('layouts.app')
@section('title', 'Mi Monedero Virtual | ECore Agents')

@section('content')
<section class="section" style="max-width:1040px;margin:0 auto;padding-top:40px">
    <div class="wallet-hero">
        <span class="section-eyebrow" style="color:#dbeafe">Finanzas del cliente</span>
        <h1>Mi Monedero Virtual ECore</h1>
        <p style="margin:0;opacity:.9">Saldo disponible para adquirir planes, cupos y complementos de atención.</p>

        <div class="wallet-balance-val">
            {{ $monedero->saldo_formateado }}
        </div>
        <small style="opacity:.8">Un monedero exclusivo asociado a tu ficha de cliente CRM.</small>
    </div>

    <div style="display:grid;grid-template-columns:minmax(0, 1.2fr) minmax(280px, .9fr);gap:28px;align-items:start;margin-bottom:34px">
        <article class="checkout-card" style="margin:0">
            <span class="section-eyebrow">Recarga simulada</span>
            <h2>Agregar saldo a mi monedero</h2>
            <p style="color:var(--muted);font-size:.9rem;margin-top:0">
                Selecciona un monto sugerido o introduce el valor que deseas recargar en tu monedero de prueba.
            </p>

            <form method="post" action="{{ route('monedero.recargar') }}">
                @csrf
                <input type="hidden" name="referencia_operacion" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div style="margin-bottom:14px">
                    <label for="monto_pesos" style="font-weight:800;display:block;margin-bottom:6px">Monto a recargar (MXN) *</label>
                    <input id="monto_pesos" name="monto_pesos" type="number" step="0.01" min="10" max="50000" value="{{ old('monto_pesos', 500) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px;font-size:1.1rem;font-weight:800">
                </div>

                <div style="margin-bottom:18px">
                    <small style="color:var(--muted);display:block;margin-bottom:6px;font-weight:700">Montos rápidos:</small>
                    <div class="wallet-quick-grid">
                        <button class="wallet-quick-btn" type="button" onclick="document.getElementById('monto_pesos').value = 500">+$500</button>
                        <button class="wallet-quick-btn" type="button" onclick="document.getElementById('monto_pesos').value = 1000">+$1,000</button>
                        <button class="wallet-quick-btn" type="button" onclick="document.getElementById('monto_pesos').value = 2000">+$2,000</button>
                        <button class="wallet-quick-btn" type="button" onclick="document.getElementById('monto_pesos').value = 5000">+$5,000</button>
                    </div>
                </div>

                <button class="button" type="submit" style="width:100%;min-height:46px">
                    Realizar recarga demostrativa
                </button>
            </form>

            <div style="margin-top:16px;padding:12px;background:#f8fafc;border-radius:8px;font-size:.82rem;color:var(--muted)">
                <strong>Nota académica:</strong> Esta recarga se almacena como saldo en tu cuenta demo para realizar compras en el catálogo sin requerir pasarelas de pago reales.
            </div>
        </article>

        <article class="checkout-card" style="margin:0;background:linear-gradient(180deg,#fcfdff,#f5f8fd)">
            <span class="section-eyebrow">Ventajas del monedero</span>
            <h2 style="font-size:1.15rem">¿Cómo funciona?</h2>
            <ul style="padding-left:18px;margin:12px 0;line-height:1.7;color:var(--muted);font-size:.9rem">
                <li><strong>Un solo clic en checkout:</strong> Paga al instante sin llenar datos de tarjeta.</li>
                <li><strong>Control de cupos:</strong> Consulta tu historial y saldo resultante tras cada compra.</li>
                <li><strong>Ajustes auditados:</strong> Cualquier corrección administrativa queda registrada en bitácora.</li>
                <li><strong>Sin saldo negativo:</strong> El sistema previene automáticamente cargos que superen el saldo disponible.</li>
            </ul>
            <a class="back-button" href="{{ route('catalogo.index') }}" style="display:block;text-align:center;width:100%">Ir a la tienda</a>
        </article>
    </div>

    <!-- Historial de movimientos del monedero -->
    <article class="checkout-card" style="margin:0">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
            <div>
                <span class="section-eyebrow">Auditoría de saldo</span>
                <h2 style="margin:0">Historial de movimientos</h2>
            </div>
            <span style="color:var(--muted);font-size:.85rem;font-weight:700">Inmutable · Orden cronológico</span>
        </div>

        @if($movimientos->isEmpty())
            <div style="text-align:center;padding:36px 14px;background:#f9fafb;border-radius:12px">
                <p style="color:var(--muted);margin:0">Aún no tienes movimientos registrados en tu monedero.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Tipo</th>
                            <th style="text-align:right">Monto</th>
                            <th style="text-align:right">Saldo resultante</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movimientos as $mov)
                            <tr>
                                <td style="white-space:nowrap;font-size:.88rem;color:var(--muted)">
                                    {{ $mov->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <strong>{{ $mov->concepto }}</strong>
                                    @if($mov->motivo)
                                        <small style="display:block;color:var(--muted)">Motivo: {{ $mov->motivo }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="store-badge @if($mov->tipo === 'recarga_demo') badge-aprobado @elseif($mov->tipo === 'pago_compra') badge-respondido @else badge-pendiente @endif">
                                        {{ $mov->tipoLabel() }}
                                    </span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;font-weight:800;color:{{ $mov->monto_centavos >= 0 ? '#085a3a' : '#b42318' }}">
                                    {{ $mov->monto_formateado }}
                                </td>
                                <td style="text-align:right;white-space:nowrap;font-weight:900">
                                    {{ $mov->saldo_formateado }}
                                </td>
                                <td>
                                    <small style="font-family:monospace;color:var(--muted)">{{ Str::limit($mov->referencia, 13) }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:18px">
                {{ $movimientos->links() }}
            </div>
        @endif
    </article>
</section>
@endsection
