@extends('layouts.app')
@section('title', 'Promociones y Descuentos | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div class="admin-section-heading">
            <div>
                <span class="section-eyebrow">Marketing y fidelización</span>
                <h1>Promociones y cupones</h1>
            </div>
            <a class="button" href="{{ route('admin.tienda.promociones.create') }}">+ Nueva promoción</a>
        </div>

        <div style="background:#eef5ff;border:1px solid #ccdcf6;border-radius:12px;padding:16px 20px;margin-bottom:24px;font-size:.9rem;color:#1e3a8a;line-height:1.6">
            <strong>Reglas y unidades del negocio:</strong>
            <ul style="margin:6px 0 0;padding-left:18px">
                <li><strong>Porcentaje:</strong> Almacenado en puntos base (1000 = 10.00%, 1500 = 15.00%).</li>
                <li><strong>Monto fijo:</strong> Almacenado en centavos enteros ($50.00 MXN = 5000 centavos).</li>
                <li><strong>Aplicabilidad:</strong> Una promoción por compra, no acumulable, calculada exclusivamente en servidor.</li>
            </ul>
        </div>

        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Tipo y valor</th>
                        <th>Vigencia</th>
                        <th>Usos</th>
                        <th>Alcance</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promociones as $promo)
                        <tr>
                            <td style="font-family:monospace;font-weight:900;font-size:1.05rem;color:var(--blue-dark)">
                                {{ $promo->codigo }}
                            </td>
                            <td>
                                <strong>{{ $promo->nombre }}</strong>
                                <small style="display:block;color:var(--muted)">{{ Str::limit($promo->descripcion, 50) }}</small>
                            </td>
                            <td style="font-weight:800;color:var(--plus)">
                                {{ $promo->etiquetaValor() }}
                                <small style="display:block;color:var(--muted)">{{ ucfirst(str_replace('_', ' ', $promo->tipo_descuento)) }}</small>
                            </td>
                            <td style="font-size:.85rem;color:var(--muted)">
                                {{ $promo->fecha_inicio->format('d/m/Y') }} al {{ $promo->fecha_fin->format('d/m/Y') }}
                            </td>
                            <td style="font-size:.9rem">
                                <strong>{{ $promo->usos_actuales }}</strong>
                                @if($promo->limite_usos)
                                    / {{ $promo->limite_usos }}
                                @else
                                    (Sin límite)
                                @endif
                            </td>
                            <td style="font-size:.85rem">
                                @if($promo->productos_count === 0)
                                    <span class="store-badge badge-respondido">Toda la tienda</span>
                                @else
                                    <span class="store-badge badge-activo">{{ $promo->productos_count }} productos</span>
                                @endif
                            </td>
                            <td>
                                <span class="store-badge {{ $promo->isVigente() ? 'badge-activo' : 'badge-rechazado' }}">
                                    {{ $promo->isVigente() ? 'Vigente' : 'Inactiva / Vencida' }}
                                </span>
                            </td>
                            <td style="text-align:right;white-space:nowrap">
                                <div style="display:inline-flex;gap:6px">
                                    <a class="back-button" href="{{ route('admin.tienda.promociones.edit', $promo) }}" style="padding:6px 12px;min-height:36px;font-size:.85rem">Editar</a>
                                    <form method="post" action="{{ route('admin.tienda.promociones.destroy', $promo) }}" onsubmit="return confirm('¿Seguro que deseas eliminar esta promoción?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-delete-item" type="submit" style="min-height:36px">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px">No hay promociones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">
            {{ $promociones->links() }}
        </div>
    </section>
</div>
@endsection
