@extends('layouts.app')
@section('title', 'Editar Categoría | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div style="margin-bottom:20px">
            <a class="back-button" href="{{ route('admin.tienda.categorias.index') }}">&larr; Volver a categorías</a>
        </div>

        <article class="form-card" style="max-width:680px;padding:32px">
            <span class="section-eyebrow">Catálogo</span>
            <h1 style="margin:4px 0 16px;font-size:1.8rem">Editar categoría: {{ $categoria->nombre }}</h1>

            <form method="post" action="{{ route('admin.tienda.categorias.update', $categoria) }}" style="display:grid;gap:18px">
                @csrf
                @method('PUT')

                <div>
                    <label for="nombre" style="font-weight:800;display:block;margin-bottom:6px">Nombre de la categoría *</label>
                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $categoria->nombre) }}" required style="width:100%;min-height:44px;padding:8px 12px;border:1px solid #bcc9dc;border-radius:9px">
                </div>

                <div>
                    <label for="descripcion" style="font-weight:800;display:block;margin-bottom:6px">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" style="width:100%;padding:10px 12px;border:1px solid #bcc9dc;border-radius:9px">{{ old('descripcion', $categoria->descripcion) }}</textarea>
                </div>

                <div>
                    <label style="font-weight:800;display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', $categoria->activo))>
                        <span>Categoría activa en la tienda</span>
                    </label>
                </div>

                <button class="button" type="submit">Actualizar categoría</button>
            </form>
        </article>
    </section>
</div>
@endsection
