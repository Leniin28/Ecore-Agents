@extends('layouts.app')
@section('title', 'Editar Impuesto | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div style="margin-bottom:20px">
            <a class="back-button" href="{{ route('admin.tienda.impuestos.index') }}">&larr; Volver a impuestos</a>
        </div>

        <article class="form-card" style="max-width:680px;padding:32px">
            <span class="section-eyebrow">Configuración</span>
            <h1 style="margin:4px 0 16px;font-size:1.8rem">Editar impuesto: {{ $impuesto->nombre }}</h1>

            <form method="post" action="{{ route('admin.tienda.impuestos.update', $impuesto) }}" style="display:grid;gap:18px">
                @csrf
                @method('PUT')

                <div>
                    <label for="nombre" style="font-weight:800;display:block;margin-bottom:6px">Nombre comercial del impuesto *</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $impuesto->nombre) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                </div>

                <div>
                    <label for="porcentaje" style="font-weight:800;display:block;margin-bottom:6px">Tasa porcentual (%) *</label>
                    <input id="porcentaje" name="porcentaje" type="number" step="0.01" min="0" max="100" value="{{ old('porcentaje', number_format($impuesto->puntos_base / 100, 2, '.', '')) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px;font-size:1.1rem;font-weight:800">
                    <small style="color:var(--muted)">Puntos base calculados: {{ $impuesto->puntos_base }} bps</small>
                </div>

                <div>
                    <label style="font-weight:800;display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', $impuesto->activo))>
                        <span>Impuesto activo y aplicable en checkout</span>
                    </label>
                </div>

                <button class="button" type="submit">Actualizar impuesto</button>
            </form>
        </article>
    </section>
</div>
@endsection
