@extends('layouts.app')
@section('title', 'Proveedores SCM | ECore Agents')
@section('body-class', 'admin-page')
@section('content')
<section class="admin-hero"><span class="section-eyebrow">SCM</span><h1>Proveedores tecnológicos</h1><p>Servicios y APIs que suministran capacidad tecnológica. No hay integración ni consumo real.</p></section>
<section class="admin-layout">@include('scm._sidebar')<div class="admin-content customer-content">
    @if(session('success')) <p class="success-alert">{{ session('success') }}</p> @endif
    @error('proveedor') <p class="form-error">{{ $message }}</p> @enderror
    <div class="admin-section-heading"><h2>Proveedores</h2><a class="button" href="{{ route('scm.proveedores.create') }}">Nuevo proveedor</a></div>
    <form class="filters" method="get"><label>Buscar<input name="buscar" value="{{ $buscar }}"></label><button class="button" type="submit">Buscar</button></form>
    <div class="table-wrap"><table><thead><tr><th>Nombre</th><th>Contacto</th><th>Correo</th><th>Teléfono</th><th>Productos</th><th>Acciones</th></tr></thead><tbody>
    @forelse($proveedores as $proveedor)<tr><td>{{ $proveedor->nombre }}</td><td>{{ $proveedor->contacto ?: '—' }}</td><td>{{ $proveedor->correo ?: '—' }}</td><td>{{ $proveedor->telefono ?: '—' }}</td><td>{{ $proveedor->productos_count }}</td><td class="table-actions"><a href="{{ route('scm.proveedores.edit', $proveedor) }}">Editar</a><form method="post" action="{{ route('scm.proveedores.destroy', $proveedor) }}">@csrf @method('delete')<button class="link-button" type="submit">Eliminar</button></form></td></tr>@empty<tr><td colspan="6">No hay proveedores.</td></tr>@endforelse
    </tbody></table></div>{{ $proveedores->links() }}
</div></section>
@endsection
