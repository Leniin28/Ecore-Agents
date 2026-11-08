@extends('layouts.app')
@section('title', 'Monederos de Clientes | Admin ECore')

@section('content')
<div class="admin-layout">
    @include('admin.tienda._sidebar')

    <section class="admin-content">
        <div class="admin-section-heading">
            <div>
                <span class="section-eyebrow">Gestión financiera</span>
                <h1>Monederos de clientes</h1>
            </div>
        </div>

        <form class="customer-filters" method="get" action="{{ route('admin.tienda.monederos.index') }}" style="margin-bottom:24px">
            <div style="flex:2">
                <label for="buscar">Buscar cliente</label>
                <input id="buscar" name="buscar" value="{{ request('buscar') }}" type="search" placeholder="Nombre o correo del cliente...">
            </div>
            <div style="display:flex;gap:8px">
                <button class="button" type="submit">Buscar</button>
                <a class="back-button" href="{{ route('admin.tienda.monederos.index') }}">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Correo</th>
                        <th>Empresa</th>
                        <th style="text-align:right">Saldo actual</th>
                        <th>Última actividad</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monederos as $monedero)
                        <tr>
                            <td style="font-weight:800">{{ $monedero->cliente->nombre }}</td>
                            <td>{{ $monedero->cliente->correo }}</td>
                            <td>{{ $monedero->cliente->empresa ?? 'Particular' }}</td>
                            <td style="text-align:right;font-weight:900;color:var(--blue-dark);font-size:1.1rem">
                                {{ $monedero->saldo_formateado }}
                            </td>
                            <td style="font-size:.85rem;color:var(--muted)">
                                {{ $monedero->updated_at->format('d/m/Y H:i') }}
                            </td>
                            <td style="text-align:right">
                                <a class="button" href="{{ route('admin.tienda.monederos.show', $monedero) }}" style="padding:6px 14px;min-height:36px;font-size:.85rem">
                                    Ver movimientos / Ajustar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:32px">No hay monederos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">
            {{ $monederos->links() }}
        </div>
    </section>
</div>
@endsection
