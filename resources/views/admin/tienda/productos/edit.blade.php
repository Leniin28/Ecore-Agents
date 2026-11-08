@extends('layouts.app')
@section('title', 'Editar Oferta Comercial | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div style="margin-bottom:20px">
            <a class="back-button" href="{{ route('admin.tienda.productos.index') }}">&larr; Volver al catálogo</a>
        </div>

        <article class="form-card" style="max-width:800px;padding:32px">
            <span class="section-eyebrow">Gestión comercial</span>
            <h1 style="margin:4px 0 16px;font-size:1.8rem">Editar oferta: {{ $producto->nombre }}</h1>

            <form method="post" action="{{ route('admin.tienda.productos.update', $producto) }}" style="display:grid;gap:18px">
                @csrf
                @method('PUT')

                <div>
                    <label for="precio_venta_unitario" style="font-weight:800;display:block;margin-bottom:6px">Precio de venta comercial (MXN) *</label>
                    <input id="precio_venta_unitario" name="precio_venta_unitario" type="number" step="0.01" min="0" value="{{ old('precio_venta_unitario', $producto->precio_venta_unitario) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px;font-size:1.1rem;font-weight:800">
                    <small style="color:var(--muted)">Costo unitario interno SCM: ${{ number_format((float) $producto->costo_unitario, 2) }} MXN</small>
                </div>

                <div>
                    <label for="categoria_id" style="font-weight:800;display:block;margin-bottom:6px">Categoría comercial normalizada</label>
                    <select id="categoria_id" name="categoria_id" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                        <option value="">Sin categoría asignada</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" @selected(old('categoria_id', $producto->categoria_id) == $cat->id)>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:16px;background:#f8faff;border:1px solid #dbe6f6;border-radius:12px">
                    <div>
                        <label style="font-weight:800;display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="hidden" name="visible_tienda" value="0">
                            <input type="checkbox" name="visible_tienda" value="1" @checked(old('visible_tienda', $producto->visible_tienda))>
                            <span>Visible en tienda pública</span>
                        </label>
                        <small style="color:var(--muted);display:block;margin-top:4px">Si está desmarcado, el recurso permanece solo en SCM/ERP interno.</small>
                    </div>

                    <div>
                        <label style="font-weight:800;display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="hidden" name="destacado" value="0">
                            <input type="checkbox" name="destacado" value="1" @checked(old('destacado', $producto->destacado))>
                            <span>Producto destacado</span>
                        </label>
                        <small style="color:var(--muted);display:block;margin-top:4px">Aparece resaltado con etiqueta especial en la tienda.</small>
                    </div>
                </div>

                <div>
                    <label for="imagen_url" style="font-weight:800;display:block;margin-bottom:6px">URL de imagen o ilustración</label>
                    <input id="imagen_url" name="imagen_url" type="text" value="{{ old('imagen_url', $producto->imagen_url) }}" placeholder="/images/plan-inicio.svg o URL web" style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                </div>

                <div>
                    <label for="descripcion" style="font-weight:800;display:block;margin-bottom:6px">Descripción comercial</label>
                    <textarea id="descripcion" name="descripcion" rows="4" style="width:100%;padding:10px 12px;border:1px solid #bcc9dc;border-radius:9px;line-height:1.6">{{ old('descripcion', $producto->descripcion) }}</textarea>
                </div>

                <button class="button" type="submit" style="min-height:48px;font-size:1.05rem">Guardar cambios comerciales</button>
            </form>
        </article>
    </section>
</div>
@endsection
