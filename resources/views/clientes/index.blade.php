@extends('layouts.app')

@section('title', 'Clientes | ECore Agents')
@section('body-class', 'admin-page')

@section('content')
    <section class="admin-hero">
        <span class="section-eyebrow">CRM inicial</span>
        <h1>Clientes</h1>
        <p>Centraliza la información básica de cada cliente y mantenla disponible para el equipo.</p>
    </section>

    <section class="admin-layout">
        @include('admin._sidebar')

        <div class="admin-content customer-content">
            @if(session('success'))
                <p class="success-alert" role="status">{{ session('success') }}</p>
            @endif

            <div class="customer-heading">
                <div><span class="section-eyebrow">Directorio</span><h2>Listado de clientes</h2></div>
                <a class="button" href="{{ route('clientes.create') }}">Nuevo cliente</a>
            </div>

            <form class="customer-filters" method="get" action="{{ route('clientes.index') }}">
                <div><label for="buscar">Buscar</label><input id="buscar" name="buscar" type="search" value="{{ $buscar }}" placeholder="Nombre, correo o empresa"></div>
                <div><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><option value="activo" @selected($estado === 'activo')>Activo</option><option value="inactivo" @selected($estado === 'inactivo')>Inactivo</option></select></div>
                <div><label for="etapa_crm">Etapa CRM</label><select id="etapa_crm" name="etapa_crm"><option value="">Todas</option>@foreach(\App\Models\Cliente::etapasCrm() as $value => $label)<option value="{{ $value }}" @selected($etapaCrm === $value)>{{ $label }}</option>@endforeach</select></div>
                <button class="button" type="submit">Aplicar filtros</button>
                @if($buscar || $estado || $etapaCrm)
                    <a class="back-button" href="{{ route('clientes.index') }}">Limpiar</a>
                @endif
            </form>

            @if($clientes->isEmpty())
                <article class="admin-empty-state"><span class="placeholder-icon">C</span><h3>Sin clientes</h3><p>No hay clientes que coincidan con los filtros actuales.</p></article>
            @else
                <div class="customer-table-wrap">
                    <table class="customer-table">
                        <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Empresa</th><th>Registro</th><th>Estado</th><th>Etapa CRM</th><th>Acciones</th></tr></thead>
                        <tbody>
                            @foreach($clientes as $cliente)
                                <tr>
                                    <td><strong>{{ $cliente->nombre }}</strong></td>
                                    <td>{{ $cliente->correo }}</td>
                                    <td>{{ $cliente->telefono }}</td>
                                    <td>{{ $cliente->empresa ?: 'Sin empresa' }}</td>
                                    <td>{{ $cliente->fecha_registro->format('d/m/Y') }}</td>
                                    <td><span class="status-badge status-{{ $cliente->estado }}">{{ ucfirst($cliente->estado) }}</span></td>
                                    <td><span class="stage-badge stage-{{ $cliente->etapa_crm }}">{{ $cliente->etapaCrmLabel() }}</span></td>
                                    <td class="table-actions">
                                        <a href="{{ route('clientes.show', $cliente) }}">Ver</a>
                                        <a href="{{ route('clientes.edit', $cliente) }}">Editar</a>
                                        @if(auth()->user()->isAdmin())
                                            <form method="post" action="{{ route('clientes.destroy', $cliente) }}" data-confirm-delete="¿Eliminar a {{ $cliente->nombre }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
@endsection
