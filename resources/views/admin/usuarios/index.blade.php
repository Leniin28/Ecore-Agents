@extends('layouts.app')

@section('title', 'Usuarios internos | ECore Agents')

@section('content')
<section class="section admin-shell">
    <div class="section-heading">
        <span class="section-eyebrow">Administración</span>
        <h1>Usuarios y accesos</h1>
        <p>Los clientes provienen del registro público. Desde aquí se crean empleados y administradores.</p>
        <a class="button" href="{{ route('admin.usuarios.create') }}">Crear usuario interno</a>
    </div>
    @if(session('success')) <p class="status-message">{{ session('success') }}</p> @endif
    <div class="table-wrap">
        <table>
            <thead><tr><th>Nombre</th><th>Correo</th><th>Tipo</th><th>Permisos</th><th>Cliente relacionado</th><th>Acciones</th></tr></thead>
            <tbody>
            @forelse($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->name }}</td><td>{{ $usuario->email }}</td><td>{{ $usuario->roleLabel() }}</td>
                    <td>{{ $usuario->isAdmin() ? 'Acceso total' : ($usuario->permissions ? strtoupper(implode(', ', $usuario->permissions)) : 'Sin acceso interno') }}</td>
                    <td>{{ $usuario->cliente?->nombre ?? '—' }}</td>
                    <td class="table-actions">
                        @unless($usuario->isClient()) <a href="{{ route('admin.usuarios.edit', $usuario) }}">Editar</a> @endunless
                        <a href="{{ route('admin.usuarios.password.edit', $usuario) }}">Restablecer contraseña</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No hay usuarios.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $usuarios->links() }}
</section>
@endsection
