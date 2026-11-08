@extends('layouts.app')
@section('title', 'Detalle de Monedero | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div style="margin-bottom:20px">
            <a class="back-button" href="{{ route('admin.tienda.monederos.index') }}">&larr; Volver a monederos</a>
        </div>

        <div style="display:grid;grid-template-columns:minmax(0, 1.3fr) minmax(320px, 1fr);gap:28px;align-items:start;margin-bottom:32px">
            <!-- Resumen del monedero del cliente -->
            <article class="checkout-card" style="margin:0;padding:28px">
                <span class="section-eyebrow">Cuenta de cliente</span>
                <h1 style="margin:4px 0 10px;font-size:1.8rem">{{ $monedero->cliente->nombre }}</h1>
                <p style="color:var(--muted);margin:0 0 18px">{{ $monedero->cliente->correo }} · Empresa: {{ $monedero->cliente->empresa ?? 'Particular' }}</p>

                <div style="padding:20px;background:#f8faff;border:1px solid #dbe6f6;border-radius:14px">
                    <small style="color:var(--muted);font-weight:800;text-transform:uppercase">Saldo total disponible</small>
                    <div style="font-size:2.4rem;font-weight:900;color:var(--blue-dark);margin:4px 0">
                        {{ $monedero->saldo_formateado }}
                    </div>
                    <small style="color:var(--muted)">Centavos registrados en base de datos: {{ $monedero->saldo_centavos }}</small>
                </div>
            </article>

            <!-- Formulario de ajuste auditado -->
            <article class="form-card" style="margin:0;padding:28px">
                <span class="section-eyebrow">Operación especial</span>
                <h2 style="margin:4px 0 14px;font-size:1.25rem">Ajuste administrativo auditado</h2>

                <form method="post" action="{{ route('admin.tienda.monederos.ajustar', $monedero) }}" style="display:grid;gap:14px">
                    @csrf

                    <div>
                        <label for="tipo_ajuste" style="font-weight:800;display:block;margin-bottom:6px">Tipo de ajuste *</label>
                        <select id="tipo_ajuste" name="tipo_ajuste" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                            <option value="credito">Crédito (+ Acreditar saldo al cliente)</option>
                            <option value="debito">Débito (- Deducir saldo al cliente)</option>
                        </select>
                    </div>

                    <div>
                        <label for="monto_pesos" style="font-weight:800;display:block;margin-bottom:6px">Monto del ajuste (MXN) *</label>
                        <input id="monto_pesos" name="monto_pesos" type="number" step="0.01" min="0.01" value="{{ old('monto_pesos', '100.00') }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px;font-weight:800">
                    </div>

                    <div>
                        <label for="motivo" style="font-weight:800;display:block;margin-bottom:6px">Motivo obligatorio del ajuste *</label>
                        <textarea id="motivo" name="motivo" rows="3" placeholder="Indica la justificación administrativa de la corrección..." required style="width:100%;padding:10px 12px;border:1px solid #bcc9dc;border-radius:9px;line-height:1.5">{{ old('motivo') }}</textarea>
                        <small style="color:var(--muted)">Quedará registrado permanentemente en la bitácora de auditoría.</small>
                    </div>

                    <button class="button" type="submit" style="min-height:44px">Aplicar y auditar ajuste</button>
                </form>
            </article>
        </div>

        <!-- Movimientos históricos del monedero -->
        <article class="checkout-card" style="padding:28px">
            <h2 style="margin:0 0 16px;font-size:1.3rem">Historial de movimientos de este monedero</h2>

            @if($movimientos->isEmpty())
                <p style="color:var(--muted);text-align:center;padding:24px 0">No hay movimientos registrados.</p>
            @else
                <div class="table-responsive">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Tipo</th>
                                <th>Operador / Usuario</th>
                                <th style="text-align:right">Monto</th>
                                <th style="text-align:right">Saldo resultante</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movimientos as $mov)
                                <tr>
                                    <td style="white-space:nowrap;font-size:.85rem;color:var(--muted)">
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
                                    <td style="font-size:.85rem">
                                        {{ $mov->usuario->name ?? 'Sistema' }}
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

                <div style="margin-top:20px">
                    {{ $movimientos->links() }}
                </div>
            @endif
        </article>
    </section>
</div>
@endsection
