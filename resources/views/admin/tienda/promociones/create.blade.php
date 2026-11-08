@extends('layouts.app')
@section('title', 'Nueva Promoción | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div style="margin-bottom:20px">
            <a class="back-button" href="{{ route('admin.tienda.promociones.index') }}">&larr; Volver a promociones</a>
        </div>

        <article class="form-card" style="max-width:760px;padding:32px">
            <span class="section-eyebrow">Marketing</span>
            <h1 style="margin:4px 0 16px;font-size:1.8rem">Crear nueva promoción</h1>

            <form method="post" action="{{ route('admin.tienda.promociones.store') }}" style="display:grid;gap:18px">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <label for="codigo" style="font-weight:800;display:block;margin-bottom:6px">Código de cupón *</label>
                        <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}" placeholder="Ej. AVANZADO10" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px;text-transform:uppercase;font-weight:800">
                    </div>
                    <div>
                        <label for="nombre" style="font-weight:800;display:block;margin-bottom:6px">Nombre comercial *</label>
                        <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" placeholder="Ej. 10% de descuento en Agente Avanzado" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <label for="tipo_descuento" style="font-weight:800;display:block;margin-bottom:6px">Tipo de descuento *</label>
                        <select id="tipo_descuento" name="tipo_descuento" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                            <option value="porcentaje" @selected(old('tipo_descuento') === 'porcentaje')>Porcentaje (%) [Puntos base: 1000 = 10%]</option>
                            <option value="monto_fijo" @selected(old('tipo_descuento') === 'monto_fijo')>Monto fijo en dinero ($ MXN)</option>
                        </select>
                    </div>
                    <div>
                        <label for="valor_humano" style="font-weight:800;display:block;margin-bottom:6px">Valor (% o $ MXN) *</label>
                        <input id="valor_humano" name="valor_humano" type="number" step="0.01" min="0.01" value="{{ old('valor_humano') }}" placeholder="10.00 para 10% ó 50.00 para $50 MXN" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px;font-weight:800">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <label for="fecha_inicio" style="font-weight:800;display:block;margin-bottom:6px">Fecha de inicio *</label>
                        <input id="fecha_inicio" name="fecha_inicio" type="datetime-local" value="{{ old('fecha_inicio', now()->format('Y-m-d\TH:i')) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                    </div>
                    <div>
                        <label for="fecha_fin" style="font-weight:800;display:block;margin-bottom:6px">Fecha de vencimiento *</label>
                        <input id="fecha_fin" name="fecha_fin" type="datetime-local" value="{{ old('fecha_fin', now()->addMonths(3)->format('Y-m-d\TH:i')) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                    </div>
                </div>

                <div>
                    <label for="limite_usos" style="font-weight:800;display:block;margin-bottom:6px">Límite de usos máximos (opcional)</label>
                    <input id="limite_usos" name="limite_usos" type="number" min="1" value="{{ old('limite_usos') }}" placeholder="Vacío para usos ilimitados" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                </div>

                <div>
                    <label for="descripcion" style="font-weight:800;display:block;margin-bottom:6px">Descripción o condiciones</label>
                    <textarea id="descripcion" name="descripcion" rows="2" style="width:100%;padding:10px 12px;border:1px solid #bcc9dc;border-radius:9px">{{ old('descripcion') }}</textarea>
                </div>

                <div>
                    <label style="font-weight:800;display:block;margin-bottom:6px">Productos aplicables (vacío aplica a todos los productos):</label>
                    <div style="max-height:180px;overflow-y:auto;border:1px solid #bcc9dc;border-radius:9px;padding:12px;display:grid;gap:8px">
                        @foreach($productos as $prod)
                            <label style="display:flex;align-items:center;gap:8px;font-size:.9rem;cursor:pointer">
                                <input type="checkbox" name="productos[]" value="{{ $prod->id }}" @checked(in_array($prod->id, old('productos', [])))>
                                <span>{{ $prod->nombre }} (${{ number_format((float) $prod->precio_venta_unitario, 2) }})</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label style="font-weight:800;display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', true))>
                        <span>Promoción activa</span>
                    </label>
                </div>

                <button class="button" type="submit">Guardar promoción</button>
            </form>
        </article>
    </section>
</div>
@endsection
