@extends('layouts.app')
@section('title', 'Impuestos Comerciales | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div class="admin-section-heading">
            <div>
                <span class="section-eyebrow">Configuración fiscal simulada</span>
                <h1>Impuestos de compra</h1>
            </div>
            <a class="button" href="{{ route('admin.tienda.impuestos.create') }}">+ Nuevo impuesto</a>
        </div>

        <div style="background:#eef5ff;border:1px solid #ccdcf6;border-radius:12px;padding:16px 20px;margin-bottom:24px;font-size:.9rem;color:#1e3a8a;line-height:1.6">
            <strong>Cálculo en centavos y puntos base:</strong> Los impuestos se calculan sobre la base gravable resultante (después del descuento de promoción). La tasa se almacena en puntos base (ej. 1600 = 16.00%) para evitar redondeos inexactos.
        </div>

        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Nombre del impuesto</th>
                        <th>Tasa porcentual</th>
                        <th>Puntos base</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($impuestos as $imp)
                        <tr>
                            <td style="font-weight:800">{{ $imp->nombre }}</td>
                            <td style="font-weight:900;color:var(--blue-dark);font-size:1.1rem">{{ $imp->porcentajeFormateado() }}</td>
                            <td style="font-family:monospace;color:var(--muted)">{{ $imp->puntos_base }} bps</td>
                            <td>
                                <span class="store-badge {{ $imp->activo ? 'badge-activo' : 'badge-inactivo' }}">
                                    {{ $imp->activo ? 'Activo (se cobra en checkout)' : 'Inactivo' }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                <div style="display:inline-flex;gap:8px">
                                    <form method="post" action="{{ route('admin.tienda.impuestos.toggle', $imp) }}">
                                        @csrf
                                        <button class="quantity-btn" type="submit">
                                            {{ $imp->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <a class="button" href="{{ route('admin.tienda.impuestos.edit', $imp) }}" style="padding:6px 12px;min-height:36px;font-size:.85rem">Editar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:32px">No hay impuestos configurados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">
            {{ $impuestos->links() }}
        </div>
    </section>
</div>
@endsection
