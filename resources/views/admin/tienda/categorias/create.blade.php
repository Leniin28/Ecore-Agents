@extends('layouts.app')
@section('title', 'Nueva Categoría | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div style="margin-bottom:20px">
            <a class="back-button" href="{{ route('admin.tienda.categorias.index') }}">&larr; Volver a categorías</a>
        </div>

        <article class="form-card" style="max-width:680px;padding:32px">
            <span class="section-eyebrow">Catálogo</span>
            <h1 style="margin:4px 0 16px;font-size:1.8rem">Nueva categoría</h1>

            <form method="post" action="{{ route('admin.tienda.categorias.store') }}" style="display:grid;gap:18px">
                @csrf

                <div>
                    <label for="nombre" style="font-weight:800;display:block;margin-bottom:6px">Nombre de la categoría *</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                </div>

                <div>
                    <label for="descripcion" style="font-weight:800;display:block;margin-bottom:6px">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" style="width:100%;padding:10px 12px;border:1px solid #bcc9dc;border-radius:9px">{{ old('descripcion') }}</textarea>
                </div>

                <div>
                    <label style="font-weight:800;display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', true))>
                        <span>Categoría activa en la tienda</span>
                    </label>
                </div>

                <button class="button" type="submit">Guardar categoría</button>
            </form>
        </article>
    </section>
</div>
@endsection
