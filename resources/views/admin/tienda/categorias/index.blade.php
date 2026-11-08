@extends('layouts.app')
@section('title', 'Categorías Comerciales | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div class="admin-section-heading">
            <div>
                <span class="section-eyebrow">Organización del catálogo</span>
                <h1>Categorías normalizadas</h1>
            </div>
            <a class="button" href="{{ route('admin.tienda.categorias.create') }}">+ Nueva categoría</a>
        </div>

        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Slug</th>
                        <th>Descripción</th>
                        <th>Productos vinculados</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categorias as $cat)
                        <tr>
                            <td style="font-weight:800">{{ $cat->nombre }}</td>
                            <td style="font-family:monospace;color:var(--muted)">{{ $cat->slug }}</td>
                            <td>{{ $cat->descripcion ?? '-' }}</td>
                            <td style="font-weight:800">{{ $cat->productos_count }}</td>
                            <td>
                                <span class="store-badge {{ $cat->activo ? 'badge-activo' : 'badge-inactivo' }}">
                                    {{ $cat->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                <div style="display:inline-flex;gap:8px">
                                    <a class="back-button" href="{{ route('admin.tienda.categorias.edit', $cat) }}" style="padding:6px 12px;min-height:36px;font-size:.85rem">Editar</a>
                                    @if($cat->productos_count === 0)
                                        <form method="post" action="{{ route('admin.tienda.categorias.destroy', $cat) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-delete-item" type="submit" style="min-height:36px">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:32px">No hay categorías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">
            {{ $categorias->links() }}
        </div>
    </section>
</div>
@endsection
